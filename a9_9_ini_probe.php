<?php
declare(strict_types=1);
/* Temporary read-only A9.9 PHP ini test. No upload processing or APIs. */
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('X-Content-Type-Options: nosniff');
header('Content-Type: application/json; charset=utf-8');
$root='/home/u342033576/.motricialy-a2t-stage-a9';$key=$root.'/a9-9-http-key.hex';
$token=(string)($_SERVER['HTTP_X_A2T_STAGE_KEY']??'');
if((string)($_SERVER['HTTP_HOST']??'')!=='motricialy.com'
 ||!is_file($key)||is_link($key)||(fileperms($key)&0777)!==0600
 ||strlen($token)!==64||!ctype_xdigit($token)
 ||!hash_equals((string)@file_get_contents($key),$token)){
 http_response_code(404);echo '{}';exit;
}
echo json_encode(['stage'=>'A9.9','scope'=>'LITESPEED_HTACCESS_PHP_VALUES',
 'web_sapi'=>PHP_SAPI,
 'upload_max_filesize'=>(string)ini_get('upload_max_filesize'),
 'post_max_size'=>(string)ini_get('post_max_size'),
 'max_file_uploads'=>(string)ini_get('max_file_uploads'),
 'memory_limit'=>(string)ini_get('memory_limit'),
 'max_execution_time'=>(string)ini_get('max_execution_time'),
 'paid_api_calls'=>0],JSON_UNESCAPED_SLASHES);
