<?php
namespace app\controller;

use support\Request;
use support\Db;

/**
 * 前台首页控制器
 */
class Index
{
    /**
     * 首页 - 插件市场
     * @param Request $request
     * @return \support\Response
     */
    public function index(Request $request)
    {
        // 获取所有插件
        $plugins = $this->getPlugins();
        
        return view('index/index', [
            'plugins' => $plugins
        ]);
    }
    
    /**
     * 获取插件列表
     * @return array
     */
    protected function getPlugins()
    {
        // 这里可以从数据库读取插件列表
        // 暂时返回示例数据
        return [
            [
                'id' => 1,
                'name' => 'AI 对话助手',
                'icon' => 'mdi-robot',
                'color' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'description' => '基于 GPT 的智能对话助手，支持多轮对话',
                'category' => 'AI工具',
                'url' => '/plugin/chat',
                'status' => 'active'
            ],
            [
                'id' => 2,
                'name' => '图片处理',
                'icon' => 'mdi-image-edit',
                'color' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                'description' => '在线图片编辑、压缩、格式转换工具',
                'category' => '图像工具',
                'url' => '/plugin/image',
                'status' => 'active'
            ],
            [
                'id' => 3,
                'name' => '代码生成器',
                'icon' => 'mdi-code-tags',
                'color' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                'description' => '快速生成常用代码模板和框架',
                'category' => '开发工具',
                'url' => '/plugin/codegen',
                'status' => 'active'
            ],
            [
                'id' => 4,
                'name' => '文档转换',
                'icon' => 'mdi-file-document',
                'color' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                'description' => '支持 PDF、Word、Excel 等格式互转',
                'category' => '办公工具',
                'url' => '/plugin/convert',
                'status' => 'active'
            ],
            [
                'id' => 5,
                'name' => '数据分析',
                'icon' => 'mdi-chart-line',
                'color' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                'description' => '可视化数据分析和报表生成',
                'category' => '数据工具',
                'url' => '/plugin/analytics',
                'status' => 'active'
            ],
            [
                'id' => 6,
                'name' => 'API 测试',
                'icon' => 'mdi-api',
                'color' => 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
                'description' => '强大的 API 接口测试工具',
                'category' => '开发工具',
                'url' => '/plugin/api-test',
                'status' => 'active'
            ],
            [
                'id' => 7,
                'name' => '视频处理',
                'icon' => 'mdi-video',
                'color' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
                'description' => '视频剪辑、压缩、格式转换',
                'category' => '媒体工具',
                'url' => '/plugin/video',
                'status' => 'coming'
            ],
            [
                'id' => 8,
                'name' => '区块链工具',
                'icon' => 'mdi-bitcoin',
                'color' => 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
                'description' => '区块链数据查询和分析工具',
                'category' => '区块链',
                'url' => '/plugin/blockchain',
                'status' => 'coming'
            ],
        ];
    }
}
