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
    
    /**
     * 获取系统权限树（不包含插件）
     */
    public static function getSystemRules(): array
    {
        return self::where('type', 'system')
            ->where('status', 1)
            ->order('sort asc, id asc')
            ->select()
            ->toArray();
    }
    
    /**
     * 获取插件权限（按插件分组）
     */
    public static function getPluginRules(): array
    {
        $rules = self::where('type', 'plugin')
            ->where('status', 1)
            ->order('plugin asc, sort asc, id asc')
            ->select()
            ->toArray();
        
        $grouped = [];
        foreach ($rules as $rule) {
            $plugin = $rule['plugin'] ?? 'other';
            if (!isset($grouped[$plugin])) {
                $grouped[$plugin] = [
                    'name'  => $rule['plugin_name'] ?? $plugin,
                    'icon'  => 'mdi mdi-puzzle',
                    'rules' => [],
                ];
            }
            $grouped[$plugin]['rules'][] = $rule;
        }
        
        return $grouped;
    }
    
    /**
     * 检查是否有指定权限
     * @param int $ruleId 规则ID
     * @param array $roleIds 角色ID列表
     */
    public static function checkPermission(int $ruleId, array $roleIds): bool
    {
        if (empty($roleIds)) {
            return false;
        }
        
        // 获取角色的所有权限
        $rules = Role::whereIn('id', $roleIds)->column('rules');
        $allRuleIds = [];
        
        foreach ($rules as $ruleString) {
            if (empty($ruleString)) {
                continue;
            }
            // 超级管理员
            if ($ruleString === '*' || in_array('*', explode(',', $ruleString))) {
                return true;
            }
            $allRuleIds = array_merge($allRuleIds, explode(',', $ruleString));
        }
        
        return in_array($ruleId, $allRuleIds);
    }
    
    /**
     * 根据路径查找权限规则
     */
    public static function findByPath(string $path): ?self
    {
        return self::where('href', $path)
            ->where('status', 1)
            ->find();
    }
}
