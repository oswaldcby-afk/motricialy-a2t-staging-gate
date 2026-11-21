<?php
declare(strict_types=1);
/* A9.15 guarded activation of separately-budgeted AI text tools.
 * CLI-only after real HTTP + browser mocked QA. Original transcription remains on.
 */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$path=$root.'/a9-15-text-enabled.flag';
if(is_file($path)){echo json_encode(['stage'=>'A9.15','status'=>'TEXT_FEATURE_ALREADY_ENABLED','secure'=>(fileperms($path)&0777)===0600]).PHP_EOL;exit;}
$out=['stage'=>'A9.15','status'=>'TEXT_FEATURE_ENABLE_BLOCKED','paid_calls'=>0];
try{
 if(!is_dir($root)||is_link($root)||(fileperms($root)&0777)!==0700)throw new RuntimeException('private_root');
 foreach(['a9-11-openai-key.private','a9-11-production-hmac.private','a9-11-mysql-production.json'] as $name){
   $p=$root.'/'.$name;
   if(!is_file($p)||is_link($p)||(fileperms($p)&0777)!==0600)throw new RuntimeException('private_config');
 }
 if(is_file($root.'/a9-11-public-paused.flag'))throw new RuntimeException('emergency_paused');
 $enable=$root.'/a9-11-public-enabled.flag';
 if(!is_file($enable)||trim((string)file_get_contents($enable))!=='enabled')throw new RuntimeException('stt_not_enabled');
 $report=$root.'/a9-15-http-clean-qa-result.json';
 if(!is_file($report)||is_link($report)||(fileperms($report)&0777)!==0600)throw new RuntimeException('private_http_qa_missing');
 $qa=json_decode((string)file_get_contents($report),true,512,JSON_THROW_ON_ERROR);
 if(($qa['status']??'')!=='PRODUCTION_PRIVATE_TEXT_QA_PASS'||
    ($qa['public_process_503']??false)!==true||
    ($qa['anonymous_capability_gated']??false)!==true||
    ($qa['new_clean_settlement']??false)!==true||
    ($qa['http_status']??0)!==200)
      throw new RuntimeException('private_http_qa_failed');
 $web='/home/u342033576/domains/motricialy.com/public_html/tools/audio-to-text';
 $api=(string)file_get_contents($web.'/api/index.php');
 $js=(string)file_get_contents($web.'/assets/audio-to-text.js');
 $ht=(string)file_get_contents($web.'/api/.htaccess');
 if(!str_contains($api,'a9-15-text-enabled.flag')||
    !str_contains($api,'a9-11-release-qa-gate.private')||
    !str_contains($ht,'process|capabilities')||
    !str_contains($js,"api+'process'")||
    !str_contains($js,"api+'capabilities'")||
    !str_contains($js,"mode==='summary'"))
   throw new RuntimeException('production_code_unverified');
 $cfg=json_decode((string)file_get_contents($root.'/a9-11-mysql-production.json'),true,512,JSON_THROW_ON_ERROR);
 if(($cfg['environment']??'')!=='production-a2t'||($cfg['dsn']??'')!=='mysql:host=localhost;dbname=u342033576_a2t_live_26;charset=utf8mb4')
  throw new RuntimeException('database_scope');
 $pdo=new PDO($cfg['dsn'],$cfg['user'],$cfg['password'],[
   PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,
   PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>7]);unset($cfg);
 $uncertain=(int)$pdo->query("SELECT COUNT(*) FROM a2t_jobs WHERE status='uncertain'")->fetchColumn();
 if($uncertain!==0)throw new RuntimeException('unreconciled_transactions');
 $rows=$pdo->query("SELECT pool_name,spent_micro,reserved_micro FROM a2t_budget_pool WHERE cycle_start='2026-11-01'")->fetchAll();
 $pool=[];foreach($rows as $r)$pool[$r['pool_name']]=(int)$r['spent_micro']+(int)$r['reserved_micro'];
 if(!isset($pool['public'],$pool['__total__'])||$pool['public']>=9500000||$pool['__total__']>=14500000)
  throw new RuntimeException('budget_guard');
 $fp=@fopen($path,'x');if(!$fp)throw new RuntimeException('create_fail');
 @chmod($path,0600);$written=fwrite($fp,'enabled');fflush($fp);fclose($fp);@chmod($path,0600);
 if($written!==7||(fileperms($path)&0777)!==0600){@unlink($path);throw new RuntimeException('flag_mode');}
 $out['status']='PUBLIC_TEXT_PROCESSING_ENABLED';
 $out['monthly_public_pool_usd']=10;
 $out['total_tool_ceiling_usd']=15;
 $out['daily_text_operations_per_subject']=2;
 $out['text_reservation_ceiling_usd']=0.02;
 $out['independent_from_audio_transcription']=true;
}catch(Throwable $e){$out['block_reason']=$e->getMessage();}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']!=='PUBLIC_TEXT_PROCESSING_ENABLED')exit(97);
