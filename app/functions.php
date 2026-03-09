<?php
/**
 * Here is your custom functions.
 */

use support\Response;
use app\admin\model\{Admin,AdminRole};

/**
 * 页面跳转提示
 * @param string $msg 提示信息
 * @param string $url 跳转地址，为空则不跳转
 * @param int $wait 等待时间（秒）
 * @param string $type 类型：success/error/info
 * @param string $title 标题
 * @return Response
 */
function jump($msg = '', $url = '', $wait = 3, $type = 'success', $title = '')
{
    if (empty($title)) {
        $title = $type === 'error' ? '操作失败' : '操作成功';
    }
    
    $data = [
        'msg' => $msg,
        'url' => $url,
        'wait' => $wait,
        'type' => $type,
        'title' => $title
    ];
    
    return view('/support/view/jump', $data);
}

/**
 * 成功提示
 * @param string $msg 提示信息
 * @param string $url 跳转地址
 * @param mixed $data 返回数据
 * @param int $wait 等待时间（秒）
 * @return Response
 */
function success($msg = '操作成功', $url = '', $data = '', $wait = 2)
{
    $result = [
        'code' => 1,
        'msg' => $msg,
        'data' => $data,
        'url' => $url,
        'wait' => $wait,
    ];
    
    $request = request();
    // 判断是否为 AJAX 请求或 POST 请求
    $isAjax = $request->header('X-Requested-With') === 'XMLHttpRequest' 
              || $request->isAjax() 
              || $request->method() === 'POST';
    
    if ($isAjax) {
        return json($result);
    } else {
        return jump($msg, $url, $wait, 'success', '操作成功');
    }
}

/**
 * 错误提示
 * @param string $msg 提示信息
 * @param string $url 跳转地址
 * @param mixed $data 返回数据
 * @param int $wait 等待时间（秒）
 * @return Response
 */
function error($msg = '操作失败', $url = '', $data = '', $wait = 2)
{
    $result = [
        'code' => 0,
        'msg' => $msg,
        'data' => $data,
        'url' => $url,
        'wait' => $wait,
    ];
    
    $request = request();
    // 判断是否为 AJAX 请求或 POST 请求
    $isAjax = $request->header('X-Requested-With') === 'XMLHttpRequest' 
              || $request->isAjax() 
              || $request->method() === 'POST';
    
    if ($isAjax) {
        return json($result);
    } else {
        return jump($msg, $url, $wait, 'error', '操作失败');
    }
}

function admin($fields = null)
{
    refresh_admin_session();
    if (!$admin = session('admin')) {
        return null;
    }
    if ($fields === null) {
        return $admin;
    }
    if (is_array($fields)) {
        $results = [];
        foreach ($fields as $field) {
            $results[$field] = $admin[$field] ?? null;
        }
        return $results;
    }
    return $admin[$fields] ?? null;
}

/**
 * 刷新当前管理员session
 * @param bool $force
 * @return void
 * @throws Exception
 */
function refresh_admin_session(bool $force = false)
{
    $admin_session = session('admin');
    if (!$admin_session) {
        return null;
    }
    $admin_id = $admin_session['id'];
    $time_now = time();
    // session在2秒内不刷新
    $session_ttl = 2;
    $session_last_update_time = session('admin.session_last_update_time', 0);
    if (!$force && $time_now - $session_last_update_time < $session_ttl) {
        return null;
    }
    $session = request()->session();
    $admin = Admin::find($admin_id);
    if (!$admin) {
        $session->forget('admin');
        return null;
    }
    $admin = $admin->toArray();
    // 账户被禁用
    if ($admin['status'] != 1) {
        $session->forget('admin');
        return;
    }
    $admin['roles'] = AdminRole::where('admin_id', $admin_id)->column('role_id');
    $admin['session_last_update_time'] = $time_now;
    $session->set('admin', $admin);
}

function admin_error_401_script()
{
  return response(<<<EOF
<script>top.location.href = '/admin/index/login';</script>
EOF
  );
}

/**
 * 文件上传函数
 * @param mixed $file 上传的文件对象
 * @return string 返回上传后的文件路径
 * @throws Exception
 */
