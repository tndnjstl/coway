-- 가망고객 상담내역 테이블
CREATE TABLE IF NOT EXISTS tndnjstl_consultation (
    uid          INT          NOT NULL AUTO_INCREMENT,
    order_uid    INT          NOT NULL,
    content      TEXT         NOT NULL,
    member_id    VARCHAR(100) NOT NULL DEFAULT '',
    consult_date DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (uid),
    KEY idx_order_uid    (order_uid),
    KEY idx_consult_date (consult_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='상담내역';
