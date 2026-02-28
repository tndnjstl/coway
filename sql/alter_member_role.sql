-- 1. role 컬럼 추가
ALTER TABLE tndnjstl_member
	ADD COLUMN `role` ENUM('staff', 'manager', 'admin') NOT NULL DEFAULT 'staff' COMMENT '권한: staff=일반, manager=관리자, admin=시스템관리자'
	AFTER is_admin;

-- 2. 기존 is_admin = 1 → role = 'admin' 동기화
UPDATE tndnjstl_member SET `role` = 'admin' WHERE is_admin = 1;
