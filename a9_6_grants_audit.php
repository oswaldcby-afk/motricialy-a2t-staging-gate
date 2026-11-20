<?php
declare(strict_types=1);
/* A9.6 SQL grants classification only; never print grant text/credentials. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(9);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(10);
$dir='/home/u342033576/.motricialy-a2t-stage-a9';
if((fileperms($dir)&0777)!==0700)exit(11);
$cfg=$dir.'/mysql-a9.json';
if(!is_file($cfg)||(fileperms($cfg)&0777)!==0600)exit(12);
if(file_exists($dir.'/a9-6-grants.done')){echo "ALREADY_AUDITED\n";exit;}
try {
 $c=json_decode((string)file_get_contents($cfg),true,512,JSON_THROW_ON_ERROR);
 if(($c['environment']??'')!=='isolated-a9-test'||($c['paid_enabled']??true)!==false)throw new RuntimeException('config');
 $pdo=new PDO($c['dsn'],$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);
 unset($c);
 $g=$pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_NUM);
 $classes=[];
 foreach($g as $row){
   $s=strtolower((string)($row[0]??''));
   // MySQL/MariaDB SHOW GRANTS may escape underscore inside quoted database names.
   $s=str_replace([chr(96),chr(39),chr(92)],'', $s);
   if(preg_match('/\bgrant\s+usage\s+on\s+\*\.\*/', $s))$classes[]='global_usage_only';
   elseif(str_contains($s,'on u342033576_a2t_stage_a9.*'))$classes[]='isolated_database_only';
   elseif(preg_match('/\bon\s+\*\.\*/', $s))$classes[]='global_non_usage';
   else $classes[]='unexpected_scope';
 }
 $strict=!in_array('unexpected_scope',$classes,true)&&!in_array('global_non_usage',$classes,true)&&in_array('isolated_database_only',$classes,true);
 @file_put_contents($dir.'/a9-6-grants.done','1',LOCK_EX);
 echo json_encode(['stage'=>'A9.6','status'=>'AUDITED','grant_count'=>count($g),
   'scope_classes'=>$classes,'least_privilege_scope_verified'=>$strict,
   'raw_grants_not_logged'=>true,'openai_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
}catch(Throwable $e){echo json_encode(['stage'=>'A9.6','status'=>'AUDIT_FAILED','exception_class'=>get_class($e)]).PHP_EOL;exit(13);}
