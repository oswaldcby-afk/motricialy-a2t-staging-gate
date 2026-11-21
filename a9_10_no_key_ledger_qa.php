<?php
declare(strict_types=1);
/* A9.10: one unauthenticated provider call coupled to REAL isolated Hostinger ledger.
 * Intentionally NO API KEY; verifies fail-closed/uncertain reservation cleanup.
 * CLI only, isolated empty test database. Never touches WordPress DB or customer audio.
 */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$private=$root.'/app-a9-7-full/private-server';
$mark=$root.'/a9-10-no-key-provider.done';
if(is_file($mark)){echo "A9_10_TEST_ALREADY_COMPLETED\n";exit;}
$files=['ledger.php'=>'90515ca4e986e2eb48a8d936b7d3b8385a13ccf9b14d360f99b05717e37d0185',
'media.php'=>'03420827736b5097be09ece684516c28a6610496cc08225c1ea783aeb65a0265',
'provider.php'=>'03cada47559c5e65f0d8ac849278ac667d5cbc25b9fc0a6a222c3d75ddfc3dff'];
foreach($files as $name=>$hash)if(!is_file($private.'/'.$name)||hash_file('sha256',$private.'/'.$name)!==$hash)exit(93);
if(!is_file($root.'/mysql-a9.json')||(fileperms($root.'/mysql-a9.json')&0777)!==0600)exit(94);
define('A2T_ENTRY',true);
define('A2T_STT_MODEL','gpt-transcribe');define('A2T_DURATION_SECONDS',900);
define('A2T_UPLOAD_BYTES',24000000);define('A2T_TOTAL_MICRO',15000000);define('A2T_PUBLIC_MICRO',10000000);
function a2t_env(string $name):string {
 if($name==='A2T_BILLING_DAY')return '1';
 if($name==='A2T_PAUSE_AI')return '1';
 return '';
}
require_once $private.'/vendor/autoload.php';
require_once $private.'/media.php';require_once $private.'/ledger.php';require_once $private.'/provider.php';
$pdo=null;$id=null;$subjects=[];$date='';$cycle='';
$dir=null;
$out=['stage'=>'A9.10','scope'=>'actual_php_provider_unauthenticated_and_real_mysql',
 'paid_provider_requests'=>0,'paid_api_key_supplied'=>false,'status'=>'FAILED'];
