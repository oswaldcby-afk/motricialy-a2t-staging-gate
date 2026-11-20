<?php
declare(strict_types=1);
/* Stage A9.6 read-only follow-up: verify durable server-private DB config and privileges.
   No API requests, no writes, no secrets or raw grants in stdout. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(9);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(10);
$dir='/home/u342033576/.motricialy-a2t-stage-a9';
$cfg=$dir.'/mysql-a9.json';
$marker=$dir.'/a9-6-read-only.done';
if(is_file($marker)){echo "READ_ONLY_ALREADY_COMPLETED\n";exit;}
if(!is_file($cfg)||is_link($cfg)||(fileperms($dir)&0777)!==0700||(fileperms($cfg)&0777)!==0600){echo "FAIL_PRIVATE_CONFIG\n";exit(11);}
try{
 $data=json_decode((string)file_get_contents($cfg),true,512,JSON_THROW_ON_ERROR);
 if(!is_array($data)||($data['environment']??'')!=='isolated-a9-test'||($data['paid_enabled']??true)!==false||
  ($data['dsn']??'')!=='mysql:host=localhost;dbname=u342033576_a2t_stage_a9;charset=utf8mb4'||
  ($data['user']??'')!=='u342033576_a2t_a9_user'||strlen((string)($data['password']??''))<25)throw new RuntimeException('configuration');
 $db=new PDO($data['dsn'],$data['user'],$data['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
   PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>8]);
 unset($data);
 if((int)$db->query('SELECT 1')->fetchColumn()!==1)throw new RuntimeException('SQL check');
 $rows=$db->query("SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA='u342033576_a2t_stage_a9' AND TABLE_NAME IN('a2t_budget_pool','a2t_daily_quota','a2t_jobs')")->fetchAll();
 if(count($rows)!==3||count(array_filter($rows,static fn($x)=>$x['ENGINE']==='InnoDB'))!==3)throw new RuntimeException('schema');
 $counts=[];
 foreach(['a2t_budget_pool','a2t_daily_quota','a2t_jobs'] as $table) {
   $counts[$table]=(int)$db->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
 }
 $g=$db->query('SHOW GRANTS')->fetchAll(PDO::FETCH_NUM);
 $ownOnly=true;$grantCount=0;
 foreach($g as $item){
   $grant=strtolower((string)($item[0]??''));
   $clean=str_replace(['`',"'"],'', $grant);
   $grantCount++;
   $onGlobal=str_contains($clean,'on *.*');
   $usage=str_contains($clean,'grant usage on *.*');
   $onExpected=str_contains($clean,'on u342033576_a2t_stage_a9.*');
   if(($onGlobal&&!$usage)||(!$onGlobal&&!$onExpected))$ownOnly=false;
 }
 @file_put_contents($marker,'done',LOCK_EX);@chmod($marker,0600);
 echo json_encode(['stage'=>'A9.6','status'=>'READ_ONLY_PASS',
  'secret_config_outside_docroot'=>true,'config_mode'=>'0600','temporary_transport_key_gone'=>!is_file($dir.'/transport-private.pem'),
  'connected_with_persisted_config'=>true,'all_three_tables_innodb'=>true,
  'row_counts'=>$counts,'grant_count'=>$grantCount,'only_expected_database_privileges'=>$ownOnly,
  'paid_operations_enabled'=>false,'openai_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
}catch(Throwable $e){echo json_encode(['stage'=>'A9.6','status'=>'READ_ONLY_FAIL','exception_class'=>get_class($e)]).PHP_EOL;exit(12);}
