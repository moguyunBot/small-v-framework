<?php
/**
 * 事件监听配置
 * 格式：'事件名' => [监听器类::class, ...]
 *
 * 内置事件列表：
 *   admin.login.success   管理员登录成功  参数: array $admin
 *   admin.login.fail      管理员登录失败  参数: array ['username', 'reason']
 *   admin.config.saved    系统配置保存后  参数: string $groupKey
 *   plugin.installed      插件安装后      参数: string $identifier
 *   plugin.uninstalled    插件卸载后      参数: string $identifier
 *   plugin.enabled        插件启用后      参数: string $identifier
 *   plugin.disabled       插件停用后      参数: string $identifier
 */
return [
    // 示例：'admin.login.success' => [\app\listener\AdminLoginListener::class],
];
