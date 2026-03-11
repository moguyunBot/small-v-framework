<?php
namespace app\admin\service;

/**
 * 框架升级服务
 * 从远端 version.json 获取最新版本信息，下载补丁包并执行升级
 */
class FrameworkUpgradeService
{
    /**
     * 获取当前框架版本
     */
    public function currentVersion(): string
    {
        return config('app.framework_version', '1.0.0');
    }

    /**
     * 从远端检查最新版本信息
     * 返回 ['version'=>'x.x.x','changelog'=>'...','download_url'=>'...','released_at'=>'...']
     */
    public function checkRemote(): array
    {
        $giteeUrl  = config('app.upgrade_check_url', 'https://gitee.com/4620337/small-v-framework/raw/main/version.json');
        $githubUrl = config('app.upgrade_check_url_github', 'https://raw.githubusercontent.com/moguyunBot/small-v-framework/main/version.json');

        // 根据服务器 IP 判断国内外，国内用 Gitee，国外用 GitHub
        $url = $this->isChinaServer() ? $giteeUrl : $githubUrl;

        $ctx = stream_context_create([
            'http' => [
                'timeout'         => 10,
                'method'          => 'GET',
                'follow_location' => 1,
                'user_agent'      => 'SmallV-Framework-Upgrader/1.0',
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        $json = @file_get_contents($url, false, $ctx);

        // 主地址失败，尝试另一个
        if ($json === false) {
            $fallbackUrl = ($url === $giteeUrl) ? $githubUrl : $giteeUrl;
            $json = @file_get_contents($fallbackUrl, false, $ctx);
        }

        if ($json === false) {
            throw new \Exception('无法连接到版本服务器，请检查网络');
        }

        $data = json_decode($json, true);
        if (!$data || !isset($data['version'])) {
            throw new \Exception('版本信息格式错误');
        }

        return $data;
    }

    /**
     * 判断当前服务器是否在中国大陆
     * 通过查询本机公网 IP 的归属地来判断
     */
    protected function isChinaServer(): bool
    {
        static $result = null;
        if ($result !== null) return $result;

        try {
            $ctx = stream_context_create(['http' => ['timeout' => 5], 'ssl' => ['verify_peer' => false]]);
            // 使用 ip-api.com 查询，免费且稳定
            $resp = @file_get_contents('http://ip-api.com/json/?fields=countryCode', false, $ctx);
            if ($resp) {
                $data = json_decode($resp, true);
                $result = isset($data['countryCode']) && $data['countryCode'] === 'CN';
                return $result;
            }
        } catch (\Exception $e) {}

        // 无法判断时默认使用 Gitee（大部分用户在国内）
        $result = true;
        return $result;
    }

    /**
     * 检查是否有新版本
     * 返回 ['upgradable'=>bool, 'current'=>'x', 'latest'=>'x', 'changelog'=>'', 'download_url'=>'']
     */
    public function checkUpgrade(): array
    {
        $current = $this->currentVersion();
        $remote  = $this->checkRemote();
        $latest  = $remote['version'];

        return [
            'upgradable'   => version_compare($latest, $current, '>'),
            'current'      => $current,
            'latest'       => $latest,
            'changelog'    => $remote['changelog']    ?? '',
            'download_url' => $remote['download_url'] ?? '',
            'released_at'  => $remote['released_at']  ?? '',
        ];
    }

    /**
     * 执行升级
     * 1. 下载补丁 zip
     * 2. 备份当前 app/ 到 runtime/backup/
     * 3. 解压覆盖文件
     * 4. 执行迁移 SQL（upgrade.sql）
     * 5. 更新 config/app.php 版本号
     */
    public function upgrade(string $downloadUrl, string $newVersion): array
    {
        $log = [];

        // 1. 下载补丁包
        $log[] = '正在下载补丁包...';
        $zipPath = runtime_path() . '/upgrade_' . $newVersion . '.zip';
        $this->download($downloadUrl, $zipPath);
        $log[] = '下载完成：' . $zipPath;

        // 2. 备份
        $log[] = '正在备份当前文件...';
        $backupPath = $this->backup($newVersion);
        $log[] = '备份完成：' . $backupPath;

        // 3. 解压覆盖
        $log[] = '正在解压并覆盖文件...';
        $extractLog = $this->extract($zipPath);
        $log = array_merge($log, $extractLog);

        // 4. 执行迁移 SQL
        $sqlFile = base_path('upgrade_' . $newVersion . '.sql');
        if (file_exists($sqlFile)) {
            $log[] = '正在执行数据库迁移...';
            $this->executeSqlFile($sqlFile);
            $log[] = '数据库迁移完成';
        } else {
            // 尝试根目录 upgrade.sql
            $sqlFile = base_path('upgrade.sql');
            if (file_exists($sqlFile)) {
                $log[] = '正在执行数据库迁移...';
                $this->executeSqlFile($sqlFile);
                $log[] = '数据库迁移完成';
            }
        }

        // 5. 更新版本号
        $log[] = '正在更新版本号...';
        $this->updateVersion($newVersion);
        $log[] = '版本号已更新为 ' . $newVersion;

        // 清理临时 zip
        @unlink($zipPath);

        return $log;
    }

    /**
     * 下载文件
     */
    protected function download(string $url, string $savePath): void
    {
        $ctx = stream_context_create([
            'http' => [
                'timeout'         => 120,
                'method'          => 'GET',
                'follow_location' => 1,
                'user_agent'      => 'SmallV-Framework-Upgrader/1.0',
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        $data = @file_get_contents($url, false, $ctx);
        if ($data === false) {
            throw new \Exception('下载失败，请检查网络或 download_url 配置');
        }

        if (!is_dir(dirname($savePath))) {
            mkdir(dirname($savePath), 0755, true);
        }

        file_put_contents($savePath, $data);
    }

    /**
     * 备份 app/ 目录
     */
    protected function backup(string $version): string
    {
        $backupDir = runtime_path() . '/backup/v' . $version . '_' . date('YmdHis');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // 备份 app/ 目录
        $this->copyDir(base_path('app'), $backupDir . '/app');

        return $backupDir;
    }

    /**
     * 解压 zip 并覆盖到根目录
     */
    protected function extract(string $zipPath): array
    {
        if (!class_exists('ZipArchive')) {
            throw new \Exception('ZipArchive 扩展未安装，无法解压');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \Exception('无法打开 zip 文件');
        }

        $log   = [];
        $base  = base_path();
        $count = $zip->numFiles;

        for ($i = 0; $i < $count; $i++) {
            $name = $zip->getNameIndex($i);
            // 跳过目录条目
            if (substr($name, -1) === '/') continue;
            // 跳过 config/app.php（不覆盖用户配置）
            if ($name === 'config/app.php') continue;

            $target = $base . '/' . $name;
            $dir    = dirname($target);

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($target, $zip->getFromIndex($i));
            $log[] = '覆盖：' . $name;
        }

        $zip->close();
        return $log;
    }

    /**
     * 执行 SQL 文件
     */
    protected function executeSqlFile(string $sqlFile): void
    {
        $sql = file_get_contents($sqlFile);
        if (empty(trim($sql))) return;
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => $s !== ''
        );
        foreach ($statements as $statement) {
            \think\facade\Db::execute($statement);
        }
    }

    /**
     * 更新 config/app.php 中的版本号
     */
    protected function updateVersion(string $newVersion): void
    {
        $file    = base_path('config/app.php');
        $content = file_get_contents($file);
        $content = preg_replace(
            "/'framework_version'\s*=>\s*'[^']*'/",
            "'framework_version' => '{$newVersion}'",
            $content
        );
        file_put_contents($file, $content);
    }

    /**
     * 递归复制目录
     */
    protected function copyDir(string $src, string $dst): void
    {
        if (!is_dir($src)) return;
        if (!is_dir($dst)) mkdir($dst, 0755, true);

        foreach (array_diff(scandir($src), ['.', '..']) as $item) {
            $s = $src . '/' . $item;
            $d = $dst . '/' . $item;
            is_dir($s) ? $this->copyDir($s, $d) : copy($s, $d);
        }
    }
}
