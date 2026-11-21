<?php
declare(strict_types=1);
/* A9.11 one-shot authenticated HTTPS transcribe on real production tool URL.
 * Public paid traffic remains OFF. One provider call only.
 * Stores sanitized result privately; no audio, transcript, tokens, DB passwords in logs. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$resultPath=$root.'/a9-11-http-paid-qa-result.json';
$once=$root.'/a9-11-http-paid-qa.once';
if(is_file($once)){if(is_file($resultPath))echo (string)file_get_contents($resultPath).PHP_EOL;else echo 'A9_11_HTTP_QA_ALREADY_ATTEMPTED'.PHP_EOL;exit;}
if(file_exists($root.'/a9-11-public-enabled.flag'))exit(92);
$keyp=$root.'/a9-11-release-qa-gate.private';
$cfgp=$root.'/a9-11-mysql-production.json';
$demo='/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech/voice-demos/alloy.mp3';
if(!is_file($keyp)||is_link($keyp)||(fileperms($keyp)&0777)!==0600||
 !is_file($cfgp)||is_link($cfgp)||(fileperms($cfgp)&0777)!==0600||
 !is_file($demo)||filesize($demo)>450000)exit(93);
$key=(string)file_get_contents($keyp);
if(strlen($key)!==64||!ctype_xdigit($key))exit(94);
$qaHeader='X-A2T-Stage-Key: '.$key;unset($key);
$base='https://motricialy.com/tools/audio-to-text/api/';
$out=['stage'=>'A9.11','check'=>'actual_public_path_qa_header_paid_http','status'=>'PRECHECK','provider_calls_attempted'=>0,'public_paid_enabled'=>false];
function a11curl(string $url,string $method='GET',array $headers=[],?array $fields=null,string $cookieJar=''):array {
 $ch=curl_init($url);if(!$ch)throw new RuntimeException('curl_init');
 $settings=[
  CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_SSL_VERIFYPEER=>true,
  CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>150,
  CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_CUSTOMREQUEST=>$method,
  CURLOPT_HTTPHEADER=>array_merge(['Accept: application/json','User-Agent: Motricialy-A9.11-Private-ReleaseQA'], $headers)
 ];
 if($fields!==null)$settings[CURLOPT_POSTFIELDS]=$fields;
 if($cookieJar!==''){$settings[CURLOPT_COOKIEJAR]=$cookieJar;$settings[CURLOPT_COOKIEFILE]=$cookieJar;}
 curl_setopt_array($ch,$settings);
 $resp=curl_exec($ch);$errno=curl_errno($ch);
 $http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
 $len=(int)curl_getinfo($ch,CURLINFO_HEADER_SIZE);
 curl_close($ch);
 if(!is_string($resp)||$errno!==0)throw new RuntimeException('transport_'.$errno);
 return ['http'=>$http,'headers'=>substr($resp,0,$len),'json'=>json_decode(substr($resp,$len),true)];
}
$cookieJar=$root.'/a9-11-test-cookie-'.bin2hex(random_bytes(6)).'.txt';
try{
 $cfg=json_decode((string)file_get_contents($cfgp),true,512,JSON_THROW_ON_ERROR);
 if(($cfg['environment']??'')!=='production-a2t'||($cfg['dsn']??'')!=='mysql:host=localhost;dbname=u342033576_a2t_live_26;charset=utf8mb4')throw new RuntimeException('db_wrong');
 $pdo=new PDO($cfg['dsn'],$cfg['user'],$cfg['password'],[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>8]);
 unset($cfg);
 foreach(['a2t_budget_pool','a2t_daily_quota','a2t_jobs'] as $table)
 if((int)$pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn()!==0)throw new RuntimeException('unexpected_db_rows');
 $public=a11curl($base.'config');
 $out['anonymous_config_off']=$public['http']===200&&($public['json']['live_api_ready']??true)===false;
 if(!$out['anonymous_config_off'])throw new RuntimeException('public_gate_open');
 $open=a11curl($base.'config','GET',[$qaHeader],null,$cookieJar);
 $out['qa_config_ready']=$open['http']===200&&($open['json']['live_api_ready']??false)===true&&($open['json']['ai_paused']??true)===false;
 $out['cookie_secure_http_only']=stripos($open['headers'],'Secure')!==false&&stripos($open['headers'],'HttpOnly')!==false;
 if(!$out['qa_config_ready']||!$out['cookie_secure_http_only'])throw new RuntimeException('private_qa_config_not_ready');
 // Block repeated charge BEFORE sending audio.
 $fp=@fopen($once,'x');if(!$fp)throw new RuntimeException('qa_already_started');
 @chmod($once,0600);fwrite($fp,'started');fclose($fp);
 $out['provider_calls_attempted']=1;
 $request=a11curl($base.'transcribe','POST',[$qaHeader,'Origin: https://motricialy.com'],[
   'file'=>new CURLFile($demo,'audio/mpeg','alloy.mp3'),
   'language'=>'auto','keywords'=>'','context'=>''
 ],$cookieJar);
 $out['transcribe_http']=$request['http'];
 if($request['http']!==200||!is_array($request['json']))throw new RuntimeException('transcribe_http_failure');
 $v=$request['json'];$txt=(string)($v['text']??'');
 $out['text_nonempty']=mb_strlen(trim($txt),'UTF-8')>=12;
 $out['text_length']=mb_strlen($txt,'UTF-8');
 $out['duration_seconds']=$v['duration_seconds']??null;
 $out['meter_type']=$v['usage']['type']??null;
 $out['meter_seconds']=$v['usage']['seconds']??null;
 $out['estimated_actual_usd']=$v['estimated_actual_usd']??null;
 if(!$out['text_nonempty']||$out['meter_type']!=='duration')throw new RuntimeException('response_meter_unexpected');
 $jobs=$pdo->query("SELECT status,reserved_micro,actual_micro FROM a2t_jobs")->fetchAll();
 $pools=$pdo->query("SELECT pool_name,spent_micro,reserved_micro FROM a2t_budget_pool")->fetchAll();
 $quotas=$pdo->query("SELECT day_utc,COUNT(*) cnt FROM a2t_daily_quota GROUP BY day_utc")->fetchAll();
 if(count($jobs)!==1||$jobs[0]['status']!=='settled'||(int)$jobs[0]['actual_micro']<1||
 count($pools)!==2||(int)$pools[0]['reserved_micro']!==0||(int)$pools[1]['reserved_micro']!==0||
 (int)$pools[0]['spent_micro']!==(int)$jobs[0]['actual_micro']||
 (int)$pools[1]['spent_micro']!==(int)$jobs[0]['actual_micro'])
 throw new RuntimeException('persisted_ledger_mismatch');
 $out['production_ledger_persisted']='reserved_to_settled';
 $out['production_spent_micro_usd']=(int)$jobs[0]['actual_micro'];
 $out['quota_subjects_count']=(int)$pdo->query('SELECT COUNT(*) FROM a2t_daily_quota')->fetchColumn();
 if($out['quota_subjects_count']!==2)throw new RuntimeException('quota_subjects_unexpected');
 // Remove only QA identity quotas, preserving actual settled provider spend in ledger.
 $pdo->exec('DELETE FROM a2t_daily_quota');
 $out['qa_daily_quota_cleaned']=(int)$pdo->query('SELECT COUNT(*) FROM a2t_daily_quota')->fetchColumn()===0;
 $out['status']='PRODUCTION_HTTP_PAID_QA_PASS';
}catch(Throwable $e){
 $out['status']='PRODUCTION_HTTP_PAID_QA_FAILED';
 $out['error_type']=get_class($e);
 $allowed=['db_wrong','unexpected_db_rows','public_gate_open','private_qa_config_not_ready','qa_already_started','transcribe_http_failure','response_meter_unexpected','persisted_ledger_mismatch','quota_subjects_unexpected'];
 $out['error_category']=in_array($e->getMessage(),$allowed,true)?$e->getMessage():'other';
}finally{
 unset($qaHeader);
 if(is_file($cookieJar))@unlink($cookieJar);
 $out['cookie_temp_deleted']=!is_file($cookieJar);
 if(is_file($once)){
  $json=json_encode($out,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  if(is_string($json)){@file_put_contents($resultPath,$json,LOCK_EX);@chmod($resultPath,0600);}
 }
}
echo json_encode($out,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
if($out['status']!=='PRODUCTION_HTTP_PAID_QA_PASS')exit(97);
