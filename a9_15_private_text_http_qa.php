<?php
declare(strict_types=1);
/* A9.15 one-time authenticated production HTTP text processing QA.
   Public text flag stays OFF. No user data, no transcripts in logs, one API call.
   Never retries uncertain provider calls; cost remains in production ledger.
 */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$tokenFile=$root.'/a9-11-release-qa-gate.private';
$cfg=$root.'/a9-11-mysql-production.json';
$once=$root.'/a9-15-http-clean-qa.once';
$qaResult=$root.'/a9-15-http-clean-qa-result.json';
if(is_file($once)){if(is_file($qaResult))echo (string)file_get_contents($qaResult).PHP_EOL;else echo "ALREADY_ATTEMPTED\n";exit;}
if(!is_file($tokenFile)||is_link($tokenFile)||(fileperms($tokenFile)&0777)!==0600||
 !is_file($cfg)||is_link($cfg)||(fileperms($cfg)&0777)!==0600)exit(92);
$out=['stage'=>'A9.15','status'=>'PRECHECK','public_text_enabled'=>false,'provider_calls_attempted'=>0,
 'production_db_only'=>true,'no_text_content_logged'=>true];
$url='https://motricialy.com/tools/audio-to-text/api/';
function getHttp(string $url,string $method='GET',array $headers=[],?string $payload=null):array{
 $ch=curl_init($url);if(!$ch)throw new RuntimeException('curl_unavailable');
 curl_setopt_array($ch,[
   CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,
   CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,
   CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>85,
   CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_FOLLOWLOCATION=>false,
   CURLOPT_MAXREDIRS=>0,
   CURLOPT_HTTPHEADER=>array_merge(['Accept: application/json','User-Agent: Motricialy-A9.15-Private-Text-QA'], $headers)
 ]);
 if($payload!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,$payload);
 $str=curl_exec($ch);$err=curl_errno($ch);
 $http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
 $headersBytes=(int)curl_getinfo($ch,CURLINFO_HEADER_SIZE);curl_close($ch);
 if(!is_string($str)||$err!==0)throw new RuntimeException('HTTP_transport_failure');
 $raw=substr($str,$headersBytes);
 return ['http'=>$http,'data'=>json_decode($raw,true),'bytes'=>strlen($raw)];
}
try{
 $privateFlag=$root.'/a9-15-text-enabled.flag';
 if(file_exists($privateFlag))throw new RuntimeException('text_flag_already_on');
 $cap=getHttp($url.'capabilities');
 $out['anonymous_capability_gated']=$cap['http']===200&&($cap['data']['text_processing_ready']??true)===false;
 if(!$out['anonymous_capability_gated'])throw new RuntimeException('text_capability_misconfigured');
 $guard=getHttp($url.'process','POST',['Origin: https://motricialy.com','Content-Type: application/json'],
  json_encode(['text'=>'No paid call should execute','mode'=>'clean']));
 $out['public_process_503']=$guard['http']===503;
 if(!$out['public_process_503'])throw new RuntimeException('public_process_not_gated');
 $cfgJson=json_decode((string)file_get_contents($cfg),true,512,JSON_THROW_ON_ERROR);
 if(($cfgJson['environment']??'')!=='production-a2t'||($cfgJson['dsn']??'')!=='mysql:host=localhost;dbname=u342033576_a2t_live_26;charset=utf8mb4')
  throw new RuntimeException('database_scope');
 $pdo=new PDO($cfgJson['dsn'],$cfgJson['user'],$cfgJson['password'],[
 PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_EMULATE_PREPARES=>false,
 PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
 PDO::ATTR_TIMEOUT=>8]);unset($cfgJson);
 $old=(int)$pdo->query("SELECT COUNT(*) FROM a2t_jobs WHERE kind='clean' AND status='settled'")->fetchColumn();
 $token=trim((string)file_get_contents($tokenFile));
 if(!preg_match('/^[a-f0-9]{64}$/D',$token))throw new RuntimeException('private_qa_token_invalid');
 $fp=@fopen($once,'x');if(!$fp)throw new RuntimeException('once_creation_failed');@chmod($once,0600);fwrite($fp,'started');fclose($fp);
 $out['provider_calls_attempted']=1;
 $fixture='On November 2, Dana said the blue valve read 17 PSI. No action items were assigned.';
 $reply=getHttp($url.'process','POST',[
   'Origin: https://motricialy.com','Content-Type: application/json','X-A2T-Stage-Key: '.$token],
   json_encode(['mode'=>'clean','text'=>$fixture],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
 unset($token,$fixture);
 $out['http_status']=$reply['http'];
 $data=$reply['data'];
 if($reply['http']!==200||!is_array($data))throw new RuntimeException('process_http_failed');
 $text=$data['text']??null;
 if(!is_string($text)||!str_contains($text,'17')||stripos($text,'Dana')===false)throw new RuntimeException('text_fidelity');
 $usage=$data['usage']??null;
 if(!is_array($usage)||!is_numeric($usage['input_tokens']??null)||!is_numeric($usage['output_tokens']??null))
  throw new RuntimeException('usage_missing');
 $out['provider_tokens']=['input'=>(int)$usage['input_tokens'],'output'=>(int)$usage['output_tokens']];
 $out['estimated_charge_usd']=$data['estimated_actual_usd']??null;
 $new=(int)$pdo->query("SELECT COUNT(*) FROM a2t_jobs WHERE kind='clean' AND status='settled'")->fetchColumn();
 $out['new_clean_settlement']=$new===$old+1;
 if(!$out['new_clean_settlement'])throw new RuntimeException('settlement_not_persisted');
 $out['clean_output_verified']=true;
 $out['status']='PRODUCTION_PRIVATE_TEXT_QA_PASS';
}catch(Throwable $e){
 $out['status']='PRODUCTION_PRIVATE_TEXT_QA_FAILED';
 $out['failure_type']=get_class($e);
 $list=['text_flag_already_on','text_capability_misconfigured','public_process_not_gated','database_scope','private_qa_token_invalid','once_creation_failed','process_http_failed','text_fidelity','usage_missing','settlement_not_persisted'];
 $out['error_category']=in_array($e->getMessage(),$list,true)?$e->getMessage():'other';
}finally{
 if(is_file($once)){
  $json=json_encode($out,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  if(is_string($json)){@file_put_contents($qaResult,$json,LOCK_EX);@chmod($qaResult,0600);}
 }
}
echo json_encode($out,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
if($out['status']!=='PRODUCTION_PRIVATE_TEXT_QA_PASS')exit(97);
