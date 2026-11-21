<?php
/* One-shot readback QA of TTS v2.18. Every provider call goes via normal /api/generate budgeting. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$mark='/home/u342033576/domains/motricialy.com/tts-v218-real-qa-20261121.once';
$f=@fopen($mark,'x');if(!$f){echo "TTS_V218_QA_ALREADY_EXECUTED\n";exit;}
fwrite($f,gmdate('c'));fclose($f);
require_once '/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech/api/realtime.php';
$script='Get clear, accurate narration. Keep your script concise and read it once before generating. AI speech sounds natural, but unfamiliar product codes, prices and brand names can still be spoken incorrectly. Review these words carefully before sharing the result. If your voiceover accompanies a tutorial, our YouTube Auto Caption Cleaner can help prepare subtitles separately. For short community audio clips, try the Discord Soundboard Maker. Voice generation is processed on a server with OpenAI; your free allowance is limited and may change as capacity requires.';
$break=strpos($script,'For short community audio clips');
if($break===false){echo "TTS_V218_QA_BAD_INPUT\n";exit;}
$parts=[trim(substr($script,0,$break)),trim(substr($script,$break))];
$segments=[];foreach($parts as $i=>$text)$segments[]=['id'=>'qa-section-'.$i,'text'=>$text,'voice'=>'marin','style'=>'Narration','language'=>'en','speed'=>1,'sentencePause'=>0,'instruction'=>'','pronunciation'=>[]];
$payload=json_encode(['requestId'=>bin2hex(random_bytes(16)),'action'=>'full','segments'=>$segments],JSON_UNESCAPED_UNICODE);
$cookieFile=sys_get_temp_dir().'/tts-v218-qa-'.getmypid().'.cookies';
function request218(string $url,?string $body,string $cookieFile):array {
 $ch=curl_init($url);$options=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_TIMEOUT=>200,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_COOKIEJAR=>$cookieFile,CURLOPT_COOKIEFILE=>$cookieFile,CURLOPT_USERAGENT=>'Motricialy-TTS-QA/2.18'];
 if($body!==null){$options[CURLOPT_POST]=true;$options[CURLOPT_POSTFIELDS]=$body;$options[CURLOPT_HTTPHEADER]=['Origin: https://motricialy.com','Content-Type: application/json'];}
 curl_setopt_array($ch,$options);$response=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=curl_error($ch);curl_close($ch);
 return [$status,is_string($response)?$response:'',$error];
}
try {
 [$s0,$c0]=request218('https://motricialy.com/tools/text-to-speech/api/config',null,$cookieFile);
 if($s0!==200)throw new RuntimeException('CONFIG_HTTP_'.$s0);
 [$status,$response,$error]=request218('https://motricialy.com/tools/text-to-speech/api/generate',$payload,$cookieFile);
 $data=json_decode($response,true);
 if($status!==200||!is_array($data)||count($data['items']??[])!==2)throw new RuntimeException('GENERATION_HTTP_'.$status.'_'.substr((string)($data['error']??$error),0,100));
 $result=[];
 foreach($data['items'] as $i=>$item){
   $url=(string)($item['url']??'');if(!preg_match('~^/tools/text-to-speech/media/[0-9a-f-]+\.wav$~',$url))throw new RuntimeException('MEDIA_URL_'.$i);
   [$code,$wav]=request218('https://motricialy.com'.$url,null,$cookieFile);
   if($code!==200||strlen($wav)<44||substr($wav,0,4)!=='RIFF')throw new RuntimeException('MEDIA_HTTP_'.$i.'_'.$code);
   $pcm=substr($wav,44);
   $duration=round(strlen($pcm)/48000,2);
   $tail=round(tts_pcm_tail_silence_seconds($pcm),2);
   $result[]=['section'=>$i+1,'duration'=>$duration,'trailingSilence'=>$tail,'hasBadTail'=>tts_pcm_has_truncated_tail($pcm)];
 }
 $complete=!$result[0]['hasBadTail']&&!$result[1]['hasBadTail']&&$result[1]['duration']>=11&&$result[1]['trailingSilence']<3.5;
 echo 'TTS_V218_QA_'.($complete?'PASS':'SUSPECT').' HTTP='.$status.' RESULT='.json_encode($result).' COST_USD='.round((float)($data['actualCostUsd']??0),6)."\n";
}catch(Throwable $e){echo "TTS_V218_QA_FAIL ".substr($e->getMessage(),0,200)."\n";}
finally{@unlink($cookieFile);}
