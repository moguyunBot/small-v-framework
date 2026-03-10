<?php
use Webman\Route;
Route::any('/app/vanity/index', [plugin\vanity\app\controller\Index::class, 'index']);
