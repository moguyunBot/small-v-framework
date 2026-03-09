<?php
namespace app\admin\controller;

use app\admin\model\Config as ConfigModel;

/**
 * 系统配置控制器
 */
class Config extends Base
{
    /**
     * 配置管理页面
     * @return \Webman\Http\Response
     */
    public function index()
    {
        // 从数据库获取配置
        $configs = ConfigModel::getConfigsByGroup('');
        
        // 如果没有配置数据，显示提示
        if (empty($configs)) {
            return view('config/index', [
                'configs' => [],
                'key' => '',
                'emptyMessage' => "系统配置暂未配置任何配置项"
            ]);
        }
        
        $key = $this->get['key'] ?? array_key_first($configs);
        
        if ($this->isPost()) {
            try {
                foreach ($this->post as $k => $v) {
                    foreach ($v['config'] as $kk => $vv) {
                        if (empty($vv['type'])) {
                            unset($this->post[$k]['config'][$kk]);
                            continue;
                        }
                        
                        // 处理单图上传
                        if ($vv['type'] == 'image') {
                            $file = $this->request->file($k)['config'][$kk]['value'] ?? null;
                            if ($file) {
                                $this->post[$k]['config'][$kk]['value'] = upload($file);
                            }
                        }
                        // 处理多图上传
                        else if ($vv['type'] == 'images') {
                            $files = $this->request->file($k)['config'][$kk]['value'] ?? null;
                            if ($files && is_array($files)) {
                                $value = [];
                                foreach ($files as $file) {
                                    if ($file) {
                                        $value[] = upload($file);
                                    }
                                }
                                if (!empty($value)) {
                                    $this->post[$k]['config'][$kk]['value'] = $value;
                                }
                            }
                        }
                        // 处理单视频上传
                        else if ($vv['type'] == 'video') {
                            $file = $this->request->file($k)['config'][$kk]['value'] ?? null;
                            if ($file) {
                                $this->post[$k]['config'][$kk]['value'] = upload($file);
                            }
                        }
                        // 处理多视频上传
                        else if ($vv['type'] == 'videos') {
                            $files = $this->request->file($k)['config'][$kk]['value'] ?? null;
                            if ($files && is_array($files)) {
                                $value = [];
                                foreach ($files as $file) {
                                    if ($file) {
                                        $value[] = upload($file);
                                    }
                                }
                                if (!empty($value)) {
                                    $this->post[$k]['config'][$kk]['value'] = $value;
                                }
                            }
                        }
                        // 处理富文本编辑器
                        else if ($vv['type'] == 'editor') {
                            if (!empty($vv['value'])) {
                                $vv['value'] = $this->downloadRemoteMedia($vv['value']);
                                $this->post[$k]['config'][$kk]['value'] = $vv['value'];
                            }
                        }
                    }
                }
                
                // 保存到数据库
                foreach ($this->post as $groupKey => $groupData) {
                    ConfigModel::saveConfigs($groupKey, $groupData['config']);
                }
                
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '保存失败');
            }
            
            // 构建跳转 URL，保持当前配置组
            $redirectUrl = 'index';
            if ($key) {
                $redirectUrl .= '?key=' . $key;
            }
            
            return success('保存成功', $redirectUrl);
        }
        
