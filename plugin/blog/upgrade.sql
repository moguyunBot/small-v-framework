-- blog 插件升级 SQL 示例
-- 在此添加新增字段、新增表等增量操作
-- 已存在的字段/表请用 IF NOT EXISTS 或 IGNORE 防止报错

-- 示例：新增字段
-- ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图' AFTER `content`;
