-- 插件管理系统数据库迁移
-- 执行时间: 2025-03-10

-- ============================================
-- 1. 扩展 admin_rules 表
-- ============================================

-- 增加菜单类型字段
ALTER TABLE `admin_rules` 
ADD COLUMN IF NOT EXISTS `type` VARCHAR(20) DEFAULT 'system' COMMENT '菜单类型: system=系统, plugin=插件' AFTER `status`,
ADD COLUMN IF NOT EXISTS `plugin` VARCHAR(50) DEFAULT NULL COMMENT '插件标识，系统菜单为空' AFTER `type`,
ADD COLUMN IF NOT EXISTS `is_system` TINYINT(1) DEFAULT '1' COMMENT '1=系统内置（不可删），0=插件添加' AFTER `plugin`;

-- 添加索引
ALTER TABLE `admin_rules` 
ADD INDEX IF NOT EXISTS `idx_plugin` (`plugin`),
ADD INDEX IF NOT EXISTS `idx_type` (`type`);

-- ============================================
-- 2. 创建插件管理表
-- ============================================

CREATE TABLE IF NOT EXISTS `plugins` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `identifier` VARCHAR(50) NOT NULL COMMENT '插件唯一标识（目录名）',
    `name` VARCHAR(100) NOT NULL COMMENT '插件名称',
    `version` VARCHAR(20) DEFAULT '1.0.0' COMMENT '版本号',
    `author` VARCHAR(50) DEFAULT '' COMMENT '作者',
    `description` TEXT COMMENT '插件描述',
    `icon` VARCHAR(50) DEFAULT 'mdi mdi-puzzle' COMMENT '菜单图标',
    `status` TINYINT(1) DEFAULT '0' COMMENT '0=禁用, 1=启用',
    `is_installed` TINYINT(1) DEFAULT '0' COMMENT '是否已安装',
    `install_time` DATETIME DEFAULT NULL COMMENT '安装时间',
    `config` JSON DEFAULT NULL COMMENT '插件配置',
    `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_identifier` (`identifier`),
    KEY `idx_status` (`status`),
    KEY `idx_installed` (`is_installed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='插件管理表';

-- ============================================
-- 3. 添加插件管理菜单到后台
-- ============================================

-- 先获取当前最大 sort 值
SET @max_sort = (SELECT MAX(sort) FROM admin_rules WHERE pid = 0);
SET @new_sort = IFNULL(@max_sort, 0) + 1;

-- 插入插件管理一级菜单
INSERT INTO `admin_rules` (`pid`, `title`, `href`, `icon`, `sort`, `is_menu`, `status`, `type`, `plugin`, `is_system`)
SELECT 0, '插件管理', '/admin/plugin/index', 'mdi mdi-puzzle', @new_sort, 1, 1, 'system', NULL, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM admin_rules WHERE href = '/admin/plugin/index');

-- 获取插件管理菜单ID
SET @plugin_menu_id = (SELECT id FROM admin_rules WHERE href = '/admin/plugin/index');

-- 插入子菜单：插件列表
INSERT INTO `admin_rules` (`pid`, `title`, `href`, `icon`, `sort`, `is_menu`, `status`, `type`, `plugin`, `is_system`)
SELECT @plugin_menu_id, '插件列表', '/admin/plugin/index', 'mdi mdi-view-list', 1, 1, 1, 'system', NULL, 1
FROM DUAL
WHERE @plugin_menu_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM admin_rules WHERE href = '/admin/plugin/index' AND pid = @plugin_menu_id);

-- 插入子菜单：安装插件
INSERT INTO `admin_rules` (`pid`, `title`, `href`, `icon`, `sort`, `is_menu`, `status`, `type`, `plugin`, `is_system`)
SELECT @plugin_menu_id, '安装插件', '/admin/plugin/install', 'mdi mdi-upload', 2, 1, 1, 'system', NULL, 1
FROM DUAL
WHERE @plugin_menu_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM admin_rules WHERE href = '/admin/plugin/install' AND pid = @plugin_menu_id);

-- 插入权限节点（不显示在菜单中）
INSERT INTO `admin_rules` (`pid`, `title`, `href`, `icon`, `sort`, `is_menu`, `status`, `type`, `plugin`, `is_system`)
SELECT @plugin_menu_id, '插件上传', '/admin/plugin/upload', '', 3, 0, 1, 'system', NULL, 1
FROM DUAL
WHERE @plugin_menu_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM admin_rules WHERE href = '/admin/plugin/upload');

INSERT INTO `admin_rules` (`pid`, `title`, `href`, `icon`, `sort`, `is_menu`, `status`, `type`, `plugin`, `is_system`)
SELECT @plugin_menu_id, '插件启用/停用', '/admin/plugin/toggle', '', 4, 0, 1, 'system', NULL, 1
FROM DUAL
WHERE @plugin_menu_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM admin_rules WHERE href = '/admin/plugin/toggle');

INSERT INTO `admin_rules` (`pid`, `title`, `href`, `icon`, `sort`, `is_menu`, `status`, `type`, `plugin`, `is_system`)
SELECT @plugin_menu_id, '插件卸载', '/admin/plugin/uninstall', '', 5, 0, 1, 'system', NULL, 1
FROM DUAL
WHERE @plugin_menu_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM admin_rules WHERE href = '/admin/plugin/uninstall');
