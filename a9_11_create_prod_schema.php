<?php
declare(strict_types=1);
/* A9.11 create only the three isolated A2T budget tables in dedicated production MySQL.
 * CLI only, no OpenAI, no WordPress DB, no sensitive output. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$cfgp=$root.'/a9-11-mysql-production.json';
$schema=$root.'/app-a9-7-full/private-server/schema.sql';
$mark=$root.'/a9-11-production-schema.done';
if(!is_file($cfgp)||is_link($cfgp)||(fileperms($cfgp)&0777)!==0600||
 !is_file($schema)||hash_file('sha256',$schema)!=='408004d48c12c4ffc94e11e7509d4e8c8342278f1d99320bb712ee7fac5ee7d0')exit(93);
$out=['stage'=>'A9.11','scope'=>'production-database-only','paid_calls'=>0,'status'=>'NOT_READY'];
try{
 $cfg=json_decode((string)file_get_contents($cfgp),true,512,JSON_THROW_ON_ERROR);
 if(($cfg['environment']??'')!=='production-a2t'||
 ($cfg['dsn']??'')!=='mysql:host=localhost;dbname=u342033576_a2t_live_26;charset=utf8mb4'||
 ($cfg['user']??'')!=='u342033576_a2t_runtime')throw new RuntimeException('unexpected_database');
 $pdo=new PDO($cfg['dsn'],$cfg['user'],$cfg['password'],[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>8]);
 unset($cfg);
 if((string)$pdo->query('SELECT DATABASE()')->fetchColumn()!=='u342033576_a2t_live_26')throw new RuntimeException('connected_wrong_db');
 $ddl=(string)file_get_contents($schema);
 if(preg_match('/\\b(DROP|ALTER|TRUNCATE|DELETE|INSERT|UPDATE|GRANT|REVOKE|CREATE\\s+DATABASE)\\b/i',$ddl))throw new RuntimeException('schema_unexpected_statements');
 $statements=array_values(array_filter(array_map('trim',explode(';',$ddl))));
 $executed=0;
 foreach($statements as $sql){
  $clean=preg_replace('/^\\s*--[^\\n]*\\n/m','',$sql);
  $clean=trim((string)$clean);
  if($clean==='')continue;
  if(!preg_match('/^CREATE\\s+TABLE\\s+(?:IF\\s+NOT\\s+EXISTS\\s+)?\\x60?a2t_(?:budget_pool|daily_quota|jobs)\\x60?\\b/i',$clean))
   throw new RuntimeException('schema_outside_scope');
  $pdo->exec($clean);$executed++;
 }
 $rows=$pdo->query("SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA='u342033576_a2t_live_26' AND TABLE_NAME LIKE 'a2t_%'")->fetchAll();
 $engines=[];foreach($rows as $x)$engines[$x['TABLE_NAME']]=$x['ENGINE'];
 $expected=['a2t_budget_pool','a2t_daily_quota','a2t_jobs'];
 foreach($expected as $t)if(($engines[$t]??'')!=='InnoDB')throw new RuntimeException('schema_engine_mismatch');
 if(count($engines)!==3)throw new RuntimeException('unexpected_tables');
 $counts=[];foreach($expected as $t)$counts[$t]=(int)$pdo->query('SELECT COUNT(*) FROM '.$t)->fetchColumn();
 if(array_sum($counts)!==0)throw new RuntimeException('unexpected_rows');
 $out['tables_innodb']=$expected;$out['empty_row_counts']=$counts;$out['statements_executed']=$executed;
 // Does not print SHOW GRANTS or passwords.
 $grants=$pdo->query('SHOW GRANTS FOR CURRENT_USER()')->fetchAll(PDO::FETCH_COLUMN);
 $out['global_data_grants_absent']=true;
 foreach($grants as $g)if(preg_match('/GRANT\\s+(?!USAGE\\s+ON)([^;]+)\\s+ON\\s+\\*\\.\\*/i',(string)$g))$out['global_data_grants_absent']=false;
 if(!$out['global_data_grants_absent'])throw new RuntimeException('grants_too_broad');
 @file_put_contents($mark,'done',LOCK_EX);@chmod($mark,0600);
 $out['status']='PRODUCTION_SCHEMA_READY';
}catch(Throwable $e){$out['status']='PRODUCTION_SCHEMA_FAILED';$out['error_type']=get_class($e);$out['error_stage']=in_array($e->getMessage(),['schema_unexpected_statements','schema_outside_scope','schema_engine_mismatch','unexpected_tables','unexpected_rows','grants_too_broad'],true)?$e->getMessage():'other';}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']!=='PRODUCTION_SCHEMA_READY')exit(97);
