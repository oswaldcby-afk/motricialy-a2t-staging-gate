<?php
declare(strict_types=1);
/* A9.9 final read-only security audit; no API or customer data. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$private=$root.'/app-a9-7-full/private-server';
$rules=(string)@file_get_contents(__DIR__.'/.htaccess');
$out=['stage'=>'A9.9','paid_api_calls'=>0,'status'=>'FINAL_AUDIT_INCOMPLETE'];
try{
 $out['deny_all']=strpos($rules,'Require all denied')!==false;
 $out['no_gateway_exception']=strpos($rules,'a9_9_upload_gateway.php')===false&&strpos($rules,'a9_9_ini_probe.php')===false;
 $out['no_temp_php_overrides']=strpos($rules,'php_value')===false&&!file_exists(__DIR__.'/.user.ini');
 $out['no_gateway_files']=!file_exists(__DIR__.'/a9_9_upload_gateway.php')&&!file_exists(__DIR__.'/a9_9_ini_probe.php');
 $out['private_auth_key_destroyed']=!file_exists($root.'/a9-9-http-key.hex');
 $out['vendor_autoload_present']=is_file($private.'/vendor/autoload.php');
 $out['private_dependency_hashes_match']=
  hash_file('sha256',$private.'/composer.json')==='0fd343c09bd85f349438b3720ea36e91b87d2ec4270d5cfd09572dbf7a776b45'
  &&hash_file('sha256',$private.'/composer.lock')==='8b8a6f616c4a075c57908d461f9d633b0496796d61e74f134240080ee5927698';
 $out['media_source_unchanged']=hash_file('sha256',$private.'/media.php')==='03420827736b5097be09ece684516c28a6610496cc08225c1ea783aeb65a0265';
 if($out['vendor_autoload_present']){
  require $private.'/vendor/autoload.php';
  $out['getID3_class_available']=class_exists('getID3');
 }
 $cfg=json_decode((string)file_get_contents($root.'/mysql-a9.json'),true,512,JSON_THROW_ON_ERROR);
 if(($cfg['environment']??'')!=='isolated-a9-test'||($cfg['paid_enabled']??true)!==false||
 ($cfg['dsn']??'')!=='mysql:host=localhost;dbname=u342033576_a2t_stage_a9;charset=utf8mb4')
 throw new RuntimeException('unexpected_isolated_db');
 $db=new PDO($cfg['dsn'],$cfg['user'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>8]);
 unset($cfg);
 $counts=[];
 foreach(['a2t_budget_pool','a2t_daily_quota','a2t_jobs'] as $t)$counts[$t]=(int)$db->query('SELECT COUNT(*) FROM '.$t)->fetchColumn();
 $out['isolated_database_rows']=$counts;$out['database_remains_empty']=array_sum($counts)===0;
 $out['real_upload_success_marker']=is_file($root.'/a9-9-https-upload-qa.done')&&trim((string)file_get_contents($root.'/a9-9-https-upload-qa.done'))==='done';
 $out['test_files_left_behind']=count(glob($root.'/a9-9-http-upload-*')?:[]) +count(glob($root.'/a9-9-client-*')?:[]);
 $all=true;foreach(['deny_all','no_gateway_exception','no_temp_php_overrides','no_gateway_files','private_auth_key_destroyed',
  'vendor_autoload_present','private_dependency_hashes_match','media_source_unchanged',
  'getID3_class_available','database_remains_empty','real_upload_success_marker'] as $k)if(($out[$k]??false)!==true)$all=false;
 if($out['test_files_left_behind']!==0)$all=false;
 $out['status']=$all?'A9_9_FINAL_ISOLATION_PASS':'A9_9_FINAL_ISOLATION_FAIL';
}catch(Throwable $e){$out['status']='A9_9_FINAL_AUDIT_ERROR';$out['failure_type']=get_class($e);}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']!=='A9_9_FINAL_ISOLATION_PASS')exit(97);
