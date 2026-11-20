<?php
declare(strict_types=1);
/* A9.9 guarded, PAID-DISABLED, temporary HTTPS multipart test for the ACTUAL media.php preflight.
   No real users, no public tool deployment, no provider, no retained uploads. */
header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Content-Type: application/json; charset=utf-8');
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$keyFile=$root.'/a9-9-http-key.hex';
$private=$root.'/app-a9-7-full/private-server';
$header=(string)($_SERVER['HTTP_X_A2T_STAGE_KEY']??'');
function a99send(int $status,array $body):never {http_response_code($status);echo json_encode($body,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);exit;}
if(getenv('A2T_ENABLE_LIVE')==='1'||(string)($_SERVER['HTTP_HOST']??'')!=='motricialy.com' ||
 !is_file($keyFile)||is_link($keyFile)||(fileperms($keyFile)&0777)!==0600||
 strlen($header)!==64||!ctype_xdigit($header)||
 !hash_equals((string)@file_get_contents($keyFile),$header))a99send(404,['error'=>'not_found']);
if((string)($_SERVER['REQUEST_METHOD']??'')==='GET'&&($_GET['action']??'')==='capabilities'){
 a99send(200,['test'=>'a9_9','php_sapi'=>PHP_SAPI,'file_uploads'=>filter_var(ini_get('file_uploads'),FILTER_VALIDATE_BOOLEAN),
  'upload_max_filesize'=>(string)ini_get('upload_max_filesize'),
  'post_max_size'=>(string)ini_get('post_max_size'),
  'max_file_uploads'=>(string)ini_get('max_file_uploads'),
  'user_ini_filename'=>(string)ini_get('user_ini.filename'),
  'getid3_autoload_exists'=>is_file($private.'/vendor/autoload.php'),
  'paid_api_enabled'=>false]);
}
if(($_GET['action']??'')!=='upload'||($_SERVER['REQUEST_METHOD']??'')!=='POST')a99send(404,['error'=>'not_found']);
if((string)($_SERVER['HTTP_ORIGIN']??'')!=='https://motricialy.com')a99send(403,['error'=>'origin_rejected']);
$len=(int)($_SERVER['CONTENT_LENGTH']??0);
if($len<=0||$len>25*1024*1024)a99send(413,['error'=>'request_limit']);
if(!isset($_FILES['audio'])||count($_FILES)!==1||!is_array($_FILES['audio']))a99send(422,['error'=>'missing_audio']);
$u=$_FILES['audio'];
if(($u['error']??99)!==UPLOAD_ERR_OK||!is_string($u['tmp_name']??null)||!is_uploaded_file($u['tmp_name']))a99send(422,['error'=>'upload_invalid']);
if((int)($u['size']??0)<44||(int)$u['size']>24*1024*1024)a99send(413,['error'=>'file_size_rejected']);
if(!is_string($u['name']??null)||!preg_match('/^[a-zA-Z0-9_.-]{1,80}\\.wav$/D',$u['name']))a99send(415,['error'=>'qa_wav_only']);
$sha=(string)($_SERVER['HTTP_X_A2T_FIXTURE_SHA256']??'');
if(!preg_match('/^[a-f0-9]{64}$/D',$sha)||!hash_equals($sha,(string)hash_file('sha256',$u['tmp_name'])))a99send(422,['error'=>'fixture_hash_mismatch']);
$tmpDir=null;
try{
 if(!is_file($private.'/vendor/autoload.php')||hash_file('sha256',$private.'/media.php')!=='03420827736b5097be09ece684516c28a6610496cc08225c1ea783aeb65a0265')
 throw new RuntimeException('dependencies_missing');
 define('A2T_ENTRY',true);
 define('A2T_UPLOAD_BYTES',24*1024*1024);
 define('A2T_DURATION_SECONDS',900);
 require_once $private.'/vendor/autoload.php';
 require_once $private.'/media.php';
 $verified=a2t_preflight_file($u);
 if(!is_array($verified))throw new RuntimeException('candidate_preflight_invalid');
 $getid3=new getID3();
 $info=$getid3->analyze($u['tmp_name']);
 $duration=(float)($info['playtime_seconds']??0);
 if($duration<1.9||$duration>2.1||($info['fileformat']??'')!=='wav')throw new RuntimeException('qa_media_mismatch');
 $tmpDir=$root.'/a9-9-http-upload-'.bin2hex(random_bytes(8));
 if(!mkdir($tmpDir,0700))throw new RuntimeException('private_tmp_dir');
 @chmod($tmpDir,0700);
 $target=$tmpDir.'/fixture.wav';
 if(!move_uploaded_file($u['tmp_name'],$target))throw new RuntimeException('move_uploaded_file_failed');
 @chmod($target,0600);
 if(!hash_equals($sha,(string)hash_file('sha256',$target)))throw new RuntimeException('moved_sha_mismatch');
 if(!@unlink($target)||!@rmdir($tmpDir))throw new RuntimeException('cleanup_failed');
 $tmpDir=null;
 $resultCode=200;
 $resultBody=[['stage'=>'A9.9','status'=>'REAL_MULTIPART_PREFLIGHT_PASS','is_uploaded_file'=>true,
  'candidate_preflight'=>true,'duration_seconds'=>round($duration,2),'fileformat'=>'wav',
  'move_uploaded_file'=>true,'temp_files_deleted'=>true,'paid_api_calls'=>0];
}catch(Throwable $e){
 $reason=($e instanceof RuntimeException)?$e->getMessage():get_class($e);
 $allowed=['dependencies_missing','candidate_preflight_invalid','qa_media_mismatch','private_tmp_dir','move_uploaded_file_failed','moved_sha_mismatch','cleanup_failed'];
 $resultCode=in_array($reason,$allowed,true)?503:422;
 $resultBody=['stage'=>'A9.9','status'=>'UPLOAD_REJECTED','reason'=>in_array($reason,$allowed,true)?$reason:'media_preflight_rejected','paid_api_calls'=>0];
}finally{
 if(is_string($tmpDir)&&is_dir($tmpDir)){
  $f=$tmpDir.'/fixture.wav';if(is_file($f))@unlink($f);
  @rmdir($tmpDir);
 }
}

a99send($resultCode??503,$resultBody??['error'=>'unclassified']);
