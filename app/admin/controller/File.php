<?php
namespace app\admin\controller;

/**
 * 文件管理器
 */
class File extends Base
{
    protected $noNeedAuth = ['index'];

    /**
     * 文件列表
     */
    public function index()
    {
        $type    = $this->get['type'] ?? 'images';
        $page    = max(1, (int)($this->get['page'] ?? 1));
        $perPage = 60;
        $allowed = ['images', 'videos', 'files'];
        if (!in_array($type, $allowed)) $type = 'images';

        $baseDir = public_path() . '/uploads/' . $type;
        $files   = [];

        if (is_dir($baseDir)) {
            // 递归扫描所有文件
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files[] = [
                        'path'  => '/uploads/' . $type . '/' . ltrim(str_replace($baseDir, '', $file->getPathname()), '/\\'),
                        'name'  => $file->getFilename(),
                        'size'  => $file->getSize(),
                        'mtime' => $file->getMTime(),
                        'ext'   => strtolower($file->getExtension()),
                    ];
                }
            }
        }

        // 按时间倒序
        usort($files, fn($a, $b) => $b['mtime'] - $a['mtime']);

        $total  = count($files);
        $offset = ($page - 1) * $perPage;
        $list   = array_slice($files, $offset, $perPage);

        // 格式化大小
        foreach ($list as &$f) {
            $f['size_format'] = $this->formatBytes($f['size']);
            $f['mtime_format'] = date('Y-m-d H:i', $f['mtime']);
        }
        unset($f);

        $totalPages = (int)ceil($total / $perPage);

        return $this->view([
            'files'      => $list,
            'type'       => $type,
            'page'       => $page,
            'total'      => $total,
            'totalPages' => $totalPages,
        ]);
    }

    /**
     * 删除文件
     */
    public function del()
    {
        if (!$this->isPost()) return error('非法请求');
        $path = $this->post['path'] ?? '';

        // 安全检查：只允许删除 uploads 目录下的文件
        if (!preg_match('#^/uploads/(images|videos|files)/#', $path)) {
            return error('非法路径');
        }

        $fullPath = public_path() . $path;
        if (!file_exists($fullPath)) {
            return error('文件不存在');
        }

        unlink($fullPath);
        return success('删除成功');
    }

    /**
     * 批量删除
     */
    public function batchDel()
    {
        if (!$this->isPost()) return error('非法请求');
        $paths = $this->post['paths'] ?? [];
        if (empty($paths) || !is_array($paths)) return error('请选择文件');

        $deleted = 0;
        foreach ($paths as $path) {
            if (!preg_match('#^/uploads/(images|videos|files)/#', $path)) continue;
            $fullPath = public_path() . $path;
            if (file_exists($fullPath)) {
                unlink($fullPath);
                $deleted++;
            }
        }

        // 清理空目录
        $this->cleanEmptyDirs(public_path() . '/uploads');

        return success("已删除 {$deleted} 个文件");
    }

    /**
     * 获取统计信息（AJAX）
     */
    public function stats()
    {
        $types = ['images', 'videos', 'files'];
        $stats = [];
        foreach ($types as $type) {
            $dir = public_path() . '/uploads/' . $type;
            $count = 0;
            $size  = 0;
            if (is_dir($dir)) {
                $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
                foreach ($it as $f) {
                    if ($f->isFile()) { $count++; $size += $f->getSize(); }
                }
            }
            $stats[$type] = ['count' => $count, 'size' => $this->formatBytes($size)];
        }
        return json(['code' => 0, 'data' => $stats]);
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < 3; $i++) $bytes /= 1024;
        return round($bytes, 2) . ' ' . $units[$i];
    }

    protected function cleanEmptyDirs(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (glob($dir . '/*', GLOB_ONLYDIR) as $subDir) {
            $this->cleanEmptyDirs($subDir);
            if (count(glob($subDir . '/*')) === 0) rmdir($subDir);
        }
    }
}
