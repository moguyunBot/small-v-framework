<?php
namespace app\admin\controller;

/**
 * 权限规则管理控制器
 */
class Rule extends Base{
    /**
     * 规则列表
     * @return \Webman\Http\Response
     */
    public function index(){
        // 支持 plugin 参数：空=系统菜单，有值=插件菜单
        $pluginId = $this->get['plugin'] ?? '';
        if ($pluginId !== '') {
            if ($err = $this->checkSuperAdmin()) return $err;
            $rules = $this->model->where('plugin', $pluginId)->order('sort asc,id desc')->select();
        } else {
            // 只显示系统菜单（plugin=''），插件菜单由插件管理模块统一管理
            $rules = $this->model->where('plugin', '')->order('sort asc,id desc')->select();
        }
        $menus = $this->recursion_title($rules);
        return $this->view(['menus'=>$menus, 'pluginId'=>$pluginId]);
    }
    
    /**
     * 递归处理标题层级
     * @param array $list 规则列表
     * @param int $pid 父级ID
     * @param int $level 层级
     * @return array
     */
    protected function recursion_title($list, $pid = 0, $level = 0)
    {
        $arr = [];
        foreach ($list as $v) {
            if ($v['pid'] == $pid) {
                $v['title'] = str_repeat('|——', $level) . $v['title'];
                $arr[] = $v;
                $arr = array_merge($arr, $this->recursion_title($list, $v['id'], $level + 1));
            }
        }
        return $arr;
    } 
    
    /**
     * 添加规则
     * @return \Webman\Http\Response
     */
    public function add(){
        // 支持 plugin 参数
        $pluginId = $this->get['plugin'] ?? '';
        $iframe   = !empty($this->get['iframe']) ? 1 : 0;
        if ($pluginId !== '') {
            if ($err = $this->checkSuperAdmin()) return $err;
        }
        if($this->isPost()){
            try{
                validate([
                    'title'         =>  'require',
                    'pid'           =>  'require',
                    'sort'          =>  'require',
                ])->check($this->post);
                $this->post['plugin'] = $pluginId;
                $this->model::create($this->post);
            }catch(\Exception $e){
                return error($e->getMessage()?:'添加失败');
            }
            $back = 'index';
            $params = [];
            if ($pluginId) $params[] = 'plugin=' . $pluginId;
            if ($iframe)   $params[] = 'iframe=1';
            if ($params)   $back .= '?' . implode('&', $params);
            return success('添加成功', $back);
        }
        
        // 上级菜单只取同插件的菜单
        if ($pluginId !== '') {
            $rules = $this->model->where('plugin', $pluginId)->order('sort asc,id desc')->select();
        } else {
            $rules = $this->model->where('plugin', '')->order('sort asc,id desc')->select();
        }
        $menus = $this->recursion_title($rules);
        
        $pid = $this->get['pid'] ?? 0;
        $parentRule = $pid > 0 ? $this->model->find($pid) : null;
        
        return $this->view( [
            'menus'      => $menus,
            'pid'        => $pid,
            'parentRule' => $parentRule,
            'pluginId'   => $pluginId,
        ]);
    }
    
    /**
     * 编辑规则
     * @return \Webman\Http\Response
     */
    public function edit(){
        $rule     = $this->model->find($this->get['id']);
        $pluginId = $rule['plugin'] ?? '';
        $iframe   = !empty($this->get['iframe']) ? 1 : 0;
        if ($pluginId !== '') {
            if ($err = $this->checkSuperAdmin()) return $err;
        }
        if($this->isPost()){
            try{
                validate([
                    'title'         =>  'require',
                    'pid'           =>  'require',
                    'sort'          =>  'require',
                ])->check($this->post);
                $this->post['plugin'] = $pluginId;
                $rule->replace()->save($this->post);
            }catch(\Exception $e){
                return error($e->getMessage()?:'修改失败');
            }
            $back = 'index';
            $params = [];
            if ($pluginId) $params[] = 'plugin=' . $pluginId;
            if ($iframe)   $params[] = 'iframe=1';
            if ($params)   $back .= '?' . implode('&', $params);
            return success('修改成功', $back);
        }
        // 上级菜单只取同插件的菜单
        if ($pluginId !== '') {
            $rules = $this->model->where('plugin', $pluginId)->order('sort asc,id desc')->select();
        } else {
            $rules = $this->model->where('plugin', '')->order('sort asc,id desc')->select();
        }
        $menus = $this->recursion_title($rules);
        
        $parentRule = $rule['pid'] > 0 ? $this->model->find($rule['pid']) : null;
        
        return $this->view( [
            'rule'       => $rule, 
            'menus'      => $menus,
            'parentRule' => $parentRule,
            'pluginId'   => $pluginId,
        ]);
    }
    
    /**
     * 删除规则
     * @return \Webman\Http\Response
     */
    public function del(){
        if($this->isPost()){
            try{
                $rule = $this->model::find($this->post['id']);
                if(!$rule){
                    throw new \Exception('菜单不存在');
                }
                if($this->model::where(['pid'=>$rule['id']])->find()){
                    throw new \Exception('该菜单存在子节点');
                }
                $rule->delete();
            }catch(\Exception $e){
                return error($e->getMessage()?:'删除失败');
            }
            return success('删除成功');
        }
    }
}
