<?php
/**
 * Migration v4 - 상담 녹음/STT + 위치 추적 테이블 생성
 * 실행 후 이 파일은 반드시 삭제하세요.
 */
require_once dirname(__DIR__) . '/app/helpers/common_db.php';

$sqls = [];

// 1. 기존 consultation 테이블 재구성
$sqls[] = "DROP TABLE IF EXISTS tndnjstl_consultation";
$sqls[] = "
CREATE TABLE tndnjstl_consultation (
    uid            INT          NOT NULL AUTO_INCREMENT,
    member_id      VARCHAR(50)  NOT NULL COMMENT '영업자 ID',
    customer_uid   INT          DEFAULT NULL,
    order_uid      INT          DEFAULT NULL,
    title          VARCHAR(200) NOT NULL,
    consult_type   VARCHAR(20)  NOT NULL COMMENT 'visit/phone/online',
    consult_date   DATETIME     NOT NULL,
    stt_text       LONGTEXT     DEFAULT NULL,
    stt_summary    TEXT         DEFAULT NULL,
    audio_file     VARCHAR(300) DEFAULT NULL,
    audio_duration INT          DEFAULT NULL,
    status         VARCHAR(10)  NOT NULL DEFAULT 'completed',
    register_date  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    update_date    DATETIME     DEFAULT NULL,
    PRIMARY KEY (uid),
    KEY idx_consultation_member   (member_id, consult_date),
    KEY idx_consultation_customer (customer_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='상담 녹음/STT 기록'
";

// 2. 위치 로그 테이블
$sqls[] = "
CREATE TABLE IF NOT EXISTS tndnjstl_location_log (
    uid        BIGINT        NOT NULL AUTO_INCREMENT,
    member_id  VARCHAR(50)   NOT NULL,
    latitude   DECIMAL(10,7) NOT NULL,
    longitude  DECIMAL(10,7) NOT NULL,
    accuracy   FLOAT         DEFAULT NULL,
    speed      FLOAT         DEFAULT NULL,
    logged_at  DATETIME      NOT NULL,
    log_date   DATE          NOT NULL,
    PRIMARY KEY (uid),
    KEY idx_location_log_member_date (member_id, log_date),
    KEY idx_location_log_date        (log_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='영업자 위치 추적 로그'
";

// 3. 위치 세션 테이블
$sqls[] = "
CREATE TABLE IF NOT EXISTS tndnjstl_location_session (
    uid            INT         NOT NULL AUTO_INCREMENT,
    member_id      VARCHAR(50) NOT NULL,
    start_time     DATETIME    NOT NULL,
    end_time       DATETIME    DEFAULT NULL,
    total_distance FLOAT       DEFAULT NULL,
    point_count    INT         DEFAULT NULL,
    status         VARCHAR(10) NOT NULL DEFAULT 'active',
    log_date       DATE        NOT NULL,
    PRIMARY KEY (uid),
    KEY idx_location_session_member (member_id, log_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='영업자 위치 추적 세션'
";

$results = [];
foreach ($sqls as $i => $sql) {
    $sql_preview = trim(substr($sql, 0, 60)) . '...';
    if ($db_local->query($sql)) {
        $results[] = "[OK] {$sql_preview}";
    } else {
        $results[] = "[FAIL] {$sql_preview} → " . $db_local->error;
    }
}

echo '<pre style="font-family:monospace;font-size:14px;padding:20px;">';
echo "=== Migration v4 실행 결과 ===\n\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\n완료. 이 파일을 서버에서 삭제해주세요.";
echo '</pre>';
