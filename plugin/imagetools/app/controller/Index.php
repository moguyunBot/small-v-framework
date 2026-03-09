<?php
namespace plugin\imagetools\app\controller;
use support\Request;

class Index
{
    public function index(Request $request){return view('index/index');}
    
    public function qrcode(Request $request){
        $text=$request->post('text','');
        if(empty($text))return json(['code'=>-1,'msg'=>'请输入内容']);
        $url='https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='.urlencode($text);
        return json(['code'=>0,'data'=>['url'=>$url]]);
    }
}
