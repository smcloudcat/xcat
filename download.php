<?php
/** v1.0.0 | GPL-2.0 license */
include('core.php');

$fileKey = isset($_GET['file']) ? basename($_GET['file']) : '';
if ($fileKey === '') {
    http_response_code(400);
    exit('缺少文件参数');
}

$code = isset($_SESSION['code']) ? preg_replace('/[^A-Za-z0-9]/', '', $_SESSION['code']) : '';
if (!$code) {
    http_response_code(400);
    exit('未加入房间');
}

$dataFile = __DIR__ . "/data/{$code}.json";
if (!file_exists($dataFile)) {
    http_response_code(404);
    exit('房间不存在或已过期');
}

$data = json_decode(file_get_contents($dataFile), true);
$entry = null;
foreach ($data['files'] as $f) {
    if ($f['stored'] === $fileKey) {
        $entry = $f;
        break;
    }
}
if (!$entry) {
    http_response_code(404);
    exit('文件未找到');
}

$path = __DIR__ . "/uploads/{$code}/{$fileKey}.secure";
if (!file_exists($path)) {
    http_response_code(404);
    exit('文件丢失');
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $entry['name'] . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($path));

readfile($path);
exit;