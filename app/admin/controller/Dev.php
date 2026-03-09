<?php
namespace app\admin\controller;

use app\admin\model\Config as ConfigModel;
use app\admin\model\ConfigGroup as ConfigGroupModel;

/**
 * 开发者工具控制器
 */
class Dev extends Base
{
    /**
     * 表单构建器
     * @return \Webman\Http\Response
     */
    public function formBuild()
    {
        return view();
    }
    
    /**
     * 配置分组列表
     * @return \Webman\Http\Response
     */
    public function groupIndex()
    {
        $list = ConfigGroupModel::order('sort asc, id desc')
            ->paginate(20);
        
        return $this->view('configgroup/index', [
            'list' => $list
        ]);
    }
    
    /**
     * 添加配置分组
     * @return \Webman\Http\Response
     */
    public function groupAdd()
    {
        if ($this->isPost()) {
            try {
                validate([
                    'group_key|分组标识'     => 'require',
                    'group_title|分组标题'   => 'require',
                ])->check($this->post);
                
                // 检查分组标识是否已存在
                $exists = ConfigGroupModel::where('group_key', $this->post['group_key'])->find();
                if ($exists) {
                    throw new \Exception('分组标识已存在');
                }
                
                ConfigGroupModel::create($this->post);
                
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '添加失败');
            }
            
            return success('添加成功', 'groupIndex');
        }
        
