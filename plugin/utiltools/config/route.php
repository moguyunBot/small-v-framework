<?php
use Webman\Route;
Route::any('/app/utiltools/index', [plugin\utiltools\app\controller\Index::class, 'index']);
Route::post('/app/utiltools/ip', [plugin\utiltools\app\controller\Index::class, 'ip']);
