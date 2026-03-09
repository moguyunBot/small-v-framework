<?php
namespace app\admin\model;

/**
 * 权限规则模型
 */
class Rule extends \think\Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'admin_rules';
    
    /**
     * 自动时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;
    
    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'create_time';
    
    /**
     * 更新时间字段
     * @var string
     */
    protected $updateTime = 'update_time';
    
    /**
     * 递归生成菜单树
     * @param array $list 规则列表
     * @param int $pid 父级ID
     * @return array
     */
    public static function recursion($list, $pid = 0)
    {
        $request = request();
        $controller = class_basename($request->controller);
        $action = $request->action;
        
        $arr = [];
        foreach ($list as $v) {
            if ($v['pid'] == $pid) {
                if ('/admin/'.$controller.'/'.$action==$v['href']) {
                    if(!empty($v['options'][0]['key'])&&!empty($v['options'][0]['value'])){
                        $param = [];
                        foreach($v['options'] as $v1){
                            $param[$v1['key']] = $v1['value'];
                        }
                        //对比交集
                        $arr1 = array_intersect($param,request()->get());
                        //必须有交集//并且没有差集才是选中状态
                        if(count($arr1)&&count(array_diff($arr1,$param))==0){
                            $v['active'] = 'active';
                        }else{
                            $v['active'] = '';
                        }
                    }else{
                        $v['active'] = 'active';
                    }
                } else {
                    $v['active'] = '';
                }
                $v['son'] = self::recursion($list, $v['id']);
                $actives = array_column($v['son'], 'active');
                if (in_array('active', $actives)) {
                    $v['active'] = 'active';
                }
                $arr[] = $v;
            }
        }
        
        return $arr;
    }
    
    /**
     * 递归生成菜单HTML
     * @param array $list 规则列表
     * @return string
     */
    public static function recursion_menu($list)
    {
        $html = '';
        foreach ($list as $v) {
            $actives = array_column($v['son'], 'active');
            if (in_array('active', $actives)) {
                $v['active'] = 'active';
            }
            $v['son'] = array_filter($v['son'], function ($rule) {
                if ($rule['is_menu']==1) {
                    return $rule;
                }
            });
            if($v['is_menu']==0)continue;
            if (count($v['son']) == 0) {
                $html .= '<li class="nav-item '.($v['active']?:'').'"> <a href="'.$v['href'].'"><i class="'.$v['icon'].'"></i> <span>'.$v['title'].'</span></a> </li>';
            } else {
                $html .= '<li class="nav-item nav-item-has-subnav '.($v['active']?'active open':'').'"><a href="javascript:void(0)"><i class="'.($v['icon']?:'iconfont icon-xitongshezhi').'"></i> <span>'.$v['title'].'</span></a><ul class="nav nav-subnav">'.static::recursion_menu($v['son']).'</ul></li>';
            }
        }
        return $html;
    }
}
