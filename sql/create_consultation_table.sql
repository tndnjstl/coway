-- 상담 녹음/STT 기록 테이블 (기존 테이블 재구성)
-- 기존 add_consultation.sql의 단순 버전을 확장하여 STT/녹음 기능 지원

-- 기존 테이블 삭제 후 재생성 (데이터 없는 초기 상태 가정)
DROP TABLE IF EXISTS tndnjstl_consultation;

CREATE TABLE tndnjstl_consultation (
    uid            INT          NOT NULL AUTO_INCREMENT,
    member_id      VARCHAR(50)  NOT NULL COMMENT '영업자 ID',
    customer_uid   INT          DEFAULT NULL COMMENT '고객 UID (FK)',
    order_uid      INT          DEFAULT NULL COMMENT '주문 UID (FK)',
    title          VARCHAR(200) NOT NULL COMMENT '상담 제목',
    consult_type   VARCHAR(20)  NOT NULL COMMENT 'visit:방문 phone:전화 online:화상',
    consult_date   DATETIME     NOT NULL COMMENT '상담 일시',
    stt_text       LONGTEXT     DEFAULT NULL COMMENT 'STT 변환 텍스트 전문',
    stt_summary    TEXT         DEFAULT NULL COMMENT '상담 요약',
    audio_file     VARCHAR(300) DEFAULT NULL COMMENT '녹음파일 경로',
    audio_duration INT          DEFAULT NULL COMMENT '녹음시간(초)',
    status         VARCHAR(10)  NOT NULL DEFAULT 'completed' COMMENT 'recording/completed/archived',
    register_date  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    update_date    DATETIME     DEFAULT NULL,
    PRIMARY KEY (uid),
    KEY idx_consultation_member   (member_id, consult_date),
    KEY idx_consultation_customer (customer_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='상담 녹음/STT 기록';
