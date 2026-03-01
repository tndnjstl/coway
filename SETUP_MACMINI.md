# 맥미니 개발환경 셋업 지시서
# 아래 내용을 텔레그램으로 맥미니 Claude Code에 그대로 전송

---

다음 작업을 순서대로 진행해줘. 각 단계마다 결과를 확인하면서 진행해.

## 1. 필수 도구 확인 및 설치

```
다음 명령어로 각 도구 설치 여부 확인해:
- git --version
- python3 --version
- php --version
- brew --version

없는 것만 설치:
- brew 없으면: /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
- php 없으면: brew install php
- python3는 mac에 기본 설치되어 있음
```

## 2. 프로젝트 클론

```
mkdir -p ~/DEV/php
cd ~/DEV/php
git clone https://github.com/tndnjstl/coway.git
cd coway
git log --oneline -3  # 최신 커밋 확인
```

## 3. sites.xml 생성 (FTP 배포 설정)

`~/DEV/php/coway/sites.xml` 파일을 아래 내용으로 생성해줘.
[FTP_PASSWORD_BASE64] 부분은 내가 따로 알려줄게.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<Sites>
    <Server>
        <Host>112.175.185.138</Host>
        <Port>21</Port>
        <User>tndnjstl</User>
        <Pass>[FTP_PASSWORD_BASE64]</Pass>
    </Server>
</Sites>
```

## 4. 로컬 PHP 개발 서버 설정

`~/DEV/php/coway/start_dev.sh` 파일을 생성해줘:

```bash
#!/bin/bash
# 로컬 개발 서버 실행 (포트 8080)
cd ~/DEV/php/coway/html
php -S localhost:8080 -t . ../html/index.php
```

실행 권한 부여: chmod +x ~/DEV/php/coway/start_dev.sh

## 5. DB 연결 설정 확인

`~/DEV/php/coway/app/helpers/common_db.php` 파일을 읽어서
DB 접속 정보가 올바르게 있는지 확인해줘.

## 6. Python 패키지 확인

```
python3 -c "import ftplib; import xml.etree.ElementTree; print('OK')"
```
→ OK 출력되면 추가 설치 불필요 (모두 표준 라이브러리)

## 7. FTP 배포 테스트

sites.xml 설정 후:
```
cd ~/DEV/php/coway
python3 ftp_deploy.py
```

## 8. 완료 확인

다음 사항 확인 후 결과 알려줘:
- [ ] git 클론 완료 (최신 커밋 해시)
- [ ] php 버전
- [ ] python3 버전
- [ ] sites.xml 존재 여부
- [ ] ftp_deploy.py 실행 가능 여부
- [ ] 프로젝트 경로: ~/DEV/php/coway

---
## 참고: 작업 규칙 (Claude Code용)

이 프로젝트에서 작업할 때:
- 코드 수정 후 항상: git add → git commit → python3 ftp_deploy.py
- DB 쿼리는 $db_local->real_escape_string() 필수
- 뷰 파일은 layouts/head.php, side_menu.php, footer.php 등 include
- 라우팅: /컨트롤러명/메서드명 → app/controllers/컨트롤러Controller.php
- 응답은 한국어로
