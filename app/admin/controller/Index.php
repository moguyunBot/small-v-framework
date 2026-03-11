<?php
namespace app\admin\controller;

use Webman\Captcha\CaptchaBuilder;
use Webman\Captcha\PhraseBuilder;
use app\admin\model\Admin;
use app\admin\model\AdminLoginLog;

/**
 * 后台首页控制器
 */
class Index extends Base{
    protected $noNeedLogin = ['login','captcha'];
    
    /**
     * 后台首页
     * @return \Webman\Http\Response
     */
    public function index(){
        $systemData = $this->getSystemMonitorData();
        return $this->view(['systemData' => $systemData]);
    }
    
    /**
     * 获取系统数据（AJAX）
     * @return mixed
     */
    public function getSystemData()
    {
        $data = $this->getSystemMonitorData();
        return json(['code' => 0, 'data' => $data]);
    }
    
    /**
     * 获取系统监控数据
     * @return array
     */
    protected function getSystemMonitorData()
    {
        $data = [];
        
        // 内存信息
        $data['memory'] = $this->getMemoryInfo();
        
        // CPU 信息
        $data['cpu'] = $this->getCpuInfo();
        
        // 磁盘信息
        $data['disk'] = $this->getDiskInfo();
        
        // PHP 信息
        $data['php'] = [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
        ];
        
        // 数据库信息
        $data['database'] = $this->getDatabaseInfo();
        
        return $data;
    }
    
    /**
     * 获取内存信息
     * @return array
     */
    protected function getMemoryInfo()
    {
        $memory = [];
        $memory['php_used'] = memory_get_usage(true);
        $memory['php_used_format'] = $this->formatBytes($memory['php_used']);
        $memory['php_limit'] = ini_get('memory_limit');
        
        if (PHP_OS === 'Linux' && file_exists('/proc/meminfo')) {
            $meminfo = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
            preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);
            
            if (isset($total[1]) && isset($available[1])) {
                $memory['system_total'] = $total[1] * 1024;
                $memory['system_available'] = $available[1] * 1024;
                $memory['system_used'] = $memory['system_total'] - $memory['system_available'];
                $memory['system_usage_percent'] = round(($memory['system_used'] / $memory['system_total']) * 100, 2);
                $memory['system_total_format'] = $this->formatBytes($memory['system_total']);
                $memory['system_used_format'] = $this->formatBytes($memory['system_used']);
            }
        }
        
        return $memory;
    }
    
    /**
     * 获取 CPU 信息
     * @return array
     */
    protected function getCpuInfo()
    {
        $cpu = [];
        if (PHP_OS === 'Linux') {
            $loadavg = sys_getloadavg();
            $cpu['load_1min'] = round($loadavg[0], 2);
            $cpu['load_5min'] = round($loadavg[1], 2);
            $cpu['load_15min'] = round($loadavg[2], 2);
        }
        return $cpu;
    }
    
    /**
     * 获取磁盘信息
     * @return array
     */
    protected function getDiskInfo()
    {
        $disk = [];
        $disk['total'] = disk_total_space('/');
        $disk['free'] = disk_free_space('/');
        $disk['used'] = $disk['total'] - $disk['free'];
        $disk['usage_percent'] = round(($disk['used'] / $disk['total']) * 100, 2);
        $disk['total_format'] = $this->formatBytes($disk['total']);
        $disk['used_format'] = $this->formatBytes($disk['used']);
        $disk['free_format'] = $this->formatBytes($disk['free']);
        return $disk;
    }
    
    /**
     * 获取数据库信息
     * @return array
     */
    protected function getDatabaseInfo()
    {
        $db = [];
        try {
            $version = \think\facade\Db::query('SELECT VERSION() as version');
            $db['version'] = $version[0]['version'] ?? 'Unknown';
            
            // 获取数据库名
            $dbConfig = config('database.connections.mysql');
            $dbName = $dbConfig['database'] ?? 'www_xvv_cc';
            
            // 表数量
            $tables = \think\facade\Db::query("SELECT COUNT(*) as count FROM information_schema.TABLES WHERE table_schema = '{$dbName}'");
            $db['tables_count'] = $tables[0]['count'] ?? 0;
            
            // 数据库大小
            $size = \think\facade\Db::query("SELECT SUM(data_length + index_length) as size FROM information_schema.TABLES WHERE table_schema = '{$dbName}'");
            $db['size'] = $size[0]['size'] ?? 0;
            $db['size_format'] = $this->formatBytes($db['size']);
            
        } catch (\Exception $e) {
            $db['error'] = true;
            $db['tables_count'] = 0;
            $db['size_format'] = 'N/A';
        }
        return $db;
    }
    
    /**
     * 格式化字节大小
     * @param int $bytes 字节数
     * @param int $precision 精度
     * @return string
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * 登录页面
     * @return \Webman\Http\Response
     */
    public function login(){
        $session = $this->request->session();
        if($this->request->method()=='POST'){
            try{
                validate([
                    'username|用户名'       =>  'require',
                    'password|密码'         =>  'require',
                    'captcha|验证码'        =>  'require',
                ])->check($this->post);
                if($this->post['captcha']!=session('captcha')){
                    throw new \Exception('验证码错误');
                }
                $admin = Admin::where(['username'=>$this->post['username']])->find();
                if(!$admin){
                    throw new \Exception('管理员不存在或密码错误1');
                }
                if(!password_verify($this->post['password'], $admin['password'])){
                    throw new \Exception('管理员不存在或密码错误2');
                }
                if($admin['status']!=1){
                    throw new \Exception('该管理员已禁用');
                }
                AdminLoginLog::record($this->post['username'], $admin['id'], 1, '登录成功');
                $session->set('admin',$admin->toArray());
            }catch(\Exception $e){
                $username = $this->post['username'] ?? '';
                AdminLoginLog::record($username, 0, 0, $e->getMessage());
                $this->captcha();
                return error($e->getMessage()?:'登录失败');
            }
            return success('登录成功','index');
        }
        $session->delete('admin');
        return $this->view();
    }
    
    /**
     * 生成验证码
     * @return \Webman\Http\Response
     */
    public function captcha()
    {
        $builder = new PhraseBuilder(4, 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ');
        $captcha = new CaptchaBuilder(null, $builder);
        $captcha->build(120);
        $this->request->session()->set("captcha", strtolower($captcha->getPhrase()));
        $img_content = $captcha->get();
        return response($img_content, 200, ['Content-Type' => 'image/jpeg']);
    }
}