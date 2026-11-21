<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$doc='/home/u342033576/domains/motricialy.com/public_html/tools/audio-to-text';
$out=['stage'=>'A9.11','audit'=>'post_public_launch_ledger_and_private_controls','paid_calls'=>0];
try{
 $flag=$root.'/a9-11-public-enabled.flag';
 $out['public_enabled']=is_file($flag)&&(fileperms($flag)&0777)===0600&&trim((string)file_get_contents($flag))==='enabled';
 $out['emergency_paused']=is_file($root.'/a9-11-public-paused.flag');
 $out['private_api_key_secure']=is_file($root.'/a9-11-openai-key.private')&&
  !is_link($root.'/a9-11-openai-key.private')&&
  (fileperms($root.'/a9-11-openai-key.private')&0777)===0600;
 $out['private_runtime_hmac_secure']=is_file($root.'/a9-11-production-hmac.private')&&
  (fileperms($root.'/a9-11-production-hmac.private')&0777)===0600;
 $cp=$root.'/a9-11-mysql-production.json';
 if(!is_file($cp)||(fileperms($cp)&0777)!==0600)throw new RuntimeException('config_not_secure');
 $cfg=json_decode((string)file_get_contents($cp),true,512,JSON_THROW_ON_ERROR);
 if(($cfg['environment']??'')!=='production-a2t'||
 ($cfg['dsn']??'')!=='mysql:host=localhost;dbname=u342033576_a2t_live_26;charset=utf8mb4'||
 ($cfg['user']??'')!=='u342033576_a2t_runtime')throw new RuntimeException('config_scope');
 $db=new PDO($cfg['dsn'],$cfg['user'],$cfg['password'],[
 PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_EMULATE_PREPARES=>false,
 PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
 PDO::ATTR_TIMEOUT=>8]);unset($cfg);
 $rows=$db->query("SELECT pool_name,spent_micro,reserved_micro,cycle_start FROM a2t_budget_pool")->fetchAll();
 $totals=[];foreach($rows as $row){
  $totals[$row['pool_name']]=['spent_micro'=>(int)$row['spent_micro'],
    'reserved_micro'=>(int)$row['reserved_micro'],'cycle_start'=>$row['cycle_start']];
 }
 $out['budget_pools']=$totals;
 $out['public_pool_limit_usd']=10;
 $out['full_cycle_limit_usd']=15;
 $out['public_pool_remaining_usd']=round((10000000-($totals['public']['spent_micro']??10000000)-($totals['public']['reserved_micro']??0))/1000000,6);
 $out['jobs_by_status']=[];
 foreach($db->query("SELECT status,COUNT(*) AS cnt FROM a2t_jobs GROUP BY status")->fetchAll() as $row)
  $out['jobs_by_status'][$row['status']]=(int)$row['cnt'];
 $out['visitor_quota_subjects']=(int)$db->query('SELECT COUNT(*) FROM a2t_daily_quota')->fetchColumn();
 $out['database_scope_correct']=(string)$db->query('SELECT DATABASE()')->fetchColumn()==='u342033576_a2t_live_26';
 $html=(string)file_get_contents($doc.'/index.html');
 $headers=(string)file_get_contents($doc.'/.htaccess');
 $out['seo_indexable_files']=!str_contains(strtolower($html),'noindex')&&!str_contains(strtolower($headers),'noindex');
 $out['public_api_file_exists']=is_file($doc.'/api/index.php');
 $out['staging_deny_all']=str_contains((string)file_get_contents(__DIR__.'/.htaccess'),'Require all denied');
 $ok=$out['public_enabled']&&!$out['emergency_paused']&&$out['private_api_key_secure']&&
  $out['private_runtime_hmac_secure']&&$out['database_scope_correct']&&$out['seo_indexable_files']&&
  $out['public_api_file_exists']&&$out['staging_deny_all']&&
  isset($totals['__total__'],$totals['public'])&&
  $totals['public']['spent_micro']+$totals['public']['reserved_micro']<=10000000&&
  $totals['__total__']['spent_micro']+$totals['__total__']['reserved_micro']<=15000000&&
  ($out['jobs_by_status']['uncertain']??0)===0;
 $out['status']=$ok?'PUBLIC_LAUNCH_LEDGER_AUDIT_PASS':'PUBLIC_LAUNCH_LEDGER_AUDIT_FAILED';
}catch(Throwable $e){$out['status']='AUDIT_EXCEPTION';$out['error_category']=$e->getMessage();}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']!=='PUBLIC_LAUNCH_LEDGER_AUDIT_PASS')exit(97);
