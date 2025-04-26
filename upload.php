<?php
/** v1.0.0 | GPL-2.0 license */
include('core.php');

if (empty($_SESSION['code'])) {
    http_response_code(400);
    exit(json_encode(['error'=>'未加入房间或加入码无效']));
}
$code = preg_replace('/[^A-Za-z0-9]/', '', $_SESSION['code']);
$dataFile = __DIR__ . "/data/$code.json";
if (!file_exists($dataFile)) {
    http_response_code(400);
    exit(json_encode(['error'=>'加入码不存在或已过期']));
}

$response = [];

foreach ($_FILES as $field) {
    $names = is_array($field['name']) ? $field['name'] : [$field['name']];
    $tmps  = is_array($field['tmp_name']) ? $field['tmp_name'] : [$field['tmp_name']];
    $errs  = is_array($field['error'])    ? $field['error']    : [$field['error']];
    $sizes = is_array($field['size'])     ? $field['size']     : [$field['size']];

    foreach ($names as $i => $origName) {
        $err  = $errs[$i];
        $tmp  = $tmps[$i];
        $size = $sizes[$i];
        $origName = basename($origName);

        if ($err !== UPLOAD_ERR_OK) {
            $response[] = ['name'=>$origName, 'status'=>'error', 'msg'=>'上传失败 (错误码 '.$err.')'];
            continue;
        }
        if ($size > $maxSize) {
            $response[] = ['name'=>$origName, 'status'=>'error', 'msg'=>'文件过大'];
            continue;
        }

        $random   = bin2hex(random_bytes(8));
        $stored   = time() . '_' . $random;
        $secureFn = $stored . '.secure';
        $dstDir   = __DIR__ . "/uploads/$code";
        if (!is_dir($dstDir)) mkdir($dstDir, 0777, true);
        $dst = "$dstDir/$secureFn";

        if (!move_uploaded_file($tmp, $dst)) {
            $response[] = ['name'=>$origName, 'status'=>'error', 'msg'=>'保存失败'];
            continue;
        }
        $data = json_decode(file_get_contents($dataFile), true);
        $entry = [
            'name'     => $origName,
            'stored'   => $stored,
            'time'     => date('Y-m-d H:i:s'),
            'uploader' => $_SESSION['role'] ?? 'unknown'
        ];
        $data['files'][] = $entry;
        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $response[] = [
            'name'   => $origName,
            'stored' => $stored,
            'status' => 'ok'
        ];
    }
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);