<?php
declare(strict_types=1);
/* TTS v2.13: safe, idempotent CLI release; no API keys, budget caps or ledger edits. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit(1);}
$root='/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech';
$target=$root.'/api/budget.php';
$ledger='/home/u342033576/domains/motricialy.com/motricialy-tts-private/ledger.sqlite';
$backup='/home/u342033576/domains/motricialy.com/tts-budget-before-v213-20261121.php';
$oldSha='d79b15e15c3211b303c2ca68b8d3d6d577f6dc9cb20b4eb425840d87e16670ee';
$newSha='62959341a9a0222be6db38b863da009dcd4b367d001de52c2ba790c2896142a5';
function fail213(string $code):never{echo "TTS_V213_FAIL_".$code."\n";exit(20);}
if(!is_file($target)||!is_file(__DIR__.'/budget-v213.php.gz.b64'))fail213('INPUT');
$baseline=file_get_contents($target);
if(!is_string($baseline)||hash('sha256',$baseline)!==$oldSha)fail213('BASELINE_HASH');
$encoded=trim((string)file_get_contents(__DIR__.'/budget-v213.php.gz.b64'));
$bytes=base64_decode($encoded,true);
$newCode=is_string($bytes)?gzdecode($bytes):false;
if(!is_string($newCode)||hash('sha256',$newCode)!==$newSha)fail213('PAYLOAD_HASH');
if(in_array('--check',$argv,true)){echo "TTS_V213_CHECK_OK\n";exit(0);}
if(in_array('--stage-test',$argv,true)){
  $folder=__DIR__.'/qa/api';
  if(!is_dir($folder)&&!mkdir($folder,0700,true))fail213('QA_DIRECTORY');
  if(file_put_contents($folder.'/budget.php',$newCode,LOCK_EX)!==strlen($newCode))fail213('QA_BUDGET');
  if(!copy($root.'/api/bootstrap.php',$folder.'/bootstrap.php'))fail213('QA_BOOTSTRAP');
  echo "TTS_V213_QA_READY\n";exit(0);
}
if(!in_array('--apply',$argv,true)){echo "USAGE --check | --stage-test | --apply\n";exit(1);}
if(!is_file($ledger))fail213('LEDGER_MISSING');
try{
  $db=new PDO('sqlite:'.$ledger,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>10]);
  $db->exec('PRAGMA query_only=ON');
  if((int)$db->query("SELECT COUNT(*) FROM reservations WHERE status='pending'")->fetchColumn()!==0)fail213('ACTIVE_GENERATION');
  if((int)$db->query("SELECT COUNT(*) FROM reservations WHERE status IN ('ambiguous','overrun') AND (charged IS NULL OR charged<=0 OR (status='ambiguous' AND charged+0.00000001<reserved))")->fetchColumn()!==0)fail213('UNPRICED_RESERVATION');
  $db=null;
}catch(Throwable $e){fail213('LEDGER_CHECK');}
if(!is_file($backup)&&!copy($target,$backup))fail213('BACKUP');
@chmod($backup,0600);
$tmp=dirname($target).'/.budget-v213-'.getmypid().'.tmp';
if(file_put_contents($tmp,$newCode,LOCK_EX)!==strlen($newCode)||!rename($tmp,$target)||hash_file('sha256',$target)!==$newSha)fail213('WRITE');
echo "TTS_V213_DEPLOY_OK KNOWN_OVERRUN_ACCOUNTED=YES UNPRICED_FAIL_CLOSED=YES CAPS_UNCHANGED=YES BACKUP=YES\n";
