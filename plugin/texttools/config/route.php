<?php
use Webman\Route;
Route::any('/app/texttools/index', [plugin\texttools\app\controller\Index::class, 'index']);
Route::post('/app/texttools/diff', [plugin\texttools\app\controller\Index::class, 'diff']);
Route::post('/app/texttools/count', [plugin\texttools\app\controller\Index::class, 'count']);
Route::post('/app/texttools/case', [plugin\texttools\app\controller\Index::class, 'changeCase']);
Route::post('/app/texttools/unique', [plugin\texttools\app\controller\Index::class, 'unique']);
