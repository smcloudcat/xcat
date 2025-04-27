<?php
/** v1.1.0 | GPL-2.0 license 
 * BY：云猫
 * Blog：lwcat.cn
 * github；https://github.com/smcloudcat/xcat
 * emial：yuncat@email.lwcat.cn
 */
include("core.php");

cleanExpired();
$act = $_GET['act'] ?? '';
switch ($act) {
    case 'create':
        handleCreate();
        break;
    case 'fetch':
        handleFetch();
        break;
    case 'download':
        handleDownload();
        break;
    case 'upload':
        handleUpload();
        break;
    case 'captcha':
        handleCaptcha();
        break;
    case 'captchacheck':
        handleCaptchaCheck();
        break;
    default:
        ret(['error' => '无效操作'], 400);
}