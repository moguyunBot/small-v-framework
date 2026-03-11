<?php
namespace app\admin\controller;

use app\admin\service\FrameworkUpgradeService;

/**
 * 系统框架升级控制器
 */
class Upgrade extends Base
{
    public function index()
    {
        $service = new FrameworkUpgradeService();

        if ($this->isPost()) {
            $action = $this->post['action'] ?? '';
            try {
                if ($action === 'check') {
                    $info = $service->checkUpgrade();
                    return json(['code' => 0, 'data' => $info]);
                }
                if ($action === 'do_upgrade') {
                    $downloadUrl = $this->post['download_url'] ?? '';
                    $newVersion  = $this->post['new_version']  ?? '';
                    if (!$downloadUrl || !$newVersion) {
                        return error('参数错误');
                    }
                    $log = $service->upgrade($downloadUrl, $newVersion);
                    return json(['code' => 1, 'msg' => '升级成功', 'data' => ['log' => $log]]);
                }
                return error('未知操作');
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
        }

        $currentVersion = $service->currentVersion();
        return $this->view(['current_version' => $currentVersion]);
    }
}
