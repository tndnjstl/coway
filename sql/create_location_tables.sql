-- 위치 추적 로그 테이블 (대량 데이터 대응)
CREATE TABLE IF NOT EXISTS tndnjstl_location_log (
    uid        BIGINT       NOT NULL AUTO_INCREMENT,
    member_id  VARCHAR(50)  NOT NULL COMMENT '영업자 ID',
    latitude   DECIMAL(10,7) NOT NULL COMMENT '위도',
    longitude  DECIMAL(10,7) NOT NULL COMMENT '경도',
    accuracy   FLOAT        DEFAULT NULL COMMENT 'GPS 정확도(미터)',
    speed      FLOAT        DEFAULT NULL COMMENT '이동 속도(m/s)',
    logged_at  DATETIME     NOT NULL COMMENT '기록 시점',
    log_date   DATE         NOT NULL COMMENT 'INDEX용 날짜',
    PRIMARY KEY (uid),
    KEY idx_location_log_member_date (member_id, log_date),
    KEY idx_location_log_date        (log_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='영업자 위치 추적 로그';

-- 위치 추적 세션 테이블
CREATE TABLE IF NOT EXISTS tndnjstl_location_session (
    uid            INT         NOT NULL AUTO_INCREMENT,
    member_id      VARCHAR(50) NOT NULL COMMENT '영업자 ID',
    start_time     DATETIME    NOT NULL COMMENT '추적 시작',
    end_time       DATETIME    DEFAULT NULL COMMENT '추적 종료',
    total_distance FLOAT       DEFAULT NULL COMMENT '총 이동거리(km)',
    point_count    INT         DEFAULT NULL COMMENT '기록 포인트 수',
    status         VARCHAR(10) NOT NULL DEFAULT 'active' COMMENT 'active/stopped',
    log_date       DATE        NOT NULL COMMENT 'INDEX용 날짜',
    PRIMARY KEY (uid),
    KEY idx_location_session_member (member_id, log_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='영업자 위치 추적 세션';
