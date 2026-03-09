<?php
use Webman\Route;
Route::any('/app/encoder/index', [plugin\encoder\app\controller\Index::class, 'index']);
Route::post('/app/encoder/base64', [plugin\encoder\app\controller\Index::class, 'base64']);
Route::post('/app/encoder/url', [plugin\encoder\app\controller\Index::class, 'url']);
Route::post('/app/encoder/hash', [plugin\encoder\app\controller\Index::class, 'hash']);
Route::post('/app/encoder/json', [plugin\encoder\app\controller\Index::class, 'json']);
