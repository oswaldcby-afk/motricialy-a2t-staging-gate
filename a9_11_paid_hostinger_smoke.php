<?php
declare(strict_types=1);
/* Stage A9.11 ONE authorized paid smoke against actual Hostinger PHP provider & isolated MySQL.
 * Uses existing public Motricialy TTS voice demo; no private-user files.
 * One-attempt marker BEFORE provider request: Cron cannot repeat charge.
 * Failed/ambiguous provider calls retain reserved capacity for review.
 */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$private=$root.'/app-a9-7-full/private-server';
$keyPath=$root.'/a9-11-openai-key.private';
$cfg=$root.'/mysql-a9.json';
$once=$root.'/a9-11-paid-provider.once';
$demo='/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech/voice-demos/alloy.mp3';
$hashes=['media.php'=>'03420827736b5097be09ece684516c28a6610496cc08225c1ea783aeb65a0265',
'ledger.php'=>'90515ca4e986e2eb48a8d936b7d3b8385a13ccf9b14d360f99b05717e37d0185',
'provider.php'=>'03cada47559c5e65f0d8ac849278ac667d5cbc25b9fc0a6a222c3d75ddfc3dff'];
if(!is_dir($root)||(fileperms($root)&0777)!==0700||
 !is_file($keyPath)||is_link($keyPath)||(fileperms($keyPath)&0777)!==0600||
 !is_file($cfg)||is_link($cfg)||(fileperms($cfg)&0777)!==0600||
 !is_file($demo)||is_link($demo)||filesize($demo)>450000)exit(93);
foreach($hashes as $file=>$hash)if(hash_file('sha256',$private.'/'.$file)!==$hash)exit(94);
$handle=@fopen($once,'x');if(!$handle){echo 'PAID_SMOKE_ALREADY_ATTEMPTED'.PHP_EOL;exit;}
@chmod($once,0600);fwrite($handle,'started');fclose($handle);
define('A2T_ENTRY',true);define('A2T_DURATION_SECONDS',900);define('A2T_UPLOAD_BYTES',24000000);
define('A2T_STT_MODEL','gpt-transcribe');define('A2T_TOTAL_MICRO',15000000);define('A2T_PUBLIC_MICRO',10000000);
function a2t_env(string $name):string{
 if($name==='A2T_BILLING_DAY')return '1';
 if($name==='A2T_PAUSE_AI')return '1';
 if($name==='OPENAI_API_KEY')return (string)file_get_contents('/home/u342033576/.motricialy-a2t-stage-a9/a9-11-openai-key.private');
 return '';
}
require_once $private.'/vendor/autoload.php';
require_once $private.'/media.php';
require_once $private.'/ledger.php';
require_once $private.'/provider.php';
$result=['stage'=>'A9.11','status'=>'STARTED','model'=>'gpt-transcribe','provider_calls_attempted'=>0,
 'paid_enabled_public'=>false,'request_retries'=>0,'isolated_db_only'=>true];
