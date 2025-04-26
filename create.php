<?php
include('core.php');

$code = generateCode(6);

while (file_exists("data/$code.json")) {
    $code = generateCode(6);
}

if (!is_dir('uploads')) mkdir('uploads', 0777, true);
if (!is_dir('data')) mkdir('data', 0777, true);
mkdir("uploads/$code", 0777, true);
$data = ['code' => $code, 'created' => time(), 'files' => []];
file_put_contents("data/$code.json", json_encode($data, JSON_PRETTY_PRINT));

$_SESSION['code'] = $code;
$_SESSION['role'] = 'creator';

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['code' => $code]);