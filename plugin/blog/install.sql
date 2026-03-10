CREATE TABLE IF NOT EXISTS `blog_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '分类名称',
  `slug` varchar(100) NOT NULL COMMENT '别名',
  `description` text COMMENT '分类描述',
  `sort` int(11) NOT NULL DEFAULT 0,
  `post_count` int(11) NOT NULL DEFAULT 0,
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='博客分类';

CREATE TABLE IF NOT EXISTS `blog_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '标签名称',
  `slug` varchar(100) NOT NULL COMMENT '别名',
  `post_count` int(11) NOT NULL DEFAULT 0,
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='博客标签';

CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '文章标题',
  `slug` varchar(255) NOT NULL COMMENT '别名',
  `cover` varchar(255) DEFAULT '' COMMENT '封面图',
  `summary` text COMMENT '摘要',
  `content` longtext COMMENT '正文',
  `category_id` int(11) NOT NULL DEFAULT 0 COMMENT '分类ID',
  `author` varchar(100) DEFAULT '' COMMENT '作者',
  `view_count` int(11) NOT NULL DEFAULT 0 COMMENT '浏览量',
  `comment_count` int(11) NOT NULL DEFAULT 0 COMMENT '评论数',
  `is_top` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否置顶',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0草稿 1发布',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='博客文章';

CREATE TABLE IF NOT EXISTS `blog_post_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章标签关联';

CREATE TABLE IF NOT EXISTS `blog_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL COMMENT '文章ID',
  `parent_id` int(11) NOT NULL DEFAULT 0 COMMENT '父评论ID',
  `nickname` varchar(100) NOT NULL COMMENT '昵称',
  `email` varchar(150) DEFAULT '' COMMENT '邮箱',
  `website` varchar(255) DEFAULT '' COMMENT '网站',
  `content` text NOT NULL COMMENT '内容',
  `ip` varchar(50) DEFAULT '' COMMENT 'IP地址',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0待审核 1通过 2拒绝',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='博客评论';

INSERT IGNORE INTO `blog_categories` (`name`, `slug`, `description`, `sort`, `create_time`, `update_time`) VALUES
('未分类', 'uncategorized', '默认分类', 0, NOW(), NOW()),
('技术', 'tech', '技术相关文章', 1, NOW(), NOW()),
('生活', 'life', '生活随笔', 2, NOW(), NOW()),
('随笔', 'essay', '随便写写', 3, NOW(), NOW());

INSERT IGNORE INTO `blog_tags` (`name`, `slug`, `post_count`, `create_time`, `update_time`) VALUES
('PHP', 'php', 2, NOW(), NOW()),
('JavaScript', 'javascript', 1, NOW(), NOW()),
('Linux', 'linux', 1, NOW(), NOW()),
('随笔', 'essay-tag', 1, NOW(), NOW()),
('开源', 'opensource', 0, NOW(), NOW());

INSERT IGNORE INTO `blog_posts` (`title`, `slug`, `cover`, `summary`, `content`, `category_id`, `author`, `view_count`, `comment_count`, `is_top`, `status`, `create_time`, `update_time`) VALUES
('欢迎使用博客系统', 'welcome', '', '这是博客系统的第一篇文章，欢迎你的到来。', '<p>欢迎使用本博客系统！</p><p>这是一篇演示文章，你可以在后台管理中编辑或删除它，然后开始写下属于自己的内容。</p><p>祝创作愉快！</p>', 1, 'Admin', 128, 0, 1, 1, NOW(), NOW()),
('PHP 8.x 新特性一览', 'php8-features', '', 'PHP 8.x 带来了许多令人兴奋的新特性，本文带你快速了解。', '<h2>命名参数</h2><p>PHP 8.0 引入了命名参数，让函数调用更清晰。</p><h2>枚举类型</h2><p>PHP 8.1 正式引入枚举（Enum），告别魔法常量。</p><h2>纤程 Fiber</h2><p>PHP 8.1 引入 Fiber，为异步编程提供了原生支持。</p>', 2, 'Admin', 256, 2, 0, 1, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
('用 Linux 搭建高性能 Web 服务器', 'linux-web-server', '', '本文介绍如何在 Linux 上从零搭建一套高性能的 Web 服务器环境。', '<h2>安装 Nginx</h2><p>apt update && apt install nginx -y</p><h2>安装 PHP-FPM</h2><p>apt install php8.2-fpm -y</p><h2>配置虚拟主机</h2><p>编辑 nginx 配置文件，设置好 root 和 fastcgi_pass 即可。</p>', 2, 'Admin', 512, 3, 0, 1, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY)),
('周末随笔：关于慢下来这件事', 'slow-down', '', '最近总是很忙，今天难得有个闲暇的下午，坐下来写写字。', '<p>最近总是在赶进度，感觉每天都在高速运转。</p><p>今天难得有个完整的下午，泡了杯茶，坐在窗边，突然觉得，慢下来也挺好的。</p><p>代码可以明天再写，但这一刻的宁静，错过了就没了。</p>', 3, 'Admin', 89, 1, 0, 1, DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 14 DAY)),
('JavaScript 异步编程进化史', 'js-async-evolution', '', '从回调地狱到 Promise，再到 async/await，JavaScript 异步编程走过了漫长的路。', '<h2>回调函数时代</h2><p>早期 JS 异步全靠回调，嵌套多了就是回调地狱。</p><h2>Promise 登场</h2><p>Promise 让链式调用成为可能。</p><h2>async/await</h2><p>代码终于可以像同步一样写了。</p>', 2, 'Admin', 340, 3, 0, 1, DATE_SUB(NOW(), INTERVAL 21 DAY), DATE_SUB(NOW(), INTERVAL 21 DAY));

