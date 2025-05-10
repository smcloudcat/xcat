<?php
/** v1.1.2 | GPL-2.0 license 
 * BY：云猫
 * Blog：lwcat.cn
 * github；https://github.com/smcloudcat/xcat
 * emial：yuncat@email.lwcat.cn
 */
 // 配置常量
session_start();
define('UPLOAD_DIR', __DIR__ . '/uploads/');//文件上传目录
define('DATA_DIR', __DIR__ . '/data/');//记录保存地址
define('CAPTCHA_LENGTH', 6);//验证码长度，小白请勿修改，需要更新前台js
define('CODE_LENGTH', 6);//房间码长度，小白请勿修改，需要更新前台js
define('FILE_TTL', 1200);//房间有效期20分钟（60秒*20分钟=1200）
 
function cleanExpired() {
    $now = time();
    foreach (glob(DATA_DIR . '*.json') as $file) {
        $data = json_decode(file_get_contents($file), true);
        if (!$data) continue;
        
        if ($now - $data['created'] > FILE_TTL) {
            $code = basename($file, '.json');
            unlink($file);
            deleteDirectory(UPLOAD_DIR . $code);
        }
    }
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    foreach (glob("$dir/*") as $file) {
        is_dir($file) ? deleteDirectory($file) : unlink($file);
    }
    rmdir($dir);
}

function ret($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function generateUniqueCode($length = CODE_LENGTH) {
    do {
        $code = generateRandomString($length);
        $path = DATA_DIR . "$code.json";
    } while (file_exists($path));
    
    return $code;
}

function generateRandomString($length, $chars = '0123456789') {
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $str;
}

function generateCaptcha($length = CAPTCHA_LENGTH) {
    $code = generateRandomString($length, '0123456789');
    $_SESSION['captcha'] = $code;
    return $code;
}
function handleCreate() {
    if (!isset($_SESSION['codechek']) || $_SESSION['codechek'] !== true) {
        ret(['error' => '未验证'], 403);
    }
    $_SESSION['codechek'] = false;

    @mkdir(UPLOAD_DIR, 0755, true);
    @mkdir(DATA_DIR, 0755, true);
    
    $code = generateUniqueCode();
    $dir = UPLOAD_DIR . $code;
    @mkdir($dir, 0755);
    
    $data = [
        'code' => $code,
        'created' => time(),
        'files' => []
    ];
    file_put_contents(DATA_DIR . "$code.json", json_encode($data, JSON_PRETTY_PRINT));
    
    $_SESSION['code'] = $code;
    $_SESSION['role'] = 'creator';
    ret(['code' => $code]);
}

function handleFetch() {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'check') {
        $code = sanitizeCode($_GET['code'] ?? '');
        ret(['valid' => file_exists(DATA_DIR . "$code.json")]);
    }
    
    if ($action === 'join') {
        $code = sanitizeCode($_GET['code'] ?? '');
        $file = DATA_DIR . "$code.json";
        
        if (!file_exists($file)) {
            ret(['success' => false, 'msg' => '加入码无效或已过期'], 404);
        }
        
        $_SESSION['code'] = $code;
        $_SESSION['role'] = 'receiver';
        $data = json_decode(file_get_contents($file), true);
        ret(['success' => true, 'files' => $data['files']]);
    }
    
    if (empty($_SESSION['code'])) {
        ret(['files' => [], 'msg' => '未加入房间'], 403);
    }
    
    $code = sanitizeCode($_SESSION['code']);
    $file = DATA_DIR . "$code.json";
    if (!file_exists($file)) {
        ret(['files' => [], 'msg' => '房间不存在或已过期'], 404);
    }
    
    $data = json_decode(file_get_contents($file), true);
    ret(['files' => $data['files']]);
}

function handleDownload() {
    $fileKey = basename($_GET['file'] ?? '');
    if (!$fileKey) {
        ret(['error' => '缺少文件参数'], 400);
    }
    $code = sanitizeCode($_SESSION['code'] ?? '');
    if (!$code) {
        ret(['error' => '未加入房间'], 403);
    }
    $dataFile = DATA_DIR . "$code.json";
    if (!file_exists($dataFile)) {
        ret(['error' => '房间不存在或已过期'], 404);
    }
    $data = json_decode(file_get_contents($dataFile), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        ret(['error' => '房间数据损坏'], 500);
    }
    $file = null;
    foreach ($data['files'] as $f) {
        if ($f['stored'] === $fileKey) {
            $file = $f;
            break;
        }
    }
    if (!$file) {
        ret(['error' => '文件未找到'], 404);
    }
    $path = UPLOAD_DIR . "$code/{$fileKey}.secure";
    if (!file_exists($path)) {
        ret(['error' => '文件丢失'], 404);
    }
    $filename = preg_replace('/[\x00-\x1F\x7F"\\\\]/', '', $file['name']);
    $filename = mb_substr($filename, 0, 200);
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.$filename.'"; filename*=UTF-8\'\''.rawurlencode($filename));
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    while (ob_get_level()) ob_end_clean();
    readfile($path);
    exit;
}

