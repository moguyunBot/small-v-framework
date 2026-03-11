<?php
namespace app\admin\controller;

use app\admin\model\Config as ConfigModel;
use app\admin\model\ConfigGroup as ConfigGroupModel;

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
        // 支持 plugin 参数：空=系统配置，有值=插件配置
        $pluginId = $this->get['plugin'] ?? '';
        
        // 插件设置仅超级管理员可访问
        if ($pluginId !== '') {
            if ($err = $this->checkSuperAdmin()) return $err;
        }
        // 从数据库获取配置
        $configs = ConfigModel::getConfigsByGroup($pluginId);
        
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
            $params = [];
            if ($key) $params[] = 'key=' . $key;
            if ($pluginId) $params[] = 'plugin=' . $pluginId;
            if (!empty($this->get['iframe'])) $params[] = 'iframe=1';
            if ($params) $redirectUrl .= '?' . implode('&', $params);
            
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
     * 配置分组列表
     */
    public function groupIndex()
    {
        $list = ConfigGroupModel::order('sort asc, id desc')->paginate(20);
        return $this->view(['list' => $list]);
    }

    /**
     * 添加配置分组
     */
    public function groupAdd()
    {
        if ($this->isPost()) {
            try {
                validate([
                    'group_key|分组标识'   => 'require',
                    'group_title|分组标题' => 'require',
                ])->check($this->post);
                $exists = ConfigGroupModel::where('group_key', $this->post['group_key'])->find();
                if ($exists) throw new \Exception('分组标识已存在');
                ConfigGroupModel::create($this->post);
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '添加失败');
            }
            return success('添加成功', 'groupIndex');
        }
        return $this->view();
    }

    /**
     * 编辑配置分组
     */
    public function groupEdit()
    {
        $id    = $this->get['id'] ?? 0;
        $group = ConfigGroupModel::find($id);
        if (!$group) return error('分组不存在');

        if ($this->isPost()) {
            try {
                validate([
                    'group_key|分组标识'   => 'require',
                    'group_title|分组标题' => 'require',
                ])->check($this->post);
                $exists = ConfigGroupModel::where('group_key', $this->post['group_key'])
                    ->where('id', '<>', $id)->find();
                if ($exists) throw new \Exception('分组标识已存在');
                $group->save($this->post);
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '编辑失败');
            }
            return success('编辑成功', 'groupIndex');
        }
        return $this->view(['group' => $group]);
    }

    /**
     * 删除配置分组
     */
    public function groupDelete()
    {
        if (!$this->isPost()) return error('非法请求');
        $id = $this->post['id'] ?? 0;
        try {
            $group = ConfigGroupModel::find($id);
            if (!$group) throw new \Exception('分组不存在');
            $count = ConfigModel::where('group_key', $group['group_key'])->count();
            if ($count > 0) throw new \Exception('该分组下还有配置项，无法删除');
            $group->delete();
            return success('删除成功');
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
    }

    /**
     * 配置项列表
     */
    public function configManage()
    {
        $groupKey = $this->get['group'] ?? '';
        $pluginId = $this->get['plugin'] ?? '';
        if ($pluginId !== '') {
            if ($err = $this->checkSuperAdmin()) return $err;
        }

        if (empty($groupKey)) return error('请先选择配置分组');

        $group = ConfigGroupModel::where('group_key', $groupKey)->find();
        if (!$group) return error('配置分组不存在');

        $where = [['group_key', '=', $groupKey]];
        if ($pluginId !== '') $where[] = ['plugin', '=', $pluginId];

        $list = ConfigModel::where($where)->order('sort asc, id desc')->paginate(20);

        return $this->view([
            'groupKey' => $groupKey,
            'pluginId' => $pluginId,
            'group'    => $group,
            'list'     => $list,
        ]);
    }

    /**
     * 添加配置项
     */
    public function configAdd()
    {
        $groupKey = $this->get['group'] ?? '';
        $pluginId = $this->get['plugin'] ?? '';
        if ($pluginId !== '') {
            if ($err = $this->checkSuperAdmin()) return $err;
        }
        if (empty($groupKey)) return error('请先选择配置分组');

        $group = ConfigGroupModel::where('group_key', $groupKey)->find();
        if (!$group) return error('配置分组不存在');

        if ($this->isPost()) {
            try {
                validate([
                    'config_key|配置项标识'   => 'require',
                    'config_title|配置项标题' => 'require',
                ])->check($this->post);
                $exists = ConfigModel::where('config_key', $this->post['config_key'])->find();
                if ($exists) throw new \Exception('配置项标识已存在');
                $data                = $this->post;
                $data['group_key']   = $groupKey;
                $data['group_title'] = $group['group_title'];
                $data['plugin']      = $pluginId;
                if (!empty($data['config_options']) && is_string($data['config_options'])) {
                    json_decode($data['config_options']);
                    if (json_last_error() !== JSON_ERROR_NONE) throw new \Exception('配置选项格式错误');
                }
                ConfigModel::create($data);
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '添加失败');
            }
            $back = 'configManage?group=' . $groupKey;
            if ($pluginId) $back .= '&plugin=' . $pluginId;
            if (!empty($this->get['iframe'])) $back .= '&iframe=1';
            return success('添加成功', $back);
        }
        return $this->view(['group' => $group, 'pluginId' => $pluginId]);
    }

    /**
     * 编辑配置项
     */
    public function configEdit()
    {
        $id     = $this->get['id'] ?? 0;
        $config = ConfigModel::find($id);
        if (!$config) return error('配置项不存在');

        $groupKey = $config['group_key'];
        $pluginId = $config['plugin'] ?? '';

        if ($this->isPost()) {
            try {
                validate([
                    'config_key|配置项标识'   => 'require',
                    'config_title|配置项标题' => 'require',
                ])->check($this->post);
                $exists = ConfigModel::where('config_key', $this->post['config_key'])
                    ->where('id', '<>', $id)->find();
                if ($exists) throw new \Exception('配置项标识已存在');
                $data              = $this->post;
                $data['group_key'] = $groupKey;
                $data['plugin']    = $pluginId;
                if (!empty($data['config_options']) && is_string($data['config_options'])) {
                    json_decode($data['config_options']);
                    if (json_last_error() !== JSON_ERROR_NONE) throw new \Exception('配置选项格式错误');
                }
                $config->save($data);
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '编辑失败');
            }
            $back = 'configManage?group=' . $groupKey;
            if ($pluginId) $back .= '&plugin=' . $pluginId;
            if (!empty($this->get['iframe'])) $back .= '&iframe=1';
            return success('编辑成功', $back);
        }

        $group = ConfigGroupModel::where('group_key', $groupKey)->find();
        if (!empty($config['config_options']) && is_array($config['config_options'])) {
            $config['config_options'] = json_encode($config['config_options'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        return $this->view(['config' => $config, 'group' => $group, 'pluginId' => $pluginId]);
    }

    /**
     * 删除配置项
     */
    public function configDelete()
    {
        if (!$this->isPost()) return error('非法请求');
        $id = $this->post['id'] ?? 0;
        try {
            $config = ConfigModel::find($id);
            if (!$config) return error('配置项不存在');
            $config->delete();
            return success('删除成功');
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
    }
}
