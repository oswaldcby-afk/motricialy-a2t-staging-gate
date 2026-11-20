<?php
declare(strict_types=1);
/* A9.8 final read-only verification of closed public gate and isolated resources. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$path='https://motricialy.com/staging/audio-to-text-a9-private-gate-20261120/';
$rules=(string)@file_get_contents(__DIR__.'/.htaccess');
$r=['stage'=>'A9.8','status'=>'INCOMPLETE','paid_api_calls'=>0];
try{
 $r['apache_deny_all_present']=strpos($rules,'Require all denied')!==false;
 $r['apache_exception_absent']=strpos($rules,'a9_8_gateway.php')===false;
 $r['public_gateway_file_removed']=!file_exists(__DIR__.'/a9_8_gateway.php');
 $r['private_test_key_destroyed']=!file_exists($root.'/a9-8-http-key.hex');
 $r['https_qa_pass_marker']=is_file($root.'/a9-8-https-qa.done')
  &&trim((string)file_get_contents($root.'/a9-8-https-qa.done'))==='done';
 $r['private_php_ledger_integrity']=hash_file('sha256',$root.'/app-a9-7-full/private-server/ledger.php')
  ==='90515ca4e986e2eb48a8d936b7d3b8385a13ccf9b14d360f99b05717e37d0185';
 $c=json_decode((string)file_get_contents($root.'/mysql-a9.json'),true,512,JSON_THROW_ON_ERROR);
 if(($c['environment']??'')!=='isolated-a9-test'||($c['paid_enabled']??true)!==false||
 ($c['dsn']??'')!=='mysql:host=localhost;dbname=u342033576_a2t_stage_a9;charset=utf8mb4')
 throw new RuntimeException('isolated_db_config_changed');
 $pdo=new PDO($c['dsn'],$c['user'],$c['password'],[
 PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,
 PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>7]);
 unset($c);
 $counts=[];
 foreach(['a2t_budget_pool','a2t_daily_quota','a2t_jobs'] as $t)
 $counts[$t]=(int)$pdo->query('SELECT COUNT(*) FROM '.$t)->fetchColumn();
 $r['isolated_database_rows']=$counts;
 $r['test_db_empty']=array_sum($counts)===0;
 if(!function_exists('curl_init'))throw new RuntimeException('curl_unavailable');
 $status=[];
 foreach(['a9_8_gateway.php','a9_7_core_audit.php'] as $name){
  $ch=curl_init($path.$name);
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>false,
   CURLOPT_TIMEOUT=>12,CURLOPT_CONNECTTIMEOUT=>5,
   CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,
   CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0]);
  $response=curl_exec($ch);
  $errno=curl_errno($ch);$http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
  curl_close($ch);
  $status[$name]=['https_errno'=>$errno,'http_status'=>$http,'denied'=>$errno===0&&$http===403];
 }
 $r['public_https_denial']=$status;
 $all=true;
 foreach($r as $k=>$v)if(in_array($k,['apache_deny_all_present','apache_exception_absent','public_gateway_file_removed','private_test_key_destroyed','https_qa_pass_marker','private_php_ledger_integrity','test_db_empty'],true)&&$v!==true)$all=false;
 foreach($status as $v)if(!$v['denied'])$all=false;
 $r['status']=$all?'FINAL_ISOLATION_PASS':'FINAL_ISOLATION_FAILED';
}catch(Throwable $e){$r['failure_type']=get_class($e);$r['status']='FINAL_AUDIT_EXCEPTION';}
echo json_encode($r,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($r['status']!=='FINAL_ISOLATION_PASS')exit(97);
