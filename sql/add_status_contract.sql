-- 1. tndnjstl_order 테이블에 status 컬럼 추가
ALTER TABLE tndnjstl_order
    ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'prospect'
    COMMENT '가망고객:prospect / 계약완료:contracted / 설치완료:installed'
    AFTER memo;

-- 2. 계약 관리 테이블 생성
CREATE TABLE IF NOT EXISTS tndnjstl_contract (
    uid            INT          NOT NULL AUTO_INCREMENT,
    order_uid      INT          NOT NULL,
    contract_start DATE         NOT NULL,
    contract_end   DATE         NOT NULL,
    duty_year      INT          NOT NULL DEFAULT 0,
    register_date  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (uid),
    KEY idx_order_uid    (order_uid),
    KEY idx_contract_end (contract_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='계약 관리';
