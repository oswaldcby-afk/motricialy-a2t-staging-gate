<?php
declare(strict_types=1);
/*
 * Isolated A9.11 release-candidate API wrapper.
 * All paid routes intentionally remain DISABLED until live smoke/ledger,
 * proxy identity, budget and independent go-live validation are complete.
 * No credentials or provider logic belong in document root.
 */
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
$path=(string)parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH);
$mark=strpos($path,'/api/');
if($mark===false){http_response_code(404);exit;}
$route=substr($path,$mark+5);
if(!in_array(trim($route,'/'),['config','transcribe','process','export/docx'],true)){
  http_response_code(404);exit;
}
$private='/home/u342033576/.motricialy-a2t-stage-a9/app-a9-7-full/private-server';
$entry='/home/u342033576/.motricialy-a2t-stage-a9/app-a9-7-full/test-public/tools/audio-to-text/api/index.php';
if(!is_file($entry)||!is_file($private.'/bootstrap.php')||is_link($entry)){
  header('Content-Type: application/json; charset=utf-8');
  http_response_code(503);echo '{"detail":"Service unavailable"}';exit;
}
putenv('A2T_PRIVATE_PATH='.$private);
putenv('A2T_ENABLE_LIVE=0');
putenv('A2T_PAUSE_AI=1');
putenv('OPENAI_API_KEY');
$_SERVER['REQUEST_URI']='/tools/audio-to-text/api/'.$route;
require $entry;
