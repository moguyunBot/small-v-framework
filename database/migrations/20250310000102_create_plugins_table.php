<?php
/**
 * 创建插件管理表
 */

use think\migration\Migrator;
use think\migration\db\Column;

class CreatePluginsTable20250310000102 extends Migrator
{
    public function change()
    {
        $table = $this->table('plugins', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('identifier', 'string', [
            'limit'   => 50,
            'null'    => false,
            'comment' => '插件唯一标识（目录名）',
        ])
        ->addColumn('name', 'string', [
            'limit'   => 100,
            'null'    => false,
            'comment' => '插件名称',
        ])
        ->addColumn('version', 'string', [
            'limit'   => 20,
            'default' => '1.0.0',
            'comment' => '版本号',
        ])
        ->addColumn('author', 'string', [
            'limit'   => 50,
            'default' => '',
            'comment' => '作者',
        ])
        ->addColumn('description', 'text', [
            'null'    => true,
            'comment' => '插件描述',
        ])
        ->addColumn('icon', 'string', [
            'limit'   => 50,
            'default' => 'mdi mdi-puzzle',
            'comment' => '菜单图标',
        ])
        ->addColumn('status', 'boolean', [
            'default' => 0,
            'comment' => '0=禁用, 1=启用',
        ])
        ->addColumn('is_installed', 'boolean', [
            'default' => 0,
            'comment' => '是否已安装',
        ])
        ->addColumn('install_time', 'datetime', [
            'null'    => true,
            'comment' => '安装时间',
        ])
        ->addColumn('config', 'json', [
            'null'    => true,
            'comment' => '插件配置',
        ])
        ->addColumn('create_time', 'datetime', [
            'default' => 'CURRENT_TIMESTAMP',
        ])
        ->addColumn('update_time', 'datetime', [
            'default' => 'CURRENT_TIMESTAMP',
            'update'  => 'CURRENT_TIMESTAMP',
        ])
        ->addIndex(['identifier'], ['unique' => true, 'name' => 'uk_identifier'])
        ->addIndex(['status'], ['name' => 'idx_status'])
        ->addIndex(['is_installed'], ['name' => 'idx_installed'])
        ->create();
    }
    
    public function down()
    {
        $this->table('plugins')->drop();
    }
}
