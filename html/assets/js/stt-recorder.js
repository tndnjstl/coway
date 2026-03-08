/**
 * SttRecorder - STT(Speech-to-Text) + 녹음 통합 모듈
 * Web Speech API (SpeechRecognition) + MediaRecorder API 동시 제어
 * 한국어(ko-KR), Chrome/Edge 최적화
 */
const SttRecorder = (function () {
    let recognition = null;
    let mediaRecorder = null;
    let stream = null;
    let chunks = [];

    let finalText = '';          // 확정된 STT 텍스트
    let interimText = '';        // 인식 중 텍스트
    let startTime = null;
    let timerInterval = null;
    let isRunning = false;
    let shouldRestart = false;   // 끊김 후 자동 재시작 여부

    let _onTextUpdate = null;    // 텍스트 업데이트 콜백
    let _onComplete = null;      // 완료 콜백
    let _onError = null;         // 오류 콜백

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    // STT 지원 여부 확인
    function isSupported() {
        return !!SpeechRecognition && !!navigator.mediaDevices;
    }

    // SpeechRecognition 초기화
    function initRecognition() {
        if (!SpeechRecognition) return;

        recognition = new SpeechRecognition();
        recognition.lang = 'ko-KR';
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.maxAlternatives = 1;

        recognition.onresult = function (event) {
            let interim = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalText += transcript + ' ';
                } else {
                    interim += transcript;
                }
            }
            interimText = interim;
            if (_onTextUpdate) _onTextUpdate(finalText, interimText);
        };

        recognition.onerror = function (event) {
            if (event.error === 'no-speech') return; // 무음 - 무시
            if (event.error === 'aborted') return;   // 수동 중지 - 무시
            if (_onError) _onError(event.error);
        };

        recognition.onend = function () {
            // 녹음 중이고 강제 종료가 아니면 자동 재시작
            if (isRunning && shouldRestart) {
                try { recognition.start(); } catch (e) { /* 이미 시작됨 */ }
            }
        };
    }

    // 공개 API
    return {
        isSupported: isSupported,

        onTextUpdate: function (callback) { _onTextUpdate = callback; },
        onComplete: function (callback) { _onComplete = callback; },
        onError: function (callback) { _onError = callback; },

        // 녹음 시작
        start: function () {
            if (isRunning) return;

            if (!isSupported()) {
                alert('이 기능은 Chrome 또는 Edge 브라우저에서 사용해주세요.');
                return;
            }

            finalText = '';
            interimText = '';
            chunks = [];
            startTime = Date.now();
            isRunning = true;
            shouldRestart = true;

            // MediaRecorder 시작
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(function (s) {
                    stream = s;

                    const mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
                        ? 'audio/webm;codecs=opus'
                        : 'audio/webm';
                    mediaRecorder = new MediaRecorder(stream, { mimeType: mimeType });

                    mediaRecorder.ondataavailable = function (e) {
                        if (e.data && e.data.size > 0) chunks.push(e.data);
                    };
                    mediaRecorder.start(1000); // 1초마다 데이터 수집

                    // STT 시작
                    initRecognition();
                    try { recognition.start(); } catch (e) { /* 이미 시작됨 */ }

                    // 타이머 시작
                    timerInterval = setInterval(function () {
                        const elapsed = Math.floor((Date.now() - startTime) / 1000);
                        const m = String(Math.floor(elapsed / 60)).padStart(2, '0');
                        const s = String(elapsed % 60).padStart(2, '0');
                        const timerEl = document.getElementById('stt-timer');
                        if (timerEl) timerEl.textContent = m + ':' + s;
                    }, 1000);
                })
                .catch(function (err) {
                    isRunning = false;
                    if (_onError) _onError('마이크 접근 오류: ' + err.message);
                });
        },

        // 일시정지
        pause: function () {
            if (!isRunning) return;
            shouldRestart = false;
            if (recognition) try { recognition.stop(); } catch (e) {}
            if (mediaRecorder && mediaRecorder.state === 'recording') mediaRecorder.pause();
        },

        // 재개
        resume: function () {
            if (!isRunning) return;
            shouldRestart = true;
            if (recognition) try { recognition.start(); } catch (e) {}
            if (mediaRecorder && mediaRecorder.state === 'paused') mediaRecorder.resume();
        },

        // 녹음 중지 및 결과 반환
        stop: function () {
            if (!isRunning) return;
            isRunning = false;
            shouldRestart = false;

            clearInterval(timerInterval);
            timerInterval = null;

            // STT 종료
            if (recognition) try { recognition.stop(); } catch (e) {}

            // MediaRecorder 종료 후 콜백
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.onstop = function () {
                    const audioBlob = new Blob(chunks, { type: 'audio/webm' });
                    const duration  = Math.floor((Date.now() - startTime) / 1000);

                    // 스트림 종료
                    if (stream) stream.getTracks().forEach(function (t) { t.stop(); });

                    if (_onComplete) _onComplete({
                        text: finalText.trim(),
                        audioBlob: audioBlob,
                        duration: duration,
                    });
                };
                mediaRecorder.stop();
            } else {
                if (stream) stream.getTracks().forEach(function (t) { t.stop(); });
                const duration = startTime ? Math.floor((Date.now() - startTime) / 1000) : 0;
                if (_onComplete) _onComplete({ text: finalText.trim(), audioBlob: null, duration: duration });
            }
        },

        // 현재 텍스트 반환
        getText: function () { return finalText; },

        getDuration: function () {
            if (!startTime) return 0;
            return Math.floor((Date.now() - startTime) / 1000);
        },

        isRunning: function () { return isRunning; },
    };
})();
