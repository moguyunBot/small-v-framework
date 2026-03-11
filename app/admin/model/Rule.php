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
    public static function recursion($list, $pid = 0, $currentPath = '')
    {
        $html = '';
        foreach ($list as $v) {
            if ($v['pid'] != $pid) continue;
            if ($v['is_menu'] == 0) continue;

            // 判断当前菜单是否 active
            $matched = $currentPath && strcasecmp($currentPath, $v['href']) === 0;
            if ($matched) {
                if (!empty($v['options'][0]['key']) && !empty($v['options'][0]['value'])) {
                    $param = array_column($v['options'], 'value', 'key');
                    $arr1  = array_intersect($param, request()->get());
                    $v['active'] = (count($arr1) && count(array_diff($arr1, $param)) == 0) ? 'active' : '';
                } else {
                    $v['active'] = 'active';
                }
            } else {
                $v['active'] = '';
            }

            // 递归生成子菜单 HTML
            $sonHtml = self::recursion($list, $v['id'], $currentPath);

            if ($sonHtml === '') {
                $html .= '<li class="nav-item ' . $v['active'] . '"><a href="' . $v['href'] . '"><i class="' . $v['icon'] . '"></i> <span>' . $v['title'] . '</span></a></li>';
            } else {
                $isOpen = str_contains($sonHtml, 'active') ? 'active open' : '';
                $icon   = $v['icon'] ?: 'iconfont icon-xitongshezhi';
                $html  .= '<li class="nav-item nav-item-has-subnav ' . $isOpen . '"><a href="javascript:void(0)"><i class="' . $icon . '"></i> <span>' . $v['title'] . '</span></a><ul class="nav nav-subnav">' . $sonHtml . '</ul></li>';
            }
        }

        return $html;
    }
    
}
