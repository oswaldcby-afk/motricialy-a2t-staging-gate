<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$base='/home/u342033576/domains/motricialy.com/public_html/tools/audio-to-text';
$url='https://motricialy.com/tools/audio-to-text/api/';
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$out=['stage'=>'A9.15','status'=>'CHECKING','paid_calls'=>0];
$jar=$root.'/a9-15-quota-cookie-'.bin2hex(random_bytes(6));
function qaGET(string $uri,string $jar):array{
 $h=curl_init($uri);
 curl_setopt_array($h,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>15,
 CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_FOLLOWLOCATION=>false,
 CURLOPT_COOKIEFILE=>$jar,CURLOPT_COOKIEJAR=>$jar,
 CURLOPT_HTTPHEADER=>['Accept: application/json','User-Agent: Motricialy-A9.15-Quota-ReadOnly']]);
 $response=curl_exec($h);$errno=curl_errno($h);
 $code=(int)curl_getinfo($h,CURLINFO_HTTP_CODE);curl_close($h);
 if(!is_string($response)||$errno!==0)throw new RuntimeException('network_failure');
 return ['status'=>$code,'data'=>json_decode($response,true)];
}
try{
 $p=@proc_open([PHP_BINARY,'-l',$base.'/api/index.php'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
 if(!is_resource($p))throw new RuntimeException('php_lint_unavailable');
 fclose($pipes[0]);$stdout=(string)stream_get_contents($pipes[1]);$stderr=(string)stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($p);
 $out['php_syntax_pass']=$exit===0&&str_contains($stdout,'No syntax errors');
 $config=qaGET($url.'config',$jar);
 $quota=qaGET($url.'quota',$jar);
 $caps=qaGET($url.'capabilities',$jar);
 $out['config_http']=$config['status'];
 $out['quota_http']=$quota['status'];
 $out['capabilities_http']=$caps['status'];
 $out['stt_ready']=$config['status']===200&&($config['data']['live_api_ready']??false)===true;
 $out['text_enabled']=$caps['status']===200&&($caps['data']['text_processing_ready']??false)===true;
 $out['quota_structure']=$quota['status']===200&&is_array($quota['data'])&&
  is_numeric($quota['data']['files_remaining']??null)&&
  is_numeric($quota['data']['minutes_remaining']??null)&&
  is_numeric($quota['data']['text_operations_remaining']??null);
 if($out['quota_structure']){
  $q=$quota['data'];
  $out['remaining']=['files'=>(int)$q['files_remaining'],'minutes'=>(float)$q['minutes_remaining'],
    'text_actions'=>(int)$q['text_operations_remaining']];
 }
 $out['status']=($out['php_syntax_pass']&&$out['stt_ready']&&$out['text_enabled']&&$out['quota_structure'])?
  'PUBLIC_QUOTA_API_PASS':'PUBLIC_QUOTA_API_NOT_READY';
}catch(Throwable $e){$out['status']='PUBLIC_QUOTA_API_ERROR';$out['error_type']=get_class($e);}
finally{if(is_file($jar))@unlink($jar);}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']!=='PUBLIC_QUOTA_API_PASS')exit(97);
