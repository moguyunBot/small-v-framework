<?php
namespace plugin\blog\app\listener;

/**
 * Blog 插件事件监听器示例
 *
 * 在 config/event.php 中注册：
 * 'admin.config.saved' => [\plugin\blog\app\listener\BlogListener::class],
 */
class BlogListener
{
    /**
     * 监听配置保存事件
     * 当 blog 配置保存后，可在此做缓存清理等操作
     */
    public function onConfigSaved(string $groupKey): void
    {
        // 只处理 blog 相关配置
        if (!str_starts_with($groupKey, 'blog')) {
            return;
        }
        // 可在此清理 blog 相关缓存
        // Cache::delete('blog_config');
    }

    /**
     * 监听管理员登录成功事件
     */
    public function onAdminLogin(array $admin): void
    {
        // 例：记录特定管理员的登录通知
        // Notification::send($admin);
    }
}
