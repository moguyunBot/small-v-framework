<?php
namespace plugin\utiltools\app\controller;
use support\Request;

class Index
{
    public function index(Request $request){return view('index/index');}
    
    public function ip(Request $request){
        $ip=$request->post('ip','');
        if(empty($ip))$ip=$request->getRealIp();
        $info=@file_get_contents("http://ip-api.com/json/{$ip}?lang=zh-CN");
        $data=$info?json_decode($info,true):[];
        return json(['code'=>0,'data'=>$data]);
    }
}
