<?php
declare(strict_types=1);
/* A9.15 provider + isolated ledger paid QA; one-time, no user data, no public route. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$private=$root.'/app-a9-7-full/private-server';
$mark=$root.'/a9-15-text-provider.once';
if(is_file($mark)){echo "ALREADY_ATTEMPTED\n";exit;}
$cfg=$root.'/mysql-a9.json';$key=$root.'/a9-11-openai-key.private';
if(!is_file($cfg)||!is_file($key)||(fileperms($cfg)&0777)!==0600||
 (fileperms($key)&0777)!==0600||!is_dir($private))exit(92);
$fp=@fopen($mark,'x');if(!$fp)exit(93);@chmod($mark,0600);fwrite($fp,'started');fclose($fp);
define('A2T_ENTRY',true);
define('A2T_STT_MODEL','gpt-transcribe');define('A2T_TEXT_MODEL','gpt-6-luna');
define('A2T_TOTAL_MICRO',15000000);define('A2T_PUBLIC_MICRO',10000000);define('A2T_DURATION_SECONDS',900);
function a2t_env(string $name):string{
 if($name==='A2T_BILLING_DAY')return '1';
 if($name==='OPENAI_API_KEY')return trim((string)file_get_contents('/home/u342033576/.motricialy-a2t-stage-a9/a9-11-openai-key.private'));
 return '';
}
require_once $private.'/ledger.php';
require_once $private.'/provider.php';
$out=['stage'=>'A9.15','mode'=>'isolated_staging_database','status'=>'STARTED','paid_calls'=>0,
  'model'=>'gpt-6-luna','no_transcripts_logged'=>true,'file_uploads'=>0];
$pdo=null;$ids=[];$subjects=[];$date='';$cycle='';$success=false;
try{
 $c=json_decode((string)file_get_contents($cfg),true,512,JSON_THROW_ON_ERROR);
 if(($c['environment']??'')!=='isolated-a9-test'||($c['paid_enabled']??true)!==false||
 ($c['dsn']??'')!=='mysql:host=localhost;dbname=u342033576_a2t_stage_a9;charset=utf8mb4')throw new RuntimeException('test_db_not_isolated');
 $pdo=new PDO($c['dsn'],$c['user'],$c['password'],[
   PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,
   PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>7]);
 unset($c);
 foreach(['a2t_budget_pool','a2t_daily_quota','a2t_jobs'] as $table)
  if((int)$pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn()!==0)throw new RuntimeException('dirty_test_db');
 $utc=new DateTimeImmutable('now',new DateTimeZone('UTC'));
 $date=$utc->format('Y-m-d');$cycle=$utc->format('Y-m-01');
 $subjects=[hash('sha256','qa-v-'.bin2hex(random_bytes(14))),hash('sha256','qa-i-'.bin2hex(random_bytes(14)))];
 $ledger=new A2TLedger($pdo);
 $fixture="On November 2, Dana said the blue valve read 17 PSI. No action items were assigned.";
 foreach(['clean','summary'] as $mode){
  $id=bin2hex(random_bytes(16));$ids[]=$id;
  $reserve=A2TLedger::textReserve();
  if($reserve!==20000)throw new RuntimeException('text_reservation_unexpected');
  $ledger->reserve($id,$subjects,$mode,0,$reserve);
  $out['paid_calls']++;
  try {
    $resp=a2t_process_provider($fixture,$mode);
    $text=(string)($resp['text']??'');
    $u=$resp['usage']??null;
    if(!is_array($u)||!is_numeric($u['input_tokens']??null)||!is_numeric($u['output_tokens']??null))
       throw new RuntimeException('usage_not_recognized');
    $in=(int)$u['input_tokens'];$outTokens=(int)$u['output_tokens'];
    if($in<1||$in>100000||$outTokens<1||$outTokens>1200)
       throw new RuntimeException('tokens_out_of_bounds');
    $micro=(int)ceil($in*.1+$outTokens*.5);
    if($micro<=0||$micro>$reserve)throw new RuntimeException('cost_above_reserve');
    if(mb_strlen(trim($text),'UTF-8')<10)throw new RuntimeException('text_empty');
    $ledger->settle($id,$micro);
    $stmt=$pdo->prepare('SELECT status,actual_micro FROM a2t_jobs WHERE id=?');
    $stmt->execute([$id]);$job=$stmt->fetch();
    if(!is_array($job)||$job['status']!=='settled'||(int)$job['actual_micro']!==$micro)
      throw new RuntimeException('settlement_mismatch');
    $out['tests'][$mode]=[
      'provider_success'=>true,'has_output'=>true,'input_tokens'=>$in,'output_tokens'=>$outTokens,
      'settled_micro_usd'=>$micro,'reserved_micro_usd'=>$reserve,
      'preserves_Dana'=>stripos($text,'Dana')!==false,
      'preserves_17'=>str_contains($text,'17'),
      'settled_on_db'=>true];
  } catch(Throwable $e) {
    try{$ledger->markUncertain($id);}catch(Throwable $ignore){}
    throw $e;
  }
 }
 $success=true;$out['status']='TEXT_PROVIDER_LEDGER_PASS';
}catch(Throwable $e){
 $out['status']='TEXT_PROVIDER_LEDGER_FAILED';
 $out['error_type']=get_class($e);
 $out['error_category']=in_array($e->getMessage(),[
 'dirty_test_db','usage_not_recognized','tokens_out_of_bounds','cost_above_reserve','text_empty','settlement_mismatch'],true)?$e->getMessage():'other';
}finally{
 if($success&&$pdo instanceof PDO){
   try{
    $pdo->beginTransaction();
    $p=$pdo->prepare('DELETE FROM a2t_jobs WHERE id=? AND status=?');
    foreach($ids as $id)$p->execute([$id,'settled']);
    $q=$pdo->prepare('DELETE FROM a2t_daily_quota WHERE day_utc=? AND subject_hash IN(?,?)');
    $q->execute([$date,$subjects[0],$subjects[1]]);
    $q=$pdo->prepare("DELETE FROM a2t_budget_pool WHERE cycle_start=? AND pool_name IN ('__total__','public')");
    $q->execute([$cycle]);$pdo->commit();
    $count=0;foreach(['a2t_budget_pool','a2t_daily_quota','a2t_jobs'] as $table)
      $count+=(int)$pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
    $out['test_database_cleaned']=$count===0;
   }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$out['test_database_cleaned']=false;}
 }else $out['test_database_cleaned']=false;
}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']!=='TEXT_PROVIDER_LEDGER_PASS'||!($out['test_database_cleaned']??false))exit(97);