        return $this->view('configgroup/add');
    }
    
    /**
     * 编辑配置分组
     * @return \Webman\Http\Response
     */
    public function groupEdit()
    {
        $id = $this->get['id'] ?? 0;
        $group = ConfigGroupModel::find($id);
        
        if (!$group) {
            return error('分组不存在');
        }
        
        if ($this->isPost()) {
            try {
                validate([
                    'group_key|分组标识'     => 'require',
                    'group_title|分组标题'   => 'require',
                ])->check($this->post);
                
                // 检查分组标识是否已被其他记录使用
                $exists = ConfigGroupModel::where('group_key', $this->post['group_key'])
                    ->where('id', '<>', $id)
                    ->find();
                if ($exists) {
                    throw new \Exception('分组标识已存在');
                }
                
                $group->save($this->post);
                
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '编辑失败');
            }
            
            return success('编辑成功', 'groupIndex');
        }
        
        return $this->view('configgroup/edit', [
            'group' => $group
        ]);
    }
    
    /**
     * 删除配置分组
     * @return \Webman\Http\Response
     */
    public function groupDelete()
    {
        if (!$this->isPost()) {
            return error('非法请求');
        }
        
        $id = $this->post['id'] ?? 0;
        
        try {
            $group = ConfigGroupModel::find($id);
            if (!$group) {
                throw new \Exception('分组不存在');
            }
            
            // 检查是否有配置项
            $count = ConfigModel::where('group_key', $group['group_key'])->count();
            
            if ($count > 0) {
                throw new \Exception('该分组下还有配置项，无法删除');
            }
            
            $group->delete();
            
            return success('删除成功');
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
    }
    
    /**
     * 配置项列表
     * @return \Webman\Http\Response
     */
    public function configManage()
    {
        // 从 URL 获取分组
        $groupKey = $this->get['group'] ?? '';
        
        if ($this->isPost()) {
            $page = $this->post['page'] ?? 1;
            $limit = $this->post['limit'] ?? 15;
            
            $where = [];
            
            if (!empty($this->post['group_key'])) {
                $where[] = ['group_key', 'like', '%' . $this->post['group_key'] . '%'];
            }
            
            if (!empty($this->post['config_key'])) {
                $where[] = ['config_key', 'like', '%' . $this->post['config_key'] . '%'];
            }
            
            $list = ConfigModel::where($where)
                ->order('group_key asc, sort asc, id desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]);
            
            return json([
                'code' => 0,
                'msg' => '',
                'count' => $list->total(),
                'data' => $list->items()
            ]);
        }
        
        // GET 请求，返回初始数据
        $where = [];
        
        // 必须指定分组
        if (empty($groupKey)) {
            return error('请先选择配置分组');
        }
        
        $where[] = ['group_key', '=', $groupKey];
        
        $list = ConfigModel::where($where)
            ->order('sort asc, id desc')
            ->paginate(20);
        
        // 获取分组信息
        $group = ConfigGroupModel::where('group_key', $groupKey)->find();
        
        if (!$group) {
            return error('配置分组不存在');
        }
        
        return $this->view('config/manage', [
            'groupKey' => $groupKey,
            'group' => $group,
            'list' => $list
        ]);
    }
    
    /**
     * 添加配置项
     * @return \Webman\Http\Response
     */
    public function configAdd()
    {
        // 从 URL 获取分组
        $groupKey = $this->get['group'] ?? '';
        
        if (empty($groupKey)) {
            return error('请先选择配置分组');
        }
        
        // 获取分组信息
        $group = ConfigGroupModel::where('group_key', $groupKey)->find();
        
        if (!$group) {
            return error('配置分组不存在');
        }
        
        if ($this->isPost()) {
            try {
                validate([
                    'config_key|配置项标识'     => 'require',
                    'config_title|配置项标题'   => 'require',
                ])->check($this->post);
                
                // 检查配置项标识是否已存在
                $exists = ConfigModel::where('config_key', $this->post['config_key'])->find();
                if ($exists) {
                    throw new \Exception('配置项标识已存在');
                }
                
                $data = $this->post;
                
                // 设置分组
                $data['group_key'] = $groupKey;
                $data['group_title'] = $group['group_title'];
                
                // 处理配置选项
                if (!empty($data['config_options'])) {
                    if (is_string($data['config_options'])) {
                        // 验证 JSON 格式
                        $options = json_decode($data['config_options'], true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new \Exception('配置选项格式错误，请输入正确的 JSON 格式');
                        }
                    }
                }
                
                ConfigModel::create($data);
                
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '添加失败');
            }
            
            return success('添加成功', 'configManage?group=' . $groupKey);
        }
        
        return $this->view('config/add_config', [
            'group' => $group
        ]);
    }
    
    /**
     * 编辑配置项
     * @return \Webman\Http\Response
     */
    public function configEdit()
    {
        $id = $this->get['id'] ?? 0;
        $config = ConfigModel::find($id);
        
        if (!$config) {
            return error('配置项不存在');
        }
        
        $groupKey = $config['group_key'];
        
        if ($this->isPost()) {
            try {
                validate([
                    'config_key|配置项标识'     => 'require',
                    'config_title|配置项标题'   => 'require',
                ])->check($this->post);
                
                // 检查配置项标识是否已被其他记录使用
                $exists = ConfigModel::where('config_key', $this->post['config_key'])
                    ->where('id', '<>', $id)
                    ->find();
                if ($exists) {
                    throw new \Exception('配置项标识已存在');
                }
                
                $data = $this->post;
                
                // 保持原分组
                $data['group_key'] = $groupKey;
                
                // 处理配置选项
                if (!empty($data['config_options'])) {
                    if (is_string($data['config_options'])) {
                        // 验证 JSON 格式
                        $options = json_decode($data['config_options'], true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new \Exception('配置选项格式错误，请输入正确的 JSON 格式');
                        }
                    }
                }
                
                $config->save($data);
                
            } catch (\Exception $e) {
                return error($e->getMessage() ?: '编辑失败');
            }
            
            return success('编辑成功', 'configManage?group=' . $groupKey);
        }
        
        // 获取分组信息
        $group = ConfigGroupModel::where('group_key', $groupKey)->find();
        
        // 处理 config_options 显示
        if (!empty($config['config_options'])) {
            if (is_array($config['config_options'])) {
                $config['config_options'] = json_encode($config['config_options'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
        }
        
        return $this->view('config/edit_config', [
            'config' => $config,
            'group' => $group
        ]);
    }
    
    /**
     * 删除配置项
     * @return \Webman\Http\Response
     */
    public function configDelete()
    {
        if (!$this->isPost()) {
            return error('非法请求');
        }
        
        $id = $this->post['id'] ?? 0;
        
        try {
            $config = ConfigModel::find($id);
            if (!$config) {
                return error('配置项不存在');
            }
            
            $config->delete();
            
            return success('删除成功');
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
    }
}
