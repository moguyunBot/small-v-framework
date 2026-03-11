CREATE TABLE IF NOT EXISTS `pay_orders` (
  `id`           int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_no`     varchar(64)   NOT NULL DEFAULT '' COMMENT '订单号',
  `out_trade_no` varchar(64)   NOT NULL DEFAULT '' COMMENT '第三方交易号',
  `user_id`      int(11)       NOT NULL DEFAULT '0' COMMENT '用户ID',
  `amount`       decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
  `subject`      varchar(255)  NOT NULL DEFAULT '' COMMENT '订单标题',
  `pay_type`     varchar(20)   NOT NULL DEFAULT '' COMMENT '支付方式 alipay/wechat',
  `status`       tinyint(1)    NOT NULL DEFAULT '0' COMMENT '状态 0待支付 1已支付 2已退款',
  `extra`        text          COMMENT '回调原始数据',
  `paid_at`      int(11)       NOT NULL DEFAULT '0' COMMENT '支付时间',
  `create_time`  int(11)       NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time`  int(11)       NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付订单表';