        return view('config/index', [
            'configs' => $configs, 
            'key' => $key
        ]);
    }
    
    /**
     * 上传图片（富文本编辑器使用）
     * @return \Webman\Http\Response
     */
    public function uploadImage()
    {
        if ($this->request->isPost()) {
            try {
                $file = $this->request->file('image');
                $savename = upload($file);
            } catch (\Exception $e) {
                return json(['errno' => 1, 'message' => $e->getMessage() ?: '上传失败']);
            }
            return json(['errno' => 0, 'data' => ['url' => $savename]]);
        }
    }
    
    /**
     * 下载外链图片和视频到本地
     * @param string $content 富文本内容
     * @return string 处理后的内容
     */
    protected function downloadRemoteMedia(string $content): string
    {
        if (empty($content)) {
            return $content;
        }
        
        // 匹配 img 标签的 src
        $pattern_img = '/<img[^>]+src=["\']([^"\']+)["\']/i';
        // 匹配 video 标签的 src
        $pattern_video = '/<video[^>]+src=["\']([^"\']+)["\']/i';
        // 匹配 source 标签的 src
        $pattern_source = '/<source[^>]+src=["\']([^"\']+)["\']/i';
        
        $content = preg_replace_callback($pattern_img, function($matches) {
            return $this->replaceWithLocalUrl($matches[0], $matches[1], 'image');
        }, $content);
        
        $content = preg_replace_callback($pattern_video, function($matches) {
            return $this->replaceWithLocalUrl($matches[0], $matches[1], 'video');
        }, $content);
        
        $content = preg_replace_callback($pattern_source, function($matches) {
            return $this->replaceWithLocalUrl($matches[0], $matches[1], 'video');
        }, $content);
        
        return $content;
    }
    
    /**
     * 替换为本地 URL
     * @param string $tag 原标签
     * @param string $url 外链 URL
     * @param string $type 类型 image|video
     * @return string 替换后的标签
     */
    protected function replaceWithLocalUrl(string $tag, string $url, string $type): string
    {
        // 如果已经是本地链接，直接返回
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($url, '/uploads/') !== false || ($httpHost && strpos($url, $httpHost) !== false)) {
            return $tag;
        }
        
        // 如果不是 http/https 开头，直接返回
        if (!preg_match('/^https?:\/\//i', $url)) {
            return $tag;
        }
        
        try {
            // 下载文件到临时目录
            $tempFile = $this->downloadFile($url);
            
            if (!$tempFile) {
                error_log('下载文件失败: ' . $url);
                return $tag;
            }
            
            // 从 URL 中提取扩展名
            $urlPath = parse_url($url, PHP_URL_PATH);
            $ext = pathinfo($urlPath, PATHINFO_EXTENSION);
            
            if (empty($ext)) {
                $ext = $type == 'image' ? 'jpg' : 'mp4';
            }
            
            $ext = strtolower($ext);
            
            // 如果是 HEIC/HEIF 格式，转换为 JPG
            if (in_array($ext, ['heic', 'heif'])) {
                $convertedFile = $this->convertHeicToJpg($tempFile);
                if ($convertedFile) {
                    @unlink($tempFile);
                    $tempFile = $convertedFile;
                    $ext = 'jpg';
                }
            }
            
            // 生成文件名
            $filename = uniqid() . '.' . $ext;
            
            // 获取 MIME 类型
            $mimeType = mime_content_type($tempFile);
            
            if (!$mimeType || $mimeType === 'application/octet-stream') {
                $mimeMap = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'mp4' => 'video/mp4',
                    'avi' => 'video/avi',
                    'mov' => 'video/quicktime',
                ];
                $mimeType = $mimeMap[$ext] ?? 'application/octet-stream';
            }
            
            // 创建临时文件对象
            $uploadFile = new \support\UploadFile($tempFile, $filename, $mimeType);
            
            // 调用 upload 函数保存
            $localPath = upload($uploadFile);
            
            // 删除临时文件
            @unlink($tempFile);
            
            // 替换 URL
            return str_replace($url, $localPath, $tag);
            
        } catch (\Exception $e) {
            error_log('下载外链失败: ' . $url . ' - ' . $e->getMessage());
            return $tag;
        }
    }
    
    /**
     * 转换 HEIC 为 JPG
     * @param string $heicFile HEIC 文件路径
     * @return string|false JPG 文件路径或 false
     */
    protected function convertHeicToJpg(string $heicFile)
    {
        try {
            if (extension_loaded('imagick') && class_exists('\Imagick')) {
                $imagick = new \Imagick($heicFile);
                $imagick->setImageFormat('jpg');
                $imagick->setImageCompressionQuality(90);
                
                $jpgFile = sys_get_temp_dir() . '/' . uniqid() . '.jpg';
                $imagick->writeImage($jpgFile);
                $imagick->clear();
                $imagick->destroy();
                
                return $jpgFile;
            }
            
            return false;
            
        } catch (\Exception $e) {
            error_log('HEIC 转换失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 下载文件到临时目录
     * @param string $url 文件 URL
     * @return string|false 临时文件路径
     */
    protected function downloadFile(string $url)
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);
            
            $content = @file_get_contents($url, false, $context);
            
            if ($content === false) {
                return false;
            }
            
            $tempFile = sys_get_temp_dir() . '/' . uniqid() . '_' . basename($url);
            
            if (file_put_contents($tempFile, $content) === false) {
                return false;
            }
            
            return $tempFile;
            
        } catch (\Exception $e) {
            return false;
        }
    }
}
