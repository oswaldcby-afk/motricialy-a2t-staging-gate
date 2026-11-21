<?php
declare(strict_types=1);
/* Motricialy A2T release gateway.
 * Requires private production config outside public_html and a one-time
 * server-generated enabled flag; otherwise all paid paths fail closed.
 * QA access uses a 256-bit secret header; never a URL query parameter.
 */
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
$uri=(string)parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH);
$mark=strpos($uri,'/api/');
if($mark===false){http_response_code(404);exit;}
$route=trim(substr($uri,$mark+5),'/');
if(!in_array($route,['config','transcribe','export/docx','process'],true)){
 http_response_code(404);exit;
}
if(($_SERVER['HTTP_HOST']??'')!=='motricialy.com'){
 http_response_code(404);exit;
}
if($route==='process'){
 header('Content-Type: application/json; charset=utf-8');
 http_response_code(503);
 echo '{"detail":"Advanced text processing is not enabled in this release"}';exit;
}
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$private=$root.'/app-a9-7-full/private-server';
$entry=$root.'/app-a9-7-full/test-public/tools/audio-to-text/api/index.php';
if(!is_file($entry)||is_link($entry)||!is_file($private.'/bootstrap.php')){
 http_response_code(503);header('Content-Type: application/json; charset=utf-8');
 echo '{"detail":"Service temporarily unavailable"}';exit;
}
foreach(['A2T_ENABLE_LIVE','A2T_PAUSE_AI','OPENAI_API_KEY','A2T_HMAC_KEY','A2T_MYSQL_DSN','A2T_MYSQL_USER','A2T_MYSQL_PASSWORD','A2T_BILLING_DAY'] as $k){
 putenv($k);
}
$validSecret=static function(string $p,int $min=1,int $max=1000):bool{
 return is_file($p)&&!is_link($p)&&((fileperms($p)&0777)===0600)&&filesize($p)>=$min&&filesize($p)<=$max;
};
$hmac='';
$hmacPath=$root.'/a9-11-production-hmac.private';
if($validSecret($hmacPath,64,64)){
 $hmac=(string)file_get_contents($hmacPath);
 if(preg_match('/^[a-f0-9]{64}$/D',$hmac))putenv('A2T_HMAC_KEY='.$hmac);
}
$qaToken=$root.'/a9-11-release-qa-gate.private';
$header=(string)($_SERVER['HTTP_X_A2T_STAGE_KEY']??'');
$qa=false;
if(strlen($header)===64&&ctype_xdigit($header)&&$validSecret($qaToken,64,64)){
 $qa=hash_equals((string)file_get_contents($qaToken),$header);
}
$enabledPath=$root.'/a9-11-public-enabled.flag';
$enabled=$validSecret($enabledPath,7,20)&&trim((string)file_get_contents($enabledPath))==='enabled';
$paused=is_file($root.'/a9-11-public-paused.flag');
$active=($qa||$enabled)&&!$paused;
$cfg=$root.'/a9-11-mysql-production.json';
$key=$root.'/a9-11-openai-key.private';
if($active&&$validSecret($cfg,50,3000)&&$validSecret($key,30,500)&&strlen($hmac)===64){
 try{
  $c=json_decode((string)file_get_contents($cfg),true,512,JSON_THROW_ON_ERROR);
  if(is_array($c)&&($c['environment']??'')==='production-a2t'&&
    ($c['dsn']??'')==='mysql:host=localhost;dbname=u342033576_a2t_live_26;charset=utf8mb4'&&
    ($c['user']??'')==='u342033576_a2t_runtime'&&
    is_string($c['password']??null)&&strlen($c['password'])>=32){
    $apiKey=(string)file_get_contents($key);
    if(str_starts_with($apiKey,'sk-')&&!str_contains($apiKey,"\n")){
     putenv('OPENAI_API_KEY='.$apiKey);
     putenv('A2T_MYSQL_DSN='.$c['dsn']);
     putenv('A2T_MYSQL_USER='.$c['user']);
     putenv('A2T_MYSQL_PASSWORD='.$c['password']);
     putenv('A2T_BILLING_DAY=1');
     putenv('A2T_PAUSE_AI=0');
     putenv('A2T_ENABLE_LIVE=1');
    }
    unset($apiKey);
  }
  unset($c);
 }catch(Throwable $e){/* fail-closed */}
}
if(getenv('A2T_ENABLE_LIVE')!=='1'){
 putenv('A2T_ENABLE_LIVE=0');
 putenv('A2T_PAUSE_AI=1');
}
putenv('A2T_PRIVATE_PATH='.$private);
$_SERVER['REQUEST_URI']='/tools/audio-to-text/api/'.$route;
require $entry;
