<?php
namespace plugin\encoder\app\controller;

use support\Request;

class Index
{
    public function index(Request $request)
    {
        return view('index/index');
    }
    
    // Base64 编码/解码
    public function base64(Request $request)
    {
        $text = $request->post('text', '');
        $action = $request->post('action', 'encode');
        
        if (empty($text)) {
            return json(['code' => -1, 'msg' => '请输入内容']);
        }
        
        try {
            if ($action === 'encode') {
                $result = base64_encode($text);
            } else {
                $result = base64_decode($text);
            }
            
            return json(['code' => 0, 'data' => ['result' => $result]]);
        } catch (\Exception $e) {
            return json(['code' => -1, 'msg' => '处理失败：' . $e->getMessage()]);
        }
    }
    
    // URL 编码/解码
    public function url(Request $request)
    {
        $text = $request->post('text', '');
        $action = $request->post('action', 'encode');
        
        if (empty($text)) {
            return json(['code' => -1, 'msg' => '请输入内容']);
        }
        
        try {
            if ($action === 'encode') {
                $result = urlencode($text);
            } else {
                $result = urldecode($text);
            }
            
            return json(['code' => 0, 'data' => ['result' => $result]]);
        } catch (\Exception $e) {
            return json(['code' => -1, 'msg' => '处理失败：' . $e->getMessage()]);
        }
    }
    
    // 哈希生成
    public function hash(Request $request)
    {
        $text = $request->post('text', '');
        $algo = $request->post('algo', 'md5');
        
        if (empty($text)) {
            return json(['code' => -1, 'msg' => '请输入内容']);
        }
        
        try {
            $result = hash($algo, $text);
            return json(['code' => 0, 'data' => ['result' => $result]]);
        } catch (\Exception $e) {
            return json(['code' => -1, 'msg' => '处理失败：' . $e->getMessage()]);
        }
    }
    
    // JSON 格式化/压缩
    public function json(Request $request)
    {
        $text = $request->post('text', '');
        $action = $request->post('action', 'format');
        
        if (empty($text)) {
            return json(['code' => -1, 'msg' => '请输入 JSON 内容']);
        }
        
        try {
            $data = json_decode($text, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return json(['code' => -1, 'msg' => 'JSON 格式错误：' . json_last_error_msg()]);
            }
            
            if ($action === 'format') {
                $result = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                $result = json_encode($data, JSON_UNESCAPED_UNICODE);
            }
            
            return json(['code' => 0, 'data' => ['result' => $result]]);
        } catch (\Exception $e) {
            return json(['code' => -1, 'msg' => '处理失败：' . $e->getMessage()]);
        }
    }
}
