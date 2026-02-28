-- 일정 테이블 생성
CREATE TABLE IF NOT EXISTS tndnjstl_schedule (
	uid           INT          AUTO_INCREMENT PRIMARY KEY,
	member_id     VARCHAR(50)  NOT NULL COMMENT '담당 영업자',
	customer_uid  INT          DEFAULT NULL COMMENT '연결 고객 UID',
	schedule_type VARCHAR(20)  NOT NULL COMMENT 'visit:방문 as:AS install:설치 consult:상담',
	title         VARCHAR(200) NOT NULL,
	schedule_date DATE         NOT NULL,
	schedule_time TIME         DEFAULT NULL,
	memo          TEXT         DEFAULT NULL,
	status        CHAR(10)     NOT NULL DEFAULT 'pending' COMMENT 'pending:예정 done:완료 cancel:취소',
	register_date DATETIME     NOT NULL DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='일정 관리';
