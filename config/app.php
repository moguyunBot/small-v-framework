<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use support\Request;

return [
    'debug' => true,
    'error_reporting' => E_ALL,
    'default_timezone' => 'Asia/Shanghai',
    'request_class' => \support\Request::class,
    'public_path' => base_path() . DIRECTORY_SEPARATOR . 'public',
    'runtime_path' => base_path(false) . DIRECTORY_SEPARATOR . 'runtime',
    'controller_suffix' => '',
    'controller_reuse' => false,
    // 框架版本号
    'framework_version' => '1.0.0',
    // 远端版本检查地址（Gitee raw 文件）
    'upgrade_check_url' => 'https://gitee.com/4620337/small-v-framework/raw/main/version.json',
    'upgrade_check_url_github' => 'https://raw.githubusercontent.com/moguyunBot/small-v-framework/main/version.json',
];
