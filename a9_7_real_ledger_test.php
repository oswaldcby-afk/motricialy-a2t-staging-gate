<?php
declare(strict_types=1);
/* Stage A9.7: real PRIVATE A2TLedger and isolated Hostinger MySQL. CLI-only.
   Test runs only against an EMPTY isolated a9 DB, then removes exactly its own rows.
   No API calls, no user data, no secrets logged. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$ledgerPath=$root.'/app-a9-7-full/private-server/ledger.php';
$configPath=$root.'/mysql-a9.json';
$mark=$root.'/a9-7-ledger-qa.done';
if(is_file($mark)){echo "A9_7_TEST_ALREADY_COMPLETED\n";exit;}
if(!is_file($ledgerPath)||!is_file($configPath)||is_link($ledgerPath)||is_link($configPath)||
 (fileperms($ledgerPath)&0777)!==0600||(fileperms($configPath)&0777)!==0600)exit(93);
if(hash_file('sha256',$ledgerPath)!=='90515ca4e986e2eb48a8d936b7d3b8385a13ccf9b14d360f99b05717e37d0185')exit(94);
define('A2T_ENTRY',true);
define('A2T_DURATION_SECONDS',900);
define('A2T_TOTAL_MICRO',15000000);
define('A2T_PUBLIC_MICRO',10000000);
function a2t_env(string $name):string {
 if($name==='A2T_BILLING_DAY')return '1';
 if($name==='A2T_PAUSE_AI')return '1';
 return '';
}
require_once $ledgerPath;
$ids=[];$subjects=[];$cycle='';$date='';
$pdo=null;$cleaned=false;$passed=false;$result=[];
try{
 $c=json_decode((string)file_get_contents($configPath),true,512,JSON_THROW_ON_ERROR);
 if(($c['environment']??'')!=='isolated-a9-test'||($c['paid_enabled']??true)!==false||
 ($c['dsn']??'')!=='mysql:host=localhost;dbname=u342033576_a2t_stage_a9;charset=utf8mb4'||
 ($c['user']??'')!=='u342033576_a2t_a9_user')throw new RuntimeException('config');
 $pdo=new PDO($c['dsn'],$c['user'],$c['password'],[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>8]);
 unset($c);
 $pdo->exec("SET time_zone='+00:00'");
 foreach(['a2t_budget_pool','a2t_daily_quota','a2t_jobs'] as $table){
  if((int)$pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn()!==0)throw new RuntimeException('not isolated empty');
 }
 $date=(new DateTimeImmutable('now',new DateTimeZone('UTC')))->format('Y-m-d');
 $cycle=(new DateTimeImmutable('now',new DateTimeZone('UTC')))->format('Y-m-01');
 $subjects=[hash('sha256','a9-7-v-'.bin2hex(random_bytes(12))),hash('sha256','a9-7-ip-'.bin2hex(random_bytes(12)))];
 $ledger=new A2TLedger($pdo);
 $micro=A2TLedger::audioReserve(10);
 if($micro<1000||$micro>3000)throw new RuntimeException('reserve formula');
 for($i=0;$i<2;$i++){
  $ids[]=bin2hex(random_bytes(16));
  $returned=$ledger->reserve($ids[$i],$subjects,'transcribe',10,$micro);
  if($returned!==$ids[$i])throw new RuntimeException('bad reservation');
 }
 $limitDenied=false;
 try{$ledger->reserve(bin2hex(random_bytes(16)),$subjects,'transcribe',10,$micro);}catch(DomainException $e){$limitDenied=true;}
 if(!$limitDenied)throw new RuntimeException('daily quota exceeded');
 $ledger->settle($ids[0],600);
 $repeatDenied=false;
 try{$ledger->settle($ids[0],600);}catch(RuntimeException $e){$repeatDenied=true;}
 if(!$repeatDenied)throw new RuntimeException('double settle accepted');
 $ledger->markUncertain($ids[1]);
 $rows=$pdo->query('SELECT pool_name,spent_micro,reserved_micro FROM a2t_budget_pool')->fetchAll();
 if(count($rows)!==2)throw new RuntimeException('budget row count');
 foreach($rows as $row){
  if((int)$row['spent_micro']!==600||(int)$row['reserved_micro']!==$micro)throw new RuntimeException('money mismatch');
 }
 $states=$pdo->query('SELECT status FROM a2t_jobs ORDER BY status')->fetchAll(PDO::FETCH_COLUMN);
 if($states!==['settled','uncertain'])throw new RuntimeException('states mismatch');
 $result=['stage'=>'A9.7','status'=>'REAL_LEDGER_PASS','source_sha256'=>hash_file('sha256',$ledgerPath),
  'real_hostinger_pdo'=>true,'tables_checked'=>3,'two_reservations'=>'PASS','third_same_visitor_denied'=>true,
  'settlement'=>'PASS','double_settle_denied'=>$repeatDenied,'uncertain_funds_preserved'=>true,
  'budget_total_usd'=>15,'public_pool_usd'=>10,'openai_calls'=>0,'paid_api_disabled'=>true];
 $passed=true;
}catch(Throwable $e){$result=['stage'=>'A9.7','status'=>'FAIL','type'=>get_class($e),'no_api_calls'=>true];}
finally{
 if($pdo instanceof PDO && $cycle!==''&&$date!==''&&count($subjects)===2){
  try{
   $pdo->beginTransaction();
   if($ids){
    $placeholders=implode(',',array_fill(0,count($ids),'?'));
    $q=$pdo->prepare('DELETE FROM a2t_jobs WHERE id IN('.$placeholders.')');
    $q->execute($ids);
   }
   $q=$pdo->prepare('DELETE FROM a2t_daily_quota WHERE day_utc=? AND subject_hash IN(?,?)');
   $q->execute([$date,$subjects[0],$subjects[1]]);
   $q=$pdo->prepare("DELETE FROM a2t_budget_pool WHERE cycle_start=? AND pool_name IN ('__total__','public')");
   $q->execute([$cycle]);
   $pdo->commit();
   $counts=[];
   foreach(['a2t_budget_pool','a2t_daily_quota','a2t_jobs'] as $table)$counts[$table]=(int)$pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
   $cleaned=!array_filter($counts);
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$cleaned=false;}
 }
}
$result['isolation_cleanup_zero_rows']=$cleaned;
if(!$cleaned||!$passed){echo json_encode($result,JSON_UNESCAPED_SLASHES).PHP_EOL;exit(97);}
@file_put_contents($mark,'done',LOCK_EX);@chmod($mark,0600);
echo json_encode($result,JSON_UNESCAPED_SLASHES).PHP_EOL;
