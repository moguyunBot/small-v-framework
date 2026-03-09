<?php
use Webman\Route;
Route::any('/app/devtools/index', [plugin\devtools\app\controller\Index::class, 'index']);
Route::post('/app/devtools/timestamp', [plugin\devtools\app\controller\Index::class, 'timestamp']);
Route::post('/app/devtools/uuid', [plugin\devtools\app\controller\Index::class, 'uuid']);
Route::post('/app/devtools/password', [plugin\devtools\app\controller\Index::class, 'password']);
Route::post('/app/devtools/color', [plugin\devtools\app\controller\Index::class, 'color']);
