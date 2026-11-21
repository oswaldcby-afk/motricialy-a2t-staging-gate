<?php
declare(strict_types=1);
/* A9.11 release gate. CLI-only, one-time, production A2T only.
   User authorized fastest safe launch; preserve hard budget cap.
   No keys, passwords, IP addresses or personal audio printed.
 */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
$rules=(string)@file_get_contents(__DIR__.'/.htaccess');
if(strpos($rules,'Require all denied')===false)exit(91);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$doc='/home/u342033576/domains/motricialy.com/public_html/tools/audio-to-text';
$enable=$root.'/a9-11-public-enabled.flag';
$paused=$root.'/a9-11-public-paused.flag';
if(is_file($enable)){echo json_encode(['stage'=>'A9.11','status'=>'ALREADY_ENABLED','flag_secure'=>(fileperms($enable)&0777)===0600]).PHP_EOL;exit;}
$out=['stage'=>'A9.11','status'=>'BLOCKED','paid_calls'=>0,'public_url'=>'https://motricialy.com/tools/audio-to-text/'];
try{
 if(!is_dir($root)||is_link($root)||(fileperms($root)&0777)!==0700)throw new RuntimeException('private_dir');
 if(is_file($paused))throw new RuntimeException('emergency_pause_active');
 foreach(['a9-11-openai-key.private'=>[30,500],
  'a9-11-production-hmac.private'=>[64,64],
  'a9-11-mysql-production.json'=>[100,3000]] as $file=>$limits){
  $p=$root.'/'.$file;
  if(!is_file($p)||is_link($p)||(fileperms($p)&0777)!==0600||
    filesize($p)<$limits[0]||filesize($p)>$limits[1])throw new RuntimeException('private_configuration_incomplete');
 }
 foreach(['a9-11-production-schema.done','a9-11-http-paid-qa-result.json'] as $file)
   if(!is_file($root.'/'.$file))throw new RuntimeException('release_qa_missing');
 $qa=json_decode((string)file_get_contents($root.'/a9-11-http-paid-qa-result.json'),true,512,JSON_THROW_ON_ERROR);
 if(($qa['status']??'')!=='PRODUCTION_HTTP_PAID_QA_PASS'||
  ($qa['public_paid_enabled']??true)!==false ||
  ($qa['production_ledger_persisted']??'')!=='reserved_to_settled'||
  ($qa['transcribe_http']??0)!==200)throw new RuntimeException('paid_qa_unverified');
 $cfg=json_decode((string)file_get_contents($root.'/a9-11-mysql-production.json'),true,512,JSON_THROW_ON_ERROR);
 if(($cfg['environment']??'')!=='production-a2t'||
  ($cfg['dsn']??'')!=='mysql:host=localhost;dbname=u342033576_a2t_live_26;charset=utf8mb4'||
  ($cfg['user']??'')!=='u342033576_a2t_runtime')throw new RuntimeException('database_identity_mismatch');
 $pdo=new PDO($cfg['dsn'],$cfg['user'],$cfg['password'],[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>7]);unset($cfg);
 $counts=[
  'settled'=>(int)$pdo->query("SELECT COUNT(*) FROM a2t_jobs WHERE status='settled'")->fetchColumn(),
  'uncertain'=>(int)$pdo->query("SELECT COUNT(*) FROM a2t_jobs WHERE status='uncertain'")->fetchColumn(),
  'reserved'=>(int)$pdo->query("SELECT COUNT(*) FROM a2t_jobs WHERE status='reserved'")->fetchColumn()
 ];
 if($counts['settled']!==1||$counts['uncertain']!==0||$counts['reserved']!==0)throw new RuntimeException('unexpected_production_jobs');
 $pools=$pdo->query('SELECT pool_name,spent_micro,reserved_micro FROM a2t_budget_pool')->fetchAll();
 if(count($pools)!==2)throw new RuntimeException('budget_pool_incomplete');
 $found=[];
 foreach($pools as $pool)$found[$pool['pool_name']]=[
  'spent'=>(int)$pool['spent_micro'],'reserved'=>(int)$pool['reserved_micro']];
 if(!isset($found['__total__'],$found['public'])||
 $found['__total__']['spent']!==750||$found['public']['spent']!==750||
 $found['__total__']['reserved']!==0||$found['public']['reserved']!==0)
 throw new RuntimeException('budget_not_reconciled');
 if((int)$pdo->query('SELECT COUNT(*) FROM a2t_daily_quota')->fetchColumn()!==0)
 throw new RuntimeException('qa_visitor_quota_not_cleared');
 $out['public_pool_remaining_usd']=round((10000000-$found['public']['spent'])/1000000,6);
 $out['monthly_public_pool_usd']=10;
 $out['monthly_global_ceiling_usd']=15;
 $out['two_files_per_day']=true;
 $out['fifteen_minutes_per_day']=true;
 if(!is_file($doc.'/index.html')||!is_file($doc.'/api/index.php')||
 !is_file($doc.'/assets/audio-to-text.js')||!is_file($doc.'/assets/audio-to-text.css'))
 throw new RuntimeException('production_page_incomplete');
 $html=(string)file_get_contents($doc.'/index.html');
 $ht=(string)file_get_contents($doc.'/.htaccess');
 $apiHt=(string)file_get_contents($doc.'/api/.htaccess');
 $apiSrc=(string)file_get_contents($doc.'/api/index.php');
 if(preg_match_all('/<h1\\b/i',$html)!==1||
 !str_contains($html,'SoftwareApplication')||!str_contains($html,'BreadcrumbList')||
 stripos($html,'noindex')!==false||stripos($ht,'noindex')!==false)
 throw new RuntimeException('seo_release_not_ready');
 if(!str_contains($apiHt,'php_value upload_max_filesize 24M')||
 !str_contains($apiHt,'php_value post_max_size 25M')||
 !str_contains($apiHt,'LimitRequestBody 27262976')||
 !str_contains($apiSrc,'a9-11-public-enabled.flag')||
 !str_contains($apiSrc,'a9-11-public-paused.flag'))
 throw new RuntimeException('php_api_guard_not_ready');
 $out['seo_indexable_on_disk']=true;
 $out['private_qa_budget_passed']=true;
 $out['production_database_separate_from_wp']=true;
 $out['public_only_upload_mvp']=true;
 $fp=@fopen($enable,'x');
 if(!$fp)throw new RuntimeException('enable_marker_creation_failed');
 @chmod($enable,0600);
 $bytes=fwrite($fp,'enabled');fflush($fp);fclose($fp);@chmod($enable,0600);
 if($bytes!==7||(fileperms($enable)&0777)!==0600){
  @unlink($enable);throw new RuntimeException('enable_marker_invalid');
 }
 $out['status']='PUBLIC_TRANSCRIPTION_ENABLED';
 $out['enable_flag_mode']='0600';
}catch(Throwable $e){$out['status']='PUBLIC_RELEASE_BLOCKED';$out['failure_category']=$e->getMessage();}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']!=='PUBLIC_TRANSCRIPTION_ENABLED')exit(97);
