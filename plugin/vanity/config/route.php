<?php
/**
 * Vanity 插件路由配置
 */

use Webman\Route;

// 首页
Route::get('', [app\plugin\vanity\controller\Index::class, 'index']);
Route::get('/index', [app\plugin\vanity\controller\Index::class, 'index']);

// 生成地址
Route::post('/generate', [app\plugin\vanity\controller\Index::class, 'generate']);

// 历史记录
Route::get('/history', [app\plugin\vanity\controller\Index::class, 'history']);

// 导出结果
Route::post('/export', [app\plugin\vanity\controller\Index::class, 'export']);