INSERT IGNORE INTO `blog_post_tags` (`post_id`, `tag_id`)
SELECT p.id, t.id FROM `blog_posts` p, `blog_tags` t WHERE p.slug='php8-features' AND t.slug='php'
UNION ALL
SELECT p.id, t.id FROM `blog_posts` p, `blog_tags` t WHERE p.slug='linux-web-server' AND t.slug='php'
UNION ALL
SELECT p.id, t.id FROM `blog_posts` p, `blog_tags` t WHERE p.slug='linux-web-server' AND t.slug='linux'
UNION ALL
SELECT p.id, t.id FROM `blog_posts` p, `blog_tags` t WHERE p.slug='slow-down' AND t.slug='essay-tag'
UNION ALL
SELECT p.id, t.id FROM `blog_posts` p, `blog_tags` t WHERE p.slug='js-async-evolution' AND t.slug='javascript';

INSERT IGNORE INTO `blog_comments` (`post_id`, `parent_id`, `nickname`, `email`, `content`, `ip`, `status`, `create_time`, `update_time`) VALUES
((SELECT id FROM `blog_posts` WHERE slug='php8-features'), 0, '张三', 'zhangsan@example.com', 'PHP 8 的枚举终于来了！', '127.0.0.1', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
((SELECT id FROM `blog_posts` WHERE slug='php8-features'), 0, '李四', 'lisi@example.com', 'Fiber 那块能再详细讲讲吗？', '127.0.0.1', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
((SELECT id FROM `blog_posts` WHERE slug='linux-web-server'), 0, '王五', 'wangwu@example.com', '跟着做成功了，感谢分享！', '127.0.0.1', 1, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
((SELECT id FROM `blog_posts` WHERE slug='linux-web-server'), 0, '赵六', 'zhaoliu@example.com', '建议用 8.2 还是 8.3？', '127.0.0.1', 0, DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY)),
((SELECT id FROM `blog_posts` WHERE slug='linux-web-server'), 0, '管理员', 'admin@example.com', '两个版本都可以，8.3 性能略好。', '127.0.0.1', 1, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
((SELECT id FROM `blog_posts` WHERE slug='slow-down'), 0, '路人甲', 'luren@example.com', '说得对，要学会给自己留白。', '127.0.0.1', 1, DATE_SUB(NOW(), INTERVAL 13 DAY), DATE_SUB(NOW(), INTERVAL 13 DAY)),
((SELECT id FROM `blog_posts` WHERE slug='js-async-evolution'), 0, '前端小白', 'fe@example.com', '终于搞懂 async/await 了！', '127.0.0.1', 1, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY)),
((SELECT id FROM `blog_posts` WHERE slug='js-async-evolution'), 0, '老司机', 'pro@example.com', '可以补充 Promise.all 的用法。', '127.0.0.1', 1, DATE_SUB(NOW(), INTERVAL 19 DAY), DATE_SUB(NOW(), INTERVAL 19 DAY)),
((SELECT id FROM `blog_posts` WHERE slug='js-async-evolution'), 0, '新手上路', 'newbie@example.com', '这篇文章救了我！', '127.0.0.1', 0, DATE_SUB(NOW(), INTERVAL 18 DAY), DATE_SUB(NOW(), INTERVAL 18 DAY));

UPDATE `blog_categories` SET `post_count` = 1 WHERE `slug` = 'uncategorized';
UPDATE `blog_categories` SET `post_count` = 3 WHERE `slug` = 'tech';
UPDATE `blog_categories` SET `post_count` = 1 WHERE `slug` = 'life';
UPDATE `blog_tags` SET `post_count` = 2 WHERE `slug` = 'php';
UPDATE `blog_tags` SET `post_count` = 1 WHERE `slug` = 'javascript';
UPDATE `blog_tags` SET `post_count` = 1 WHERE `slug` = 'linux';
UPDATE `blog_tags` SET `post_count` = 1 WHERE `slug` = 'essay-tag';
UPDATE `blog_posts` SET `comment_count` = 2 WHERE `slug` = 'php8-features';
UPDATE `blog_posts` SET `comment_count` = 3 WHERE `slug` = 'linux-web-server';
UPDATE `blog_posts` SET `comment_count` = 1 WHERE `slug` = 'slow-down';
UPDATE `blog_posts` SET `comment_count` = 3 WHERE `slug` = 'js-async-evolution';