function handleUpload() {
    if (empty($_SESSION['code'])) {
        ret(['error' => '未加入房间或加入码无效'], 403);
    }
    
    $code = sanitizeCode($_SESSION['code']);
    $dataFile = DATA_DIR . "$code.json";
    if (!file_exists($dataFile)) {
        ret(['error' => '加入码不存在或已过期'], 404);
    }

    $uploadId = $_POST['uploadId'];
    $fileName = $_POST['fileName'];
    $chunkIndex = intval($_POST['chunkIndex']);
    $totalChunks = intval($_POST['totalChunks']);

    $tmpDir = UPLOAD_DIR . "$code/chunks/{$uploadId}";
    if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);

    $chunkPath = "$tmpDir/{$chunkIndex}.part";
    if (!move_uploaded_file($_FILES['chunkData']['tmp_name'], $chunkPath)) {
        ret(['status'=>'error','msg'=>'保存分片失败'], 500);
    }
    ret(['status'=>'ok']);
}

function handleCaptcha() {
    $code = generateCaptcha();
    $im = imagecreatetruecolor(120, 40);
    $bg = imagecolorallocate($im, 255, 255, 255);
    imagefilledrectangle($im, 0, 0, 120, 40, $bg);
    for ($i = 0; $i < 5; $i++) {
        $color = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        imageline($im, 0, mt_rand(0, 40), 120, mt_rand(0, 40), $color);
    }
    $font = 'font.ttf';
    $color = imagecolorallocate($im, mt_rand(0, 150), mt_rand(0, 150), mt_rand(0, 150));
    imagettftext($im, 20, 0, 10, 30, $color, $font, $code);
    
    header('Content-type: image/png');
    imagepng($im);
    imagedestroy($im);
    exit;
}

function handleCaptchaCheck() {
    $success = false;
    if (isset($_POST['captcha'])) {
        $input = strtolower(trim($_POST['captcha']));
        $captcha = strtolower($_SESSION['captcha'] ?? '');
        if ($input === $captcha) {
            $success = true;
            $_SESSION['codechek'] = true;
            unset($_SESSION['captcha']);
        }
    }
    ret(['success' => $success]);
}

function sanitizeCode($code) {
    return preg_replace('/[^A-Za-z0-9]/', '', $code);
}

function normalizeFiles($file) {
    $files = [];
    foreach ($file as $key => $values) {
        foreach ((array)$values as $i => $value) {
            $files[$i][$key] = $value;
        }
    }
    return $files;
}

function handleMerge() {
    if (empty($_SESSION['code'])) {
        ret(['error' => '未加入房间或加入码无效'], 403);
    }
    
    $code = sanitizeCode($_SESSION['code']);
    $dataFile = DATA_DIR . "$code.json";
    if (!file_exists($dataFile)) {
        ret(['error' => '加入码不存在或已过期'], 404);
    }
    $uploadId = $_POST['uploadId'];
    $fileName = $_POST['fileName'];
    $totalChunks = intval($_POST['totalChunks']);

    $chunksDir = UPLOAD_DIR . "$code/chunks/{$uploadId}";
    $dstDir    = UPLOAD_DIR . $code;
    if (!is_dir($dstDir)) mkdir($dstDir, 0755, true);

    $stored = time() . '_' . bin2hex(random_bytes(8));
    $outPath = "$dstDir/{$stored}.secure";
    $outHandle = fopen($outPath, 'wb');

    for ($i = 0; $i < $totalChunks; $i++) {
        $part = "$chunksDir/{$i}.part";
        if (!file_exists($part)) {
            fclose($outHandle);
            ret(['status'=>'error','msg'=>"缺少分片 {$i}"], 500);
        }
        $inHandle = fopen($part, 'rb');
        stream_copy_to_stream($inHandle, $outHandle);
        fclose($inHandle);
        unlink($part);
    }
    fclose($outHandle);
    @rmdir($chunksDir);
    $data = json_decode(file_get_contents($dataFile), true);
    $data['files'][] = [
        'name'     => $fileName,
        'stored'   => $stored,
        'time'     => date('Y-m-d H:i:s'),
        'uploader' => $_SESSION['role'] ?? 'unknown'
    ];
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    ret(['status'=>'ok','stored'=>$stored]);
}