CREATE TABLE IF NOT EXISTS `user_members` (
  `id`          int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nickname`    varchar(64)  NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar`      varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `email`       varchar(128) NOT NULL DEFAULT '' COMMENT '邮箱',
  `mobile`      varchar(20)  NOT NULL DEFAULT '' COMMENT '手机号',
  `password`    varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `balance`     decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '余额',
  `status`      tinyint(1)   NOT NULL DEFAULT '1' COMMENT '状态 1正常 0禁用',
  `create_time` int(11)      NOT NULL DEFAULT '0' COMMENT '注册时间',
  `update_time` int(11)      NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email`  (`email`),
  UNIQUE KEY `uk_mobile` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';
