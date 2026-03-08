/**
 * LocationTracker - 위치 추적 모듈
 * Geolocation API watchPosition + 30초 간격 서버 배치 전송
 * HTTPS 환경 필수
 */
const LocationTracker = (function () {
    let watchId     = null;
    let sessionUid  = null;
    let buffer      = [];      // 위치 좌표 버퍼
    let sendTimer   = null;
    let isTracking  = false;

    const SEND_INTERVAL = 30000; // 30초

    const GEO_OPTIONS = {
        enableHighAccuracy: true,
        maximumAge: 10000,
        timeout: 15000,
    };

    // 위치 버퍼 → 서버 전송
    function flush() {
        if (buffer.length === 0 || !sessionUid) return;
        const points = buffer.slice();
        buffer = [];

        $.ajax({
            url: '/Location/logBatch',
            method: 'POST',
            data: {
                session_uid: sessionUid,
                points: JSON.stringify(points),
            },
            timeout: 10000,
        }).fail(function () {
            // 전송 실패 시 버퍼에 다시 추가
            buffer = points.concat(buffer);
        });
    }

    return {
        // 추적 시작
        start: function (onStarted, onError) {
            if (!navigator.geolocation) {
                if (onError) onError('GPS를 지원하지 않는 브라우저입니다.');
                return;
            }
            if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
                if (onError) onError('위치 추적은 HTTPS 환경에서만 사용할 수 있습니다.');
                return;
            }

            // 서버에 세션 시작 요청
            $.post('/Location/startSession', {}, function (res) {
                if (!res.success) {
                    if (onError) onError('세션 시작 실패: ' + (res.message || ''));
                    return;
                }
                sessionUid = res.session_uid;
                isTracking = true;
                buffer = [];

                // watchPosition 시작
                watchId = navigator.geolocation.watchPosition(
                    function (pos) {
                        buffer.push({
                            lat: pos.coords.latitude,
                            lng: pos.coords.longitude,
                            accuracy: pos.coords.accuracy,
                            speed: pos.coords.speed,
                            timestamp: new Date(pos.timestamp).toISOString(),
                        });
                    },
                    function (err) {
                        // GPS 오류 - 계속 추적
                        console.warn('GPS 오류:', err.message);
                    },
                    GEO_OPTIONS
                );

                // 30초마다 버퍼 전송
                sendTimer = setInterval(flush, SEND_INTERVAL);

                if (onStarted) onStarted(sessionUid);
            }, 'json').fail(function () {
                if (onError) onError('서버 연결에 실패했습니다.');
            });
        },

        // 추적 중지
        stop: function (onStopped) {
            if (!isTracking) return;
            isTracking = false;

            clearInterval(sendTimer);
            sendTimer = null;

            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }

            // 남은 버퍼 전송 후 세션 종료
            flush();

            $.post('/Location/stopSession', { session_uid: sessionUid }, function (res) {
                sessionUid = null;
                if (onStopped) onStopped(res);
            }, 'json').fail(function () {
                sessionUid = null;
                if (onStopped) onStopped({ success: false });
            });
        },

        // 현재 추적 상태 서버 확인 후 복원
        checkStatus: function (callback) {
            $.get('/Location/trackingStatus', function (res) {
                if (res.tracking && res.session_uid) {
                    // 이미 추적 중인 세션이 있으면 복원
                    isTracking = true;
                    sessionUid = res.session_uid;
                    buffer = [];

                    watchId = navigator.geolocation.watchPosition(
                        function (pos) {
                            buffer.push({
                                lat: pos.coords.latitude,
                                lng: pos.coords.longitude,
                                accuracy: pos.coords.accuracy,
                                speed: pos.coords.speed,
                                timestamp: new Date(pos.timestamp).toISOString(),
                            });
                        },
                        function () {},
                        GEO_OPTIONS
                    );
                    sendTimer = setInterval(flush, SEND_INTERVAL);
                }
                if (callback) callback(res.tracking, res.session_uid);
            }, 'json').fail(function () {
                if (callback) callback(false, null);
            });
        },

        isTracking: function () { return isTracking; },
        getSessionUid: function () { return sessionUid; },
    };
})();
