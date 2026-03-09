<?php
use Webman\Route;

// 网络工具插件路由
Route::any('/app/nettools/index', [plugin\nettools\app\controller\Index::class, 'index']);
Route::post('/app/nettools/ping', [plugin\nettools\app\controller\Index::class, 'ping']);
Route::post('/app/nettools/dns', [plugin\nettools\app\controller\Index::class, 'dns']);
Route::post('/app/nettools/whois', [plugin\nettools\app\controller\Index::class, 'whois']);
Route::post('/app/nettools/port', [plugin\nettools\app\controller\Index::class, 'port']);
