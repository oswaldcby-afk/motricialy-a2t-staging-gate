<?php
/* One-time controlled TTS v2.9 QA: real public API, normal billing path. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$marker='/home/u342033576/domains/motricialy.com/tts-v29-smoke-20261121.once';
$lock=@fopen($marker,'x');
if(!$lock){echo "TTS_V29_SMOKE_ALREADY_EXECUTED\n";exit;}
fwrite($lock,gmdate('c'));fclose($lock);
$text='Keep your script concise and read it once before generating. AI speech sounds natural, but unfamiliar product codes, prices and brand names can still be spoken incorrectly.';
$payload=[
  'requestId'=>sprintf('%s-%s-%s-%s-%s',bin2hex(random_bytes(4)),bin2hex(random_bytes(2)),bin2hex(random_bytes(2)),bin2hex(random_bytes(2)),bin2hex(random_bytes(6))),
  'action'=>'full',
  'segments'=>[['id'=>'ash-smoke','text'=>$text,'voice'=>'ash','style'=>'Narration','language'=>'en','speed'=>1,'sentencePause'=>0,'instruction'=>'','pronunciation'=>[]]]
];
$json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
$url='https://motricialy.com/tools/text-to-speech/api/generate';
$headers=['Origin: https://motricialy.com','Content-Type: application/json'];
$status=0;$body='';
if(function_exists('curl_init')){
 $ch=curl_init($url);
 curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$json,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>140,CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_USERAGENT=>'Motricialy-TTS-Internal-QA/2.9']);
 $response=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$body=is_string($response)?$response:'';curl_close($ch);
}else{
 $context=stream_context_create(['http'=>['method'=>'POST','header'=>implode("\r\n",$headers),'content'=>$json,'timeout'=>140,'ignore_errors'=>true]]);
 $response=@file_get_contents($url,false,$context);$body=is_string($response)?$response:'';
 if(isset($http_response_header)&&preg_match('~HTTP/\S+\s+(\d{3})~',$http_response_header[0]??'',$m))$status=(int)$m[1];
}
$result=json_decode($body,true);
if(!is_array($result)){echo "TTS_V29_SMOKE_HTTP_{$status} NO_JSON\n";exit;}
$valid=($status===200&&count($result['items']??[])===1&&($result['mode']??'')==='openai-verified-api');
echo 'TTS_V29_SMOKE_'.($valid?'PASS':'FAIL').' HTTP='.$status.' ITEMS='.count($result['items']??[]).' COST_USD='.round((float)($result['actualCostUsd']??0),6);
if(!$valid)echo ' ERROR='.substr(preg_replace('/[^a-zA-Z0-9 .,;:-]/','',(string)($result['error']??'unknown')),0,180);
echo "\n";
