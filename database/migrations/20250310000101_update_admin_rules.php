<?php
/**
 * 扩展 admin_rules 表，支持插件菜单
 */

use think\migration\Migrator;
use think\migration\db\Column;

class UpdateAdminRules20250310000101 extends Migrator
{
    public function change()
    {
        $table = $this->table('admin_rules');
        
        // 增加插件相关字段（如果字段不存在）
        if (!$table->hasColumn('type')) {
            $table->addColumn('type', 'string', [
                'limit'   => 20,
                'default' => 'system',
                'comment' => '菜单类型: system=系统, plugin=插件',
                'after'   => 'status',
            ])->update();
        }
        
        if (!$table->hasColumn('plugin')) {
            $table->addColumn('plugin', 'string', [
                'limit'   => 50,
                'default' => null,
                'null'    => true,
                'comment' => '插件标识，系统菜单为空',
                'after'   => 'type',
            ])->update();
        }
        
        if (!$table->hasColumn('is_system')) {
            $table->addColumn('is_system', 'boolean', [
                'default' => 1,
                'comment' => '1=系统内置（不可删），0=插件添加',
                'after'   => 'plugin',
            ])->update();
        }
        
        // 添加索引
        if (!$table->hasIndex(['plugin'])) {
            $table->addIndex(['plugin'], ['name' => 'idx_plugin']);
        }
        if (!$table->hasIndex(['type'])) {
            $table->addIndex(['type'], ['name' => 'idx_type']);
        }
    }
    
    public function down()
    {
        $table = $this->table('admin_rules');
        
        if ($table->hasColumn('type')) {
            $table->removeColumn('type');
        }
        if ($table->hasColumn('plugin')) {
            $table->removeColumn('plugin');
        }
        if ($table->hasColumn('is_system')) {
            $table->removeColumn('is_system');
        }
        
        $table->update();
    }
}
