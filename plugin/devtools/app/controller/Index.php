<?php
namespace plugin\devtools\app\controller;
use support\Request;

class Index
{
    public function index(Request $request){return view('index/index');}
    
    public function timestamp(Request $request){
        $action=$request->post('action','now');
        if($action==='now'){
            return json(['code'=>0,'data'=>['timestamp'=>time(),'datetime'=>date('Y-m-d H:i:s')]]);
        }else{
            $ts=$request->post('timestamp',0);
            return json(['code'=>0,'data'=>['datetime'=>date('Y-m-d H:i:s',$ts)]]);
        }
    }
    
    public function uuid(Request $request){
        $uuid=sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
        return json(['code'=>0,'data'=>['uuid'=>$uuid]]);
    }
    
    public function password(Request $request){
        $length=$request->post('length',16);
        $chars='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password='';for($i=0;$i<$length;$i++)$password.=$chars[mt_rand(0,strlen($chars)-1)];
        return json(['code'=>0,'data'=>['password'=>$password]]);
    }
    
    public function color(Request $request){
        $color=sprintf('#%06X',mt_rand(0,0xFFFFFF));
        return json(['code'=>0,'data'=>['color'=>$color]]);
    }
}
