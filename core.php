<?php
session_start();
/** v1.0.0 | GPL-2.0 license 
 * BY：云猫
 * Blog：lwcat.cn
 * github；https://github.com/smcloudcat/xcat
 * emial：yuncat@email.lwcat.cn
 */

/** 以下是单文件大小上传限制**/
$maxSize = 100 * 1024 * 1024; //100M，需要自己改，不能超过服务器限制
/** 以上是单文件大小上传限制**/

cleanExpired();
function cleanExpired() {
    $now = time();
    foreach (glob('data/*.json') as $file) {
        $data = json_decode(file_get_contents($file), true);
        if (!$data) continue;
        if ($now - $data['created'] > 600) { //房间过期：10分钟 = 600秒
            unlink($file);
            $code = basename($file, '.json');
            $dir = __DIR__ . "/uploads/$code";
            if (is_dir($dir)) {
                foreach (glob("$dir/*") as $f) unlink($f);
                rmdir($dir);
            }
        }
    }
}
function ret($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
function generateCode($length = 6) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $str;
}