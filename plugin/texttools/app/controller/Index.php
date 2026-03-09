<?php
namespace plugin\texttools\app\controller;
use support\Request;

class Index
{
    public function index(Request $request){return view('index/index');}
    
    public function diff(Request $request){
        $text1=$request->post('text1','');$text2=$request->post('text2','');
        if(empty($text1)||empty($text2))return json(['code'=>-1,'msg'=>'请输入两段文本']);
        $lines1=explode("\n",$text1);$lines2=explode("\n",$text2);
        $diff=[];foreach($lines1 as $i=>$line){if(!isset($lines2[$i])||$lines2[$i]!==$line)$diff[]=($i+1).": ".$line." → ".(isset($lines2[$i])?$lines2[$i]:'(删除)');}
        return json(['code'=>0,'data'=>['diff'=>implode("\n",$diff)]]);
    }
    
    public function count(Request $request){
        $text=$request->post('text','');
        if(empty($text))return json(['code'=>-1,'msg'=>'请输入文本']);
        return json(['code'=>0,'data'=>['chars'=>mb_strlen($text),'words'=>str_word_count($text),'lines'=>substr_count($text,"\n")+1]]);
    }
    
    public function changeCase(Request $request){
        $text=$request->post('text','');$type=$request->post('type','upper');
        if(empty($text))return json(['code'=>-1,'msg'=>'请输入文本']);
        $result=$type==='upper'?mb_strtoupper($text):mb_strtolower($text);
        return json(['code'=>0,'data'=>['result'=>$result]]);
    }
    
    public function unique(Request $request){
        $text=$request->post('text','');
        if(empty($text))return json(['code'=>-1,'msg'=>'请输入文本']);
        $lines=array_unique(explode("\n",$text));
        return json(['code'=>0,'data'=>['result'=>implode("\n",$lines)]]);
    }
}
