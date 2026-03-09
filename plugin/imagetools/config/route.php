<?php
use Webman\Route;
Route::any('/app/imagetools/index', [plugin\imagetools\app\controller\Index::class, 'index']);
Route::post('/app/imagetools/qrcode', [plugin\imagetools\app\controller\Index::class, 'qrcode']);
