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
        return [
            [
                'id' => 1,
                'name' => 'IPv6 连接测试',
                'icon' => 'mdi-network',
                'color' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'description' => '测试您的网络 IPv4/IPv6 连接性能，DNS解析和速度测试',
                'category' => '网络工具',
                'url' => '/app/ipv6test/index',
                'status' => 'active'
            ],
            [
                'id' => 2,
                'name' => '网络工具箱',
                'icon' => 'mdi-lan',
                'color' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'description' => 'Ping测试、DNS查询、Whois查询、端口扫描',
                'category' => '网络工具',
                'url' => '/app/nettools/index',
                'status' => 'active'
            ],
            [
                'id' => 3,
                'name' => '编码解码工具',
                'icon' => 'mdi-code-braces',
                'color' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                'description' => 'Base64、URL编码解码、MD5/SHA哈希、JSON格式化',
                'category' => '开发工具',
                'url' => '/app/encoder/index',
                'status' => 'active'
            ],
            [
                'id' => 4,
                'name' => '文本处理工具',
                'icon' => 'mdi-text-box',
                'color' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                'description' => '文本对比、字数统计、大小写转换、文本去重',
                'category' => '办公工具',
                'url' => '/app/texttools/index',
                'status' => 'active'
            ],
            [
                'id' => 5,
                'name' => '开发者工具',
                'icon' => 'mdi-code-tags',
                'color' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                'description' => '时间戳转换、UUID生成、随机密码、颜色生成器',
                'category' => '开发工具',
                'url' => '/app/devtools/index',
                'status' => 'active'
            ],
            [
                'id' => 6,
                'name' => '图片工具',
                'icon' => 'mdi-image-edit',
                'color' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                'description' => '二维码生成、图片处理工具',
                'category' => '图像工具',
                'url' => '/app/imagetools/index',
                'status' => 'active'
            ],
            [
                'id' => 7,
                'name' => '实用工具',
                'icon' => 'mdi-toolbox',
                'color' => 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
                'description' => 'IP地址查询、User-Agent解析等实用工具',
                'category' => '网络工具',
                'url' => '/app/utiltools/index',
                'status' => 'active'
            ],
            [
                'id' => 8,
                'name' => '区块链靓号生成器',
                'icon' => 'mdi-bitcoin',
                'color' => 'linear-gradient(135deg, #f7931a 0%, #9945ff 100%)',
                'description' => '支持 BTC/ETH/TRON/LTC/DOGE/SOL/XRP/XLM/ADA/DOT/ATOM/APT 等12条主流区块链靓号生成，完全本地运行',
                'category' => '区块链工具',
                'url' => '/app/vanity/index',
                'status' => 'active'
            ],
        ];
    }
}
