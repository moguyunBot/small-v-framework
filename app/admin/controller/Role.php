<?php
namespace app\admin\controller;

use app\admin\model\Rule;

/**
 * 角色管理控制器
 */
class Role extends Base{
    /**
     * 角色列表
     * @return \Webman\Http\Response
     */
    public function index(){
        $where = [];
        $roles = $this->model->where($where)->paginate(['list_rows'=>20,'query'=>$this->get]);
        return $this->view('',['roles'=>$roles]);
    }
 
    /**
     * 添加角色
     * @return \Webman\Http\Response
     */
    public function add(){
        if($this->isPost()){
            try{
                validate([
                    'name|用户组名称'       =>  'require|unique:roles',
                    'rules|权限'            =>  'require'
                ])->useZh()->check($this->post);
                
                $this->post['rules'] = join(',',$this->post['rules']);
                $this->model::create($this->post);
                
            }catch(\Exception $e){
                return error($e->getMessage()?:'添加失败');
            }
            return success('添加成功','index');
        }
        
        // 获取系统权限（树形结构）
        $systemRules = $this->getTreeRules('system', []);
        
        // 获取插件权限（按插件分组）
        $pluginRules = $this->getPluginRules([]);
        
        return $this->view('',[
            'systemRules' => $systemRules,
            'pluginRules' => $pluginRules,
        ]);
    }
    
    /**
     * 编辑角色
     * @return \Webman\Http\Response
     */
    public function edit(){
        $role = $this->model::find($this->get['id']);
        
        if($this->isPost()){
            try{
                validate([
                    'name|角色名称'         =>  'require|unique:roles',
                    'rules|权限'            =>  'require'
                ])->useZh()->check($this->post);
                
                $this->post['rules'] = join(',',$this->post['rules']);
                $role->replace()->save($this->post);
            }catch(\Exception $e){
                return error($e->getMessage()?:'修改失败');
            }
            return success('修改成功','index');
        }
        
        // 当前角色的权限ID数组
        $checkedRules = $role['rules'] == '*' ? [] : explode(',', $role['rules']);
        
        // 获取系统权限（树形结构）
        $systemRules = $this->getTreeRules('system', $checkedRules);
        
        // 获取插件权限（按插件分组）
        $pluginRules = $this->getPluginRules($checkedRules);
        
        return $this->view('',[
            'systemRules' => $systemRules,
            'pluginRules' => $pluginRules,
            'role'        => $role,
        ]);
    }
    
    /**
     * 获取树形权限列表
     */
    protected function getTreeRules(string $type, array $checkedRules): array
    {
        return Rule::field('id,pid parent,title text')
            ->where(['status' => 1, 'type' => $type])
            ->order('sort asc,id asc')
            ->select()
            ->map(function ($v) use ($checkedRules) {
                $v['parent'] = $v['parent'] ?: '#';
                $v['state'] = [
                    'selected' => in_array($v['id'], $checkedRules)
                ];
                return $v;
            })->toArray();
    }
    
    /**
     * 获取插件权限（按插件分组）
     */
    protected function getPluginRules(array $checkedRules): array
    {
        $rules = Rule::where(['status' => 1, 'type' => 'plugin'])
            ->order('plugin asc, sort asc, id asc')
            ->select();
        
        $grouped = [];
        foreach ($rules as $rule) {
            $plugin = $rule['plugin'] ?? 'other';
            if (!isset($grouped[$plugin])) {
                // 获取插件信息
                $pluginInfo = \app\admin\model\Plugin::where('identifier', $plugin)->find();
                $grouped[$plugin] = [
                    'name'   => $pluginInfo['name'] ?? $plugin,
                    'icon'   => $pluginInfo['icon'] ?? 'mdi mdi-puzzle',
                    'rules'  => [],
                ];
            }
            
            $rule['state'] = [
                'selected' => in_array($rule['id'], $checkedRules)
            ];
            $grouped[$plugin]['rules'][] = $rule->toArray();
        }
        
        return $grouped;
    }
    
    /**
     * 删除角色
     * @return \Webman\Http\Response
     */
    public function del(){
        if($this->isPost()){
            try{
                $role = $this->model::find($this->post['id']);
                if(!$role){
                    throw new \Exception('角色不存在');
                }
                $role->delete();
            }catch(\Exception $e){
                return error($e->getMessage()?:'删除失败');
            }
            return success('删除成功');
        }
    }
}