$pdo=null;$id=null;$subjectIds=[];$cycle='';$date='';$settled=false;
try{
 $c=json_decode((string)file_get_contents($cfg),true,512,JSON_THROW_ON_ERROR);
 if(($c['environment']??'')!=='isolated-a9-test'||($c['paid_enabled']??true)!==false||
 ($c['dsn']??'')!=='mysql:host=localhost;dbname=u342033576_a2t_stage_a9;charset=utf8mb4'||
 ($c['user']??'')!=='u342033576_a2t_a9_user')throw new RuntimeException('test_db_not_isolated');
 $pdo=new PDO($c['dsn'],$c['user'],$c['password'],[
 PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,
 PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>8]);
 unset($c);
 $pdo->exec("SET time_zone = '+00:00'");
 foreach(['a2t_budget_pool','a2t_daily_quota','a2t_jobs'] as $t)
 if((int)$pdo->query('SELECT COUNT(*) FROM '.$t)->fetchColumn()!==0)throw new RuntimeException('nonempty_test_database');
 $metadata=a2t_prepare_direct($demo,'mp3');
 $seconds=(int)$metadata['seconds_for_reservation'];
 if($seconds<1||$seconds>35||$metadata['media_bytes']>450000)
 throw new RuntimeException('demo_outside_small_test');
 $result['verified_demo_seconds']=$seconds;
 $micro=A2TLedger::audioReserve($seconds);
 if($micro>6000)throw new RuntimeException('reservation_above_smoke_cap');
 $result['reserved_micro_usd']=$micro;
 $utc=new DateTimeImmutable('now',new DateTimeZone('UTC'));
 $cycle=$utc->format('Y-m-01');$date=$utc->format('Y-m-d');
 $subjectIds=[hash('sha256','a911-public-demo-v:'.bin2hex(random_bytes(15))),
  hash('sha256','a911-public-demo-ip:'.bin2hex(random_bytes(15)))];
 $id=bin2hex(random_bytes(16));
 $ledger=new A2TLedger($pdo);
 $ledger->reserve($id,$subjectIds,'transcribe',$seconds,$micro);
 $result['reservation_created']=true;
 $audio=file_get_contents($demo);
 if(!is_string($audio)||strlen($audio)!==$metadata['media_bytes'])throw new RuntimeException('demo_read_mismatch');
 // ONE true API request, no retry. The provider sees a public Motricialy sample, not user audio.
 $result['provider_calls_attempted']=1;
 $answer=a2t_transcribe_provider($audio,'mp3',$metadata['provider_mime'],[],['en'],'');
 unset($audio);
 $transcript=(string)($answer['text']??'');
 $result['transcript_nonempty']=mb_strlen(trim($transcript),'UTF-8')>=12;
 $result['transcript_length']=mb_strlen($transcript,'UTF-8');
 $result['mentioned_brand']=stripos($transcript,'Motricialy')!==false;
 if(!$result['transcript_nonempty'])throw new RuntimeException('empty_transcript');
 $usage=$answer['usage']??null;
 if(!is_array($usage))throw new RuntimeException('provider_usage_missing');
 $result['provider_usage_type']=$usage['type']??null;
 $meter=$usage['seconds']??null;
 $result['provider_usage_seconds']=is_numeric($meter)?(float)$meter:null;
 if(($usage['type']??'')!=='duration'||!is_numeric($meter)||(float)$meter<0||(float)$meter>900)
 throw new RuntimeException('unexpected_pricing_meter');
 $actual=(int)ceil((float)$meter*75);
 $result['actual_meter_micro_usd']=$actual;
 if($actual>$micro)throw new RuntimeException('provider_charge_exceeded_reservation');
 $ledger->settle($id,$actual);$settled=true;
 $row=$pdo->prepare('SELECT status,reserved_micro,actual_micro,settled_at FROM a2t_jobs WHERE id=?');
 $row->execute([$id]);$job=$row->fetch();
 if(!is_array($job)||$job['status']!=='settled'||(int)$job['actual_micro']!==$actual||empty($job['settled_at']))
 throw new RuntimeException('settlement_mismatch');
 $result['state_transition']='reserved_to_settled';
 $result['status']='HOSTINGER_PAID_LEDGER_PASS';
}catch(Throwable $e){
 $result['status']='HOSTINGER_PAID_LEDGER_FAILED';
 $result['error_type']=get_class($e);
 $result['error_category']=in_array($e->getMessage(),['provider_usage_missing','unexpected_pricing_meter','provider_charge_exceeded_reservation','empty_transcript','settlement_mismatch'],true)?$e->getMessage():'other';
 if($pdo instanceof PDO && $id!==null && !$settled){
   try{(new A2TLedger($pdo))->markUncertain($id);$result['uncertain_reservation_retained']=true;}catch(Throwable $unused){}
 }
}finally{
 if($pdo instanceof PDO && $settled && $id!==null && count($subjectIds)===2 && $date!=='' && $cycle!==''){
  try{
   // Only clean own successful test rows; no other jobs existed at baseline.
   $pdo->beginTransaction();
   $q=$pdo->prepare('DELETE FROM a2t_jobs WHERE id=? AND status=?');$q->execute([$id,'settled']);
   $q=$pdo->prepare('DELETE FROM a2t_daily_quota WHERE day_utc=? AND subject_hash IN(?,?)');
   $q->execute([$date,$subjectIds[0],$subjectIds[1]]);
   $q=$pdo->prepare("DELETE FROM a2t_budget_pool WHERE cycle_start=? AND pool_name IN ('__total__','public')");
   $q->execute([$cycle]);$pdo->commit();
   $counts=[];foreach(['a2t_budget_pool','a2t_daily_quota','a2t_jobs'] as $t)
     $counts[$t]=(int)$pdo->query('SELECT COUNT(*) FROM '.$t)->fetchColumn();
   $result['database_cleanup_zero_rows']=array_sum($counts)===0;
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$result['database_cleanup_zero_rows']=false;}
 }
}
echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if($result['status']!=='HOSTINGER_PAID_LEDGER_PASS'||($result['database_cleanup_zero_rows']??false)!==true)exit(97);