function upload($file)
{
    if (!$file || !$file->isValid()) {
        throw new Exception('无效的文件');
    }
    
    // 获取文件扩展名
    $ext = strtolower($file->getUploadExtension());
    
    // 允许的图片格式
    $allowedImages = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic', 'heif'];
    // 允许的视频格式
    $allowedVideos = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'];
    // 允许的文件格式
    $allowedFiles = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];
    
    // 危险的扩展名黑名单
    $dangerousExt = ['php', 'php3', 'php4', 'php5', 'phtml', 'pht', 'jsp', 'asp', 'aspx', 
                     'sh', 'bash', 'cgi', 'pl', 'py', 'rb', 'exe', 'dll', 'so', 'bat', 'cmd'];
    
    $allowedExtensions = array_merge($allowedImages, $allowedVideos, $allowedFiles);
    
    // 检查扩展名
    if (!in_array($ext, $allowedExtensions) || in_array($ext, $dangerousExt)) {
        throw new Exception('不支持的文件格式: ' . $ext);
    }
    
    // 获取文件 MIME 类型
    $mimeType = $file->getUploadMimeType();
    
    // 验证 MIME 类型（防止伪造扩展名）
    $allowedMimes = [
        // 图片
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp',
        'image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence',
        // 视频
        'video/mp4', 'video/avi', 'video/quicktime', 'video/x-msvideo', 'video/x-flv', 
        'video/x-matroska', 'video/webm',
        // 文档
        'application/pdf', 'application/msword', 'application/vnd.ms-excel', 
        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument',
        'text/plain', 'application/zip', 'application/x-rar-compressed'
    ];
    
    $mimeValid = false;
    foreach ($allowedMimes as $allowedMime) {
        if (strpos($mimeType, $allowedMime) === 0) {
            $mimeValid = true;
            break;
        }
    }
    
    if (!$mimeValid) {
        throw new Exception('文件类型不匹配: ' . $mimeType);
    }
    
    // 检查文件内容（防止木马伪装）
    $tmpPath = $file->getPathname();
    
    // 检查文件头（魔术数字）
    if (in_array($ext, $allowedImages)) {
        if (!checkImageHeader($tmpPath, $ext)) {
            throw new Exception('图片文件头验证失败');
        }
    }
    
    // 扫描文件内容中的危险代码
    $content = file_get_contents($tmpPath, false, null, 0, 8192); // 只读取前8KB
    $dangerousPatterns = [
        '/<\?php/i',
        '/<\?=/i',
        '/<script/i',
        '/eval\s*\(/i',
        '/base64_decode/i',
        '/exec\s*\(/i',
        '/system\s*\(/i',
        '/passthru\s*\(/i',
        '/shell_exec/i',
        '/assert\s*\(/i',
        '/preg_replace.*\/e/i',
        '/create_function/i',
    ];
    
    foreach ($dangerousPatterns as $pattern) {
        if (preg_match($pattern, $content)) {
            throw new Exception('文件包含危险代码');
        }
    }
    
    // 检查文件大小（最大 50MB）
    $maxSize = 50 * 1024 * 1024;
    if ($file->getSize() > $maxSize) {
        throw new Exception('文件大小超过限制');
    }
    
    // 确定上传目录
    if (in_array($ext, $allowedImages)) {
        $subDir = 'images';
    } elseif (in_array($ext, $allowedVideos)) {
        $subDir = 'videos';
    } else {
        $subDir = 'files';
    }
    
    // 生成安全的文件名：日期 + 随机字符串 + 扩展名
    $date = date('Ymd');
    $filename = $date . '_' . uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    
    // 上传目录
    $uploadDir = public_path() . "/uploads/{$subDir}/{$date}";
    
    // 创建目录（如果不存在）
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // 移动文件
    $filepath = $uploadDir . '/' . $filename;
    
    // webman 的 File::move() 期望完整路径，而不是目录+文件名
    if (method_exists($file, 'move')) {
        // 检查是否是 webman 的 UploadFile（继承自 File）
        if ($file instanceof \Webman\Http\UploadFile || $file instanceof \Webman\File) {
            // webman 的 move 方法需要完整路径
            $file->move($filepath);
        } else {
            // 自定义的 UploadFile 类，需要目录和文件名分开
            $file->move($uploadDir, $filename);
        }
    } else {
        // 如果是标准的上传文件对象
        move_uploaded_file($file->getPathname(), $filepath);
    }
    
    // 验证文件是否存在
    if (!file_exists($filepath)) {
        throw new Exception('文件保存失败');
    }
    
    // 设置文件权限（只读）
    chmod($filepath, 0644);
    
    // 返回相对路径（用于保存到数据库）
    $returnPath = "/uploads/{$subDir}/{$date}/{$filename}";
    error_log("upload 函数返回路径: $returnPath, 实际文件: $filepath");
    return $returnPath;
}

/**
 * 检查图片文件头
 * @param string $filepath 文件路径
 * @param string $ext 扩展名
 * @return bool
 */
function checkImageHeader($filepath, $ext)
{
    $file = fopen($filepath, 'rb');
    $bin = fread($file, 12); // 读取更多字节以支持 HEIC
    fclose($file);
    
    // 对于某些格式，文件头验证可能不准确，记录日志但不阻止
    $strInfo = @unpack("C2chars", $bin);
    $typeCode = intval($strInfo['chars1'] . $strInfo['chars2']);
    
    $fileType = [
        'jpg'  => [255216],
        'jpeg' => [255216],
        'png'  => [13780],
        'gif'  => [7173],
        'bmp'  => [6677],
        'webp' => [8273], // RIFF
    ];
    
    // 检查标准格式
    if (isset($fileType[$ext]) && in_array($typeCode, $fileType[$ext])) {
        return true;
    }
    
    // 检查 HEIC/HEIF 格式（ftyp 标识）
    if (in_array($ext, ['heic', 'heif'])) {
        // HEIC/HEIF 文件在偏移 4-11 字节处包含 "ftyp" 标识
        // 或者包含 "heic", "mif1" 等标识
        if (strpos($bin, 'ftyp') !== false || 
            strpos($bin, 'heic') !== false || 
            strpos($bin, 'mif1') !== false) {
            return true;
        }
        
        // 对于从外链下载的 HEIC 文件，可能格式稍有不同，放宽验证
        // 只要文件大小合理就允许
        if (filesize($filepath) > 100) {
            error_log("HEIC 文件头验证宽松通过: $filepath");
            return true;
        }
    }
    
    // 记录验证失败的信息
    error_log("文件头验证失败: $filepath, 扩展名: $ext, TypeCode: $typeCode, 前12字节: " . bin2hex($bin));
    
    return false;
}
/**
 * 获取系统配置值
 * @param string $key 配置键，格式：group.config_key
 * @param mixed $default 默认值
 * @return mixed
 */
function get_config($key, $default = null)
{
    $parts = explode('.', $key);
    
    if (count($parts) >= 2) {
        // group.config_key
        $groupKey = $parts[0];
        $configKey = $parts[1];
    } else {
        return $default;
    }
    
    return \app\admin\model\Config::getConfigValue('', $groupKey, $configKey, $default);
}
