<?php
/** v1.0.0 | GPL-2.0 license */
include('core.php');
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

if ($action === 'check') {
    $code = preg_replace('/[^A-Za-z0-9]/', '', $_GET['code'] ?? '');
    if (file_exists("data/$code.json")) {
        ret(['valid' => true]);
    } else {
        ret(['valid' => false]);
    }
}

if ($action === 'join') {
    $code = preg_replace('/[^A-Za-z0-9]/', '', $_GET['code'] ?? '');
    if (file_exists("data/$code.json")) {
        $_SESSION['code'] = $code;
        $_SESSION['role'] = 'receiver';
        $data = json_decode(file_get_contents("data/$code.json"), true);
        ret(['success' => true, 'files' => $data['files']]);
    } else {
        ret(['success' => false, 'msg' => '加入码无效或已过期']);
    }
}

if (empty($_SESSION['code'])) {
    ret(['files' => [], 'msg' => '未加入房间']);
}
$code = $_SESSION['code'];
$dataFile = "data/$code.json";
if (!file_exists($dataFile)) {
    ret(['files' => [], 'msg' => '房间不存在或已过期']);
}
$data = json_decode(file_get_contents($dataFile), true);
ret(['files' => $data['files']]);