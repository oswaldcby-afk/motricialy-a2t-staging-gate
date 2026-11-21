<?php
declare(strict_types=1);
/* Stage A9.10 final isolation audit, read-only and CLI-only. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
$base='/home/u342033576/.motricialy-a2t-stage-a9';
$p=$base.'/app-a9-7-full/private-server';
$rules=(string)@file_get_contents(__DIR__.'/.htaccess');
$out=['stage'=>'A9.10','audit'=>'final_php_hostinger_isolation','paid_calls'=>0];
try{
 $out['staging_closed']=strpos($rules,'Require all denied')!==false&&strpos($rules,'Require all granted')===false;
 $out['paid_api_disabled']=getenv('A2T_ENABLE_LIVE')!=='1';
 $hashes=[
  'ledger.php'=>'90515ca4e986e2eb48a8d936b7d3b8385a13ccf9b14d360f99b05717e37d0185',
  'media.php'=>'03420827736b5097be09ece684516c28a6610496cc08225c1ea783aeb65a0265',
  'provider.php'=>'03cada47559c5e65f0d8ac849278ac667d5cbc25b9fc0a6a222c3d75ddfc3dff',
  'bootstrap.php'=>'1e33ef157a57c898e17bf89fd45bd147712df405b10049fe5c3de0e2efaa8698'
 ];
 $out['source_integrity']=true;
 foreach($hashes as $file=>$sha)if(!is_file($p.'/'.$file)||hash_file('sha256',$p.'/'.$file)!==$sha)$out['source_integrity']=false;
 $out['getid3_available']=is_file($p.'/vendor/autoload.php');
 if($out['getid3_available']){require_once $p.'/vendor/autoload.php';$out['getid3_available']=class_exists('getID3');}
 $cfgp=$base.'/mysql-a9.json';
 if(!is_file($cfgp)||is_link($cfgp)||(fileperms($cfgp)&0777)!==0600)throw new RuntimeException('config_permissions');
 $c=json_decode((string)file_get_contents($cfgp),true,512,JSON_THROW_ON_ERROR);
 if(($c['environment']??'')!=='isolated-a9-test'||($c['paid_enabled']??true)!==false||
 ($c['dsn']??'')!=='mysql:host=localhost;dbname=u342033576_a2t_stage_a9;charset=utf8mb4')throw new RuntimeException('db_not_isolated');
 $db=new PDO($c['dsn'],$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>8]);unset($c);
 $counts=[];
 foreach(['a2t_budget_pool','a2t_daily_quota','a2t_jobs'] as $t)$counts[$t]=(int)$db->query('SELECT COUNT(*) FROM '.$t)->fetchColumn();
 $out['isolated_db_rows']=$counts;
 $out['db_empty']=array_sum($counts)===0;
 $left=0;
 foreach(['a9-10-direct-*','a9-10-no-key-*'] as $pattern){
  foreach(glob($base.'/'.$pattern)?:[] as $f)if(is_dir($f))$left++;
 }
 $out['media_temp_dirs_left']=$left;
 $mark=$base.'/a9-10-no-key-provider.done';
 $out['no_key_failure_qa_marker']=is_file($mark)&&trim((string)file_get_contents($mark))==='done';
 $ok=$out['staging_closed']&&$out['paid_api_disabled']&&$out['source_integrity']&&$out['getid3_available']&&
  $out['db_empty']&&$left===0&&$out['no_key_failure_qa_marker'];
 $out['status']=$ok?'A9_10_HOSTINGER_ISOLATION_PASS':'A9_10_HOSTINGER_ISOLATION_INCOMPLETE';
}catch(Throwable $e){$out['status']='A9_10_AUDIT_FAILED';$out['error_type']=get_class($e);}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']!=='A9_10_HOSTINGER_ISOLATION_PASS')exit(97);
