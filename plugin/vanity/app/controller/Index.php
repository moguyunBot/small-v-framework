<?php
namespace plugin\vanity\app\controller;
use support\Request;

class Index
{
    public function index(Request $request)
    {
        return view('index/index');
    }
}
