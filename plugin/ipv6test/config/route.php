<?php
use Webman\Route;

// IPv6 测试插件路由
Route::any('/app/ipv6test/index', [plugin\ipv6test\app\controller\Index::class, 'index']);
Route::any('/app/ipv6test/faq', [plugin\ipv6test\app\controller\Index::class, 'faq']);
Route::post('/app/ipv6test/getIpInfo', [plugin\ipv6test\app\controller\Index::class, 'getIpInfo']);
Route::post('/app/ipv6test/getMyIpv4', [plugin\ipv6test\app\controller\Index::class, 'getMyIpv4']);
Route::post('/app/ipv6test/getMyIpv6', [plugin\ipv6test\app\controller\Index::class, 'getMyIpv6']);
Route::post('/app/ipv6test/testDns', [plugin\ipv6test\app\controller\Index::class, 'testDns']);
Route::post('/app/ipv6test/testSpeed', [plugin\ipv6test\app\controller\Index::class, 'testSpeed']);

