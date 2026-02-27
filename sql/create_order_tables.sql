-- 주문 마스터 테이블
CREATE TABLE IF NOT EXISTS `tndnjstl_order` (
    `uid`           INT          NOT NULL AUTO_INCREMENT,
    `customer_type` CHAR(1)      NOT NULL COMMENT 'P:개인 B:개인사업자 C:법인사업자',
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_phone`VARCHAR(20)  NOT NULL,
    `member_id`     VARCHAR(50)  NOT NULL COMMENT '담당 영업자 ID',
    `memo`          TEXT         DEFAULT NULL,
    `register_date` DATETIME     NOT NULL DEFAULT NOW(),
    PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='주문 마스터';

-- 주문 상품 상세 테이블
CREATE TABLE IF NOT EXISTS `tndnjstl_order_item` (
    `uid`              INT          NOT NULL AUTO_INCREMENT,
    `order_uid`        INT          NOT NULL,
    `model_uid`        VARCHAR(100) NOT NULL,
    `model_name`       VARCHAR(200) NOT NULL,
    `model_no`         VARCHAR(100) NOT NULL DEFAULT '',
    `model_color`      VARCHAR(100) NOT NULL DEFAULT '',
    `category`         VARCHAR(100) NOT NULL DEFAULT '',
    `payment_type`     CHAR(4)      NOT NULL COMMENT 'rent:렌탈 buy:일시불',
    `visit_cycle`      TINYINT      DEFAULT NULL COMMENT '방문주기(개월)',
    `duty_year`        TINYINT      DEFAULT NULL COMMENT '의무사용기간(년)',
    `promo_a141`       TINYINT(1)   NOT NULL DEFAULT 0,
    `promo_a142`       TINYINT(1)   NOT NULL DEFAULT 0,
    `promo_a143`       TINYINT(1)   NOT NULL DEFAULT 0,
    `promo_a144`       TINYINT(1)   NOT NULL DEFAULT 0,
    `base_setup_price` INT          NOT NULL DEFAULT 0,
    `base_rent_price`  INT          NOT NULL DEFAULT 0,
    `final_setup_price`INT          NOT NULL DEFAULT 0,
    `final_rent_price` INT          NOT NULL DEFAULT 0,
    `normal_price`     INT          NOT NULL DEFAULT 0,
    `total_pay`        INT          NOT NULL DEFAULT 0,
    `register_date`    DATETIME     NOT NULL DEFAULT NOW(),
    PRIMARY KEY (`uid`),
    KEY `idx_order_uid` (`order_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='주문 상품 상세';