try{
 $cfg=json_decode((string)file_get_contents($root.'/mysql-a9.json'),true,512,JSON_THROW_ON_ERROR);
 if(($cfg['environment']??'')!=='isolated-a9-test'||($cfg['paid_enabled']??true)!==false||
 ($cfg['dsn']??'')!=='mysql:host=localhost;dbname=u342033576_a2t_stage_a9;charset=utf8mb4'
 ||($cfg['user']??'')!=='u342033576_a2t_a9_user')throw new RuntimeException('isolated_config');
 $pdo=new PDO($cfg['dsn'],$cfg['user'],$cfg['password'],[
 PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,
 PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>8]);unset($cfg);
 foreach(['a2t_budget_pool','a2t_daily_quota','a2t_jobs'] as $table){
  if((int)$pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn()!==0)throw new RuntimeException('db_not_empty');
 }
 $utc=new DateTimeImmutable('now',new DateTimeZone('UTC'));
 $date=$utc->format('Y-m-d');$cycle=$utc->format('Y-m-01');
 $dir=$root.'/a9-10-no-key-'.bin2hex(random_bytes(8));
 if(!mkdir($dir,0700))throw new RuntimeException('temp_dir');
 @chmod($dir,0700);
 $rate=8000;$seconds=2;$samples=$rate*$seconds;$data='';
 for($i=0;$i<$samples;$i++)$data.=pack('v',((int)(sin($i*2*M_PI*440/$rate)*2000))&65535);
 $wav='RIFF'.pack('V',36+strlen($data)).'WAVEfmt '.pack('VvvVVvv',16,1,1,$rate,$rate*2,2,16).'data'.pack('V',strlen($data)).$data;
 $file=$dir.'/fixture.wav';
 if(file_put_contents($file,$wav,LOCK_EX)!==strlen($wav))throw new RuntimeException('wav_write');
 @chmod($file,0600);
 $info=a2t_prepare_direct($file,'wav');
 $ledger=new A2TLedger($pdo);
 $subjects=[hash('sha256','a910-v-'.bin2hex(random_bytes(14))),hash('sha256','a910-ip-'.bin2hex(random_bytes(14)))];
 $id=bin2hex(random_bytes(16));
 $reserve=A2TLedger::audioReserve($info['seconds_for_reservation']);
 if($reserve<1||$reserve>5000)throw new RuntimeException('reservation_bad');
 $ledger->reserve($id,$subjects,'transcribe',$info['seconds_for_reservation'],$reserve);
 $out['reserved_budget_micro']=$reserve;
 $out['reserve_success']=true;
 $rejected=false;$code401=false;
 try{a2t_transcribe_provider($wav,'wav',$info['provider_mime'],[],['en'],'');}
 catch(RuntimeException $e){$rejected=true;$code401=str_contains($e->getMessage(),'code 401');}
 $out['provider_rejected_without_key']=$rejected;
 $out['provider_rejected_http_401']=$code401;
 if(!$rejected||!$code401)throw new RuntimeException('provider_did_not_fail_as_expected');
 $ledger->markUncertain($id);
 $q=$pdo->prepare('SELECT status,reserved_micro FROM a2t_jobs WHERE id=?');$q->execute([$id]);$row=$q->fetch();
 $out['uncertain_reservation_preserved']=is_array($row)&&$row['status']==='uncertain'&&(int)$row['reserved_micro']===$reserve;
 if(!$out['uncertain_reservation_preserved'])throw new RuntimeException('uncertain_lost');
 $out['status']='NO_KEY_PROVIDER_LEDGER_PASS';
}catch(Throwable $e){$out['status']='NO_KEY_PROVIDER_LEDGER_FAILED';$out['error_type']=get_class($e);
 $out['error_category']=in_array($e->getMessage(),['provider_did_not_fail_as_expected','uncertain_lost'],true)?$e->getMessage():'other';
}finally{
 if(is_string($dir)&&is_dir($dir)){if(is_file($dir.'/fixture.wav'))@unlink($dir.'/fixture.wav');@rmdir($dir);}
 $out['media_deleted']=!is_string($dir)||!file_exists($dir);
 if($pdo instanceof PDO&&$id!==null&&count($subjects)===2&&$cycle!==''&&$date!==''){
  try{
   $pdo->beginTransaction();
   $q=$pdo->prepare('DELETE FROM a2t_jobs WHERE id=?');$q->execute([$id]);
   $q=$pdo->prepare('DELETE FROM a2t_daily_quota WHERE day_utc=? AND subject_hash IN(?,?)');$q->execute([$date,$subjects[0],$subjects[1]]);
   $q=$pdo->prepare("DELETE FROM a2t_budget_pool WHERE cycle_start=? AND pool_name IN ('__total__','public')");$q->execute([$cycle]);
   $pdo->commit();
   $counts=[];foreach(['a2t_budget_pool','a2t_daily_quota','a2t_jobs'] as $t)$counts[$t]=(int)$pdo->query('SELECT COUNT(*) FROM '.$t)->fetchColumn();
   $out['db_cleaned']=array_sum($counts)===0;
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$out['db_cleaned']=false;}
 }else $out['db_cleaned']=$pdo instanceof PDO?false:null;
}
if($out['status']==='NO_KEY_PROVIDER_LEDGER_PASS'&&$out['db_cleaned']===true&&$out['media_deleted']===true){
 @file_put_contents($mark,'done',LOCK_EX);@chmod($mark,0600);
}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']!=='NO_KEY_PROVIDER_LEDGER_PASS'||$out['db_cleaned']!==true||!$out['media_deleted'])exit(97);
