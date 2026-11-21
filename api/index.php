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
$prefix='/tools/audio-to-text/api/';
if(!str_starts_with($uri,$prefix)){http_response_code(404);exit;}
$route=trim(substr($uri,strlen($prefix)),'/');
if(!in_array($route,['config','transcribe','export/docx','process','capabilities','quota'],true)){
 http_response_code(404);exit;
}
if(($_SERVER['HTTP_HOST']??'')!=='motricialy.com'){
 http_response_code(404);exit;
}
$root='/home/u342033576/.motricialy-a2t-stage-a9';
// A capability discovery route is read-only and never calls a paid provider.
if($route==='capabilities'){
 if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit;}
 $textFlag=$root.'/a9-15-text-enabled.flag';
 $publicFlag=$root.'/a9-11-public-enabled.flag';
 $textOn=is_file($textFlag)&&!is_link($textFlag)&&((fileperms($textFlag)&0777)===0600)
   &&trim((string)@file_get_contents($textFlag))==='enabled';
 $publicOn=is_file($publicFlag)&&!is_link($publicFlag)&&((fileperms($publicFlag)&0777)===0600)
   &&trim((string)@file_get_contents($publicFlag))==='enabled';
 $enabled=$textOn&&$publicOn&&!is_file($root.'/a9-11-public-paused.flag');
 header('Content-Type: application/json; charset=utf-8');
 echo json_encode(['text_processing_ready'=>$enabled,'max_text_characters'=>12000,
   'max_text_operations_daily'=>2,'clean_and_summary_are_separate_metered_operations'=>true],JSON_UNESCAPED_SLASHES);
 exit;
}
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
// Text processing is separately gated from audio transcription.
if($route==='process'){
 $textFlag=$root.'/a9-15-text-enabled.flag';
 $publicTextOn=is_file($textFlag)&&!is_link($textFlag)&&((fileperms($textFlag)&0777)===0600)
   &&trim((string)@file_get_contents($textFlag))==='enabled';
 if(!$qa&&!$publicTextOn){
  header('Content-Type: application/json; charset=utf-8');
  http_response_code(503);echo '{"detail":"AI text tools are not enabled yet"}';exit;
 }
 if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){http_response_code(405);exit;}
 $bytes=(int)($_SERVER['CONTENT_LENGTH']??0);
 if($bytes<=0||$bytes>45000){http_response_code(413);header('Content-Type: application/json');echo '{"detail":"Text exceeds the current processing limit"}';exit;}
}
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
// Read-only per-visitor quota reporting; no AI provider calls or user content stored.
if($route==='quota'){
 if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit;}
 if(getenv('A2T_ENABLE_LIVE')!=='1'){
  http_response_code(503);header('Content-Type: application/json');echo '{"detail":"Service temporarily unavailable"}';exit;
 }
 define('A2T_ENTRY',true);
 require_once $private.'/bootstrap.php';
 a2t_headers();
 try{
  $identities=a2t_subjects();
  $pdo=a2t_pdo();
  $q=$pdo->prepare('SELECT audio_count,audio_seconds,text_count FROM a2t_daily_quota WHERE day_utc=UTC_DATE() AND subject_hash IN (?,?)');
  $q->execute($identities);
  $count=0;$seconds=0;$textCount=0;
  foreach($q->fetchAll() as $u){
   $count=max($count,(int)$u['audio_count']);
   $seconds=max($seconds,(int)$u['audio_seconds']);
   $textCount=max($textCount,(int)$u['text_count']);
  }
  a2t_json(200,[
   'files_remaining'=>max(0,2-$count),
   'minutes_remaining'=>round(max(0,900-$seconds)/60,1),
   'text_operations_remaining'=>max(0,2-$textCount),
   'daily_file_limit'=>2,'daily_minutes_limit'=>15,'daily_text_operations_limit'=>2
  ]);
 }catch(Throwable $e){a2t_fail(503,'Quota information temporarily unavailable');}
}
$_SERVER['REQUEST_URI']='/tools/audio-to-text/api/'.$route;
require $entry;
