-- ============================================================
-- v2 코웨이 영업관리 시스템 - 1차 기능 추가 마이그레이션
-- ============================================================

-- 1. tndnjstl_member: 직급/지점/팀 컬럼 추가
ALTER TABLE tndnjstl_member
    ADD COLUMN IF NOT EXISTS `position`    ENUM('staff','team_leader','director','branch_manager') NOT NULL DEFAULT 'staff' COMMENT '직급: 영업사원/팀장/국장/지점장' AFTER `role`,
    ADD COLUMN IF NOT EXISTS `branch_name` VARCHAR(100) DEFAULT NULL COMMENT '지점명' AFTER `position`,
    ADD COLUMN IF NOT EXISTS `team_name`   VARCHAR(100) DEFAULT NULL COMMENT '팀명'   AFTER `branch_name`;

-- 2. tndnjstl_order_item: 프로모션 연결 컬럼 추가
ALTER TABLE tndnjstl_order_item
    ADD COLUMN IF NOT EXISTS `promo_uid` INT DEFAULT NULL COMMENT '적용 프로모션 UID' AFTER `model_color`;

-- 3. 프로모션 관리 테이블
CREATE TABLE IF NOT EXISTS `tndnjstl_promotion` (
    `uid`             INT          NOT NULL AUTO_INCREMENT,
    `promo_name`      VARCHAR(200) NOT NULL COMMENT '프로모션명',
    `target_category` VARCHAR(200) DEFAULT  NULL COMMENT '적용 카테고리 (NULL=전체)',
    `discount_type`   ENUM('amount','percent') NOT NULL DEFAULT 'amount' COMMENT '할인 유형',
    `discount_value`  INT          NOT NULL DEFAULT 0 COMMENT '할인금액 또는 할인율(%)',
    `base_fee`        INT          NOT NULL DEFAULT 200000 COMMENT '기본 영업비(원)',
    `special_fee`     INT          NOT NULL DEFAULT 0 COMMENT '특별 영업비(원)',
    `start_date`      DATE         NOT NULL COMMENT '시작일',
    `end_date`        DATE         NOT NULL COMMENT '종료일',
    `description`     TEXT         DEFAULT NULL COMMENT '설명/조건',
    `image_path`      VARCHAR(300) DEFAULT NULL COMMENT '첨부 이미지 경로',
    `is_active`       TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '활성여부',
    `register_date`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `register_id`     VARCHAR(50)  NOT NULL DEFAULT '',
    PRIMARY KEY (`uid`),
    KEY `idx_active_date` (`is_active`, `start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='프로모션 관리';

-- 4. 수수료 기록 테이블
CREATE TABLE IF NOT EXISTS `tndnjstl_commission` (
    `uid`               INT         NOT NULL AUTO_INCREMENT,
    `order_uid`         INT         NOT NULL,
    `order_item_uid`    INT         NOT NULL DEFAULT 0,
    `member_id`         VARCHAR(50) NOT NULL COMMENT '수혜 영업자',
    `commission_type`   ENUM('base','special','team','director','branch') NOT NULL COMMENT '수수료 유형',
    `gross_amount`      INT         NOT NULL DEFAULT 0 COMMENT '총 수수료',
    `hold_amount`       INT         NOT NULL DEFAULT 0 COMMENT '해지방어비 50% 적립',
    `net_amount`        INT         NOT NULL DEFAULT 0 COMMENT '즉시 수령 예정액',
    `hold_release_date` DATE        DEFAULT NULL COMMENT '해지방어비 지급 예정일 (계약+1년)',
    `hold_released`     TINYINT(1)  NOT NULL DEFAULT 0 COMMENT '해지방어비 지급 여부',
    `promo_uid`         INT         DEFAULT NULL,
    `register_date`     DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`uid`),
    KEY `idx_member`       (`member_id`),
    KEY `idx_order`        (`order_uid`),
    KEY `idx_hold_release` (`hold_release_date`, `hold_released`),
    KEY `idx_reg_date`     (`register_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='영업 수수료 기록';

-- 5. 가격 변동 이력 테이블
CREATE TABLE IF NOT EXISTS `tndnjstl_price_history` (
    `uid`                INT          NOT NULL AUTO_INCREMENT,
    `model_uid`          VARCHAR(100) NOT NULL,
    `model_name`         VARCHAR(200) NOT NULL DEFAULT '',
    `rent_price_before`  INT          DEFAULT NULL,
    `rent_price_after`   INT          NOT NULL DEFAULT 0,
    `normal_price_before`INT          DEFAULT NULL,
    `normal_price_after` INT          NOT NULL DEFAULT 0,
    `setup_price_before` INT          DEFAULT NULL,
    `setup_price_after`  INT          NOT NULL DEFAULT 0,
    `change_type`        ENUM('auto','manual') NOT NULL DEFAULT 'auto' COMMENT '변경 유형',
    `change_reason`      VARCHAR(200) DEFAULT NULL,
    `changed_date`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`uid`),
    KEY `idx_model`        (`model_uid`),
    KEY `idx_changed_date` (`changed_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='제품 가격 변동 이력';
