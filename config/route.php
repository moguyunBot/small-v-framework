<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Webman\Route;
use app\admin\model\Plugin;

// ============================================
// 加载启用的插件路由
// ============================================

// 获取所有已启用且已安装的插件
$enabledPlugins = Plugin::where('status', 1)
    ->where('is_installed', 1)
    ->column('identifier');

foreach ($enabledPlugins as $pluginId) {
    $routeFile = plugin_path($pluginId) . '/config/route.php';
    if (file_exists($routeFile)) {
        // 插件路由以 /plugin/{pluginId} 为前缀
        Route::group("/plugin/$pluginId", function () use ($routeFile) {
            require_once $routeFile;
        });
    }
}

// ============================================
// 系统路由（自动路由或在这里定义）
// ============================================

// 插件管理路由（系统内置）
Route::group('/admin/plugin', function () {
    Route::get('', [app\admin\controller\Plugin::class, 'index']);
    Route::get('/index', [app\admin\controller\Plugin::class, 'index']);
    Route::get('/upload', [app\admin\controller\Plugin::class, 'upload']);
    Route::post('/upload', [app\admin\controller\Plugin::class, 'upload']);
    Route::get('/install', [app\admin\controller\Plugin::class, 'install']);
    Route::post('/install', [app\admin\controller\Plugin::class, 'install']);
    Route::post('/uninstall', [app\admin\controller\Plugin::class, 'uninstall']);
    Route::post('/toggle', [app\admin\controller\Plugin::class, 'toggle']);
    Route::post('/delete', [app\admin\controller\Plugin::class, 'delete']);
    Route::get('/config', [app\admin\controller\Plugin::class, 'config']);
    Route::post('/config', [app\admin\controller\Plugin::class, 'config']);
});
