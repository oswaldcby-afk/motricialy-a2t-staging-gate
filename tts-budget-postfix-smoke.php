<?php
/* One-shot low-cost TTS after-reconciliation check via normal public API. No API key here. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$marker='/home/u342033576/domains/motricialy.com/tts-v212-smoke-20261121.once';
$f=@fopen($marker,'x');if(!$f){echo "TTS_V212_SMOKE_ALREADY_EXECUTED\n";exit(0);}
fwrite($f,gmdate('c'));fclose($f);
$uuid=vsprintf('%s-%s-%s-%s-%s',str_split(bin2hex(random_bytes(16)),[8,4,4,4,12]));
$payload=['requestId'=>$uuid,'action'=>'full','segments'=>[['id'=>'smoke-20261121','text'=>'Hello. This is a short text to speech test.','voice'=>'ash','style'=>'Narration','language'=>'en','speed'=>1,'sentencePause'=>0,'instruction'=>'','pronunciation'=>[]]]];
$ch=curl_init('https://motricialy.com/tools/text-to-speech/api/generate');
curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Origin: https://motricialy.com','Content-Type: application/json'],CURLOPT_TIMEOUT=>110,CURLOPT_CONNECTTIMEOUT=>12,CURLOPT_FOLLOWLOCATION=>false]);
$body=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
curl_close($ch);
$out=is_string($body)?json_decode($body,true):null;
$ok=$code===200&&is_array($out)&&count($out['items']??[])===1;
echo 'TTS_V212_SMOKE_'.($ok?'PASS':'FAIL').' HTTP='.$code.' COST_USD='.round((float)($out['actualCostUsd']??0),7);
if(!$ok)echo ' ERROR='.substr(preg_replace('/[^A-Za-z0-9 :.,_-]/','',(string)($out['error']??'NO_JSON')),0,160);
echo "\n";
