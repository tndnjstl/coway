-- 고객 테이블 생성
CREATE TABLE IF NOT EXISTS tndnjstl_customer (
	uid           INT          AUTO_INCREMENT PRIMARY KEY,
	customer_type CHAR(1)      NOT NULL COMMENT 'P:개인 B:개인사업자 C:법인사업자',
	customer_name VARCHAR(100) NOT NULL,
	customer_phone VARCHAR(20) NOT NULL,
	customer_email VARCHAR(100) DEFAULT NULL,
	address       VARCHAR(300) DEFAULT NULL,
	memo          TEXT         DEFAULT NULL,
	member_id     VARCHAR(50)  NOT NULL COMMENT '담당 영업자',
	register_date DATETIME     NOT NULL DEFAULT NOW(),
	update_date   DATETIME     DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='고객 관리';

-- 주문 테이블에 customer_uid 컬럼 추가
ALTER TABLE tndnjstl_order
	ADD COLUMN customer_uid INT DEFAULT NULL COMMENT '고객 UID (tndnjstl_customer.uid)' AFTER member_id;
