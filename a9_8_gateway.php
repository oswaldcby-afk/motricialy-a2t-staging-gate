<?php
declare(strict_types=1);
/* A9.8 test-only HTTPS gateway. No public tool, secrets, or paid provider.
   Allow one exact file in Apache; an unguessable 256-bit header is required
   before any private API code is loaded. Remove this after isolated QA. */
header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$key=$root.'/a9-8-http-key.hex';
$install=$root.'/app-a9-7-full';
$private=$install.'/private-server';
$entry=$install.'/test-public/tools/audio-to-text/api/index.php';
$provided=(string)($_SERVER['HTTP_X_A2T_STAGE_KEY']??'');
if (getenv('A2T_ENABLE_LIVE')==='1' ||
    (string)($_SERVER['HTTP_HOST']??'')!=='motricialy.com' ||
    strlen($provided)!==64 || !ctype_xdigit($provided) ||
    !is_file($key) || is_link($key) || (fileperms($key)&0777)!==0600) {
    http_response_code(404);exit;
}
$expected=(string)@file_get_contents($key);
if(strlen($expected)!==64 || !hash_equals($expected,$provided)) {
    http_response_code(404);exit;
}
if(!is_file($entry) || is_link($entry) ||
   !is_file($private.'/bootstrap.php') || is_link($private.'/bootstrap.php') ||
   !is_file($private.'/ledger.php') ||
   hash_file('sha256',$private.'/ledger.php')!=='90515ca4e986e2eb48a8d936b7d3b8385a13ccf9b14d360f99b05717e37d0185'){
    http_response_code(503);exit;
}
$route=(string)($_GET['route']??'');
if(!in_array($route,['config','process','transcribe','export/docx','unexpected'],true)){
    http_response_code(404);exit;
}
if(!in_array((string)($_SERVER['REQUEST_METHOD']??''),['GET','POST'],true)){
    http_response_code(405);exit;
}
/* Simulate original final tool route while exercising real HTTPS, Apache,
   PHP SAPI, Origin, request body, response headers and DOCX binary transfer.
   Does not claim final public URL routing acceptance. */
$_SERVER['REQUEST_URI']='/tools/audio-to-text/api/'.$route;
$_SERVER['SCRIPT_NAME']='/tools/audio-to-text/api/index.php';
$_SERVER['DOCUMENT_ROOT']=$install.'/test-public';
putenv('A2T_PRIVATE_PATH='.$private);
putenv('A2T_ENABLE_LIVE=0');
putenv('A2T_PAUSE_AI=1');
putenv('OPENAI_API_KEY');
unset($_ENV['OPENAI_API_KEY'],$_SERVER['OPENAI_API_KEY']);
require $entry;
