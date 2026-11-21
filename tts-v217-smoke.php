<?php
/* TTS v2.17 one-shot smoke: actual 373-character Ash speech through normal budget and API. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$flag='/home/u342033576/domains/motricialy.com/tts-v217-smoke-20261121.once';
$fd=@fopen($flag,'x');if(!$fd){echo "TTS_V217_SMOKE_ALREADY_EXECUTED\n";exit(0);}
fwrite($fd,gmdate('c'));fclose($fd);
$text='Get clear, accurate narration. Keep your script concise and read it once before generating. AI speech sounds natural, but unfamiliar product codes, prices and brand names can still be spoken incorrectly. Review these words carefully before sharing the result. If your voiceover accompanies a tutorial, our YouTube Auto Caption Cleaner can help prepare subtitles separately.';
$payload=['requestId'=>bin2hex(random_bytes(16)),'action'=>'full','segments'=>[['id'=>'smart-sentence-test','text'=>$text,'voice'=>'ash','style'=>'Narration','language'=>'en','speed'=>1,'sentencePause'=>0,'instruction'=>'','pronunciation'=>[]]]];
$ch=curl_init('https://motricialy.com/tools/text-to-speech/api/generate');
curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE),CURLOPT_HTTPHEADER=>['Origin: https://motricialy.com','Content-Type: application/json'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>12,CURLOPT_TIMEOUT=>135,CURLOPT_FOLLOWLOCATION=>false]);
$body=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$curlError=curl_error($ch);curl_close($ch);
$data=is_string($body)?json_decode($body,true):null;
$ok=$status===200&&is_array($data)&&count($data['items']??[])===1&&isset($data['items'][0]['duration'])&&($data['items'][0]['duration']>0);
echo "TTS_V217_SMOKE_".($ok?'PASS':'FAIL')." HTTP=".$status." INPUT_CHARS=".mb_strlen($text,'UTF-8')." DURATION_S=".round((float)($data['items'][0]['duration']??0),2)." COST_USD=".round((float)($data['actualCostUsd']??0),6);
if(!$ok)echo " ERROR=".substr(preg_replace('/[^a-zA-Z0-9 :,.!_-]/','',is_array($data)?(string)($data['error']??'unknown'):$curlError),0,140);
echo "\n";
