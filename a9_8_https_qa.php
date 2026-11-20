<?php
declare(strict_types=1);
/* One-off real public HTTPS transport verification by protected server-side CLI.
   Key is read exclusively on Hostinger; no public user uploads or paid requests. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$keyPath=$root.'/a9-8-http-key.hex';
if(!is_file($keyPath)||is_link($keyPath)||(fileperms($keyPath)&0777)!==0600)exit(93);
$key=(string)file_get_contents($keyPath);
if(strlen($key)!==64)exit(94);
$base='https://motricialy.com/staging/audio-to-text-a9-private-gate-20261120/';
$results=[];$code=0;$status='HTTPS_QA_FAILED';$reason='unknown';
function a9_http(string $url,string $method='GET',?string $body=null,array $extra=[]):array {
 $ch=curl_init($url);
 if(!$ch)throw new RuntimeException('curl_init_failed');
 $opts=[
 CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,
 CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>22,
 CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_FOLLOWLOCATION=>false,
 CURLOPT_MAXREDIRS=>0,CURLOPT_SSL_VERIFYPEER=>true,
 CURLOPT_SSL_VERIFYHOST=>2,
 CURLOPT_HTTPHEADER=>$extra,
 CURLOPT_USERAGENT=>'Motricialy-A9.8-Private-QA/1.0',
 ];
 if($body!==null)$opts[CURLOPT_POSTFIELDS]=$body;
 curl_setopt_array($ch,$opts);
 $res=curl_exec($ch);
 $errno=curl_errno($ch);
 $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
 $headersize=(int)curl_getinfo($ch,CURLINFO_HEADER_SIZE);
 $effective=(string)curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
 curl_close($ch);
 if(!is_string($res)||$errno!==0)
    throw new RuntimeException('https_transport_errno_'.(string)$errno);
 if(!str_starts_with($effective,'https://motricialy.com/'))
    throw new RuntimeException('destination_changed');
 return ['code'=>$code,'headers'=>substr($res,0,$headersize),'body'=>substr($res,$headersize)];
}
function a9_expect(string $name,array $response,int $expected,array &$results):void{
 $results[$name]=['expected'=>$expected,'observed'=>$response['code'],'pass'=>$response['code']===$expected];
 if($response['code']!==$expected)throw new RuntimeException($name.'_status_mismatch');
}
try {
 if(!function_exists('curl_init')||!class_exists('ZipArchive'))throw new RuntimeException('runtime_missing');
 $gate=$base.'a9_8_gateway.php';
 $token=['X-A2T-Stage-Key: '.$key];
 $okOrigin=['Origin: https://motricialy.com'];
 a9_expect('non_gateway_still_denied',a9_http($base.'a9_7_core_audit.php'),403,$results);
 a9_expect('gateway_without_key',a9_http($gate.'?route=config'),404,$results);
 a9_expect('gateway_wrong_key',a9_http($gate.'?route=config','GET',null,['X-A2T-Stage-Key: '.str_repeat('0',64)]),404,$results);
 $x=a9_http($gate.'?route=config','GET',null,$token);
 a9_expect('authenticated_config',$x,200,$results);
 $cfg=json_decode($x['body'],true);
 if(!is_array($cfg)||($cfg['live_api_ready']??true)!==false||($cfg['ai_paused']??false)!==true)throw new RuntimeException('config_not_fail_closed');
 $results['config_paused_fields']=true;
 a9_expect('transcribe_disabled',a9_http($gate.'?route=transcribe','POST','',array_merge($token,$okOrigin,['Content-Type: application/x-www-form-urlencoded'])),503,$results);
 a9_expect('process_disabled',a9_http($gate.'?route=process','POST',json_encode(['text'=>'Small test text','mode'=>'clean']),array_merge($token,$okOrigin,['Content-Type: application/json'])),503,$results);
 a9_expect('cross_origin',a9_http($gate.'?route=process','POST',json_encode(['text'=>'Hello','mode'=>'clean']),array_merge($token,['Origin: https://example.invalid','Content-Type: application/json'])),403,$results);
 a9_expect('missing_origin',a9_http($gate.'?route=process','POST',json_encode(['text'=>'Hello','mode'=>'clean']),array_merge($token,['Content-Type: application/json'])),403,$results);
 $body=json_encode(['title'=>'中文 QA','text'=>'Hello & <中文> ✅'],JSON_UNESCAPED_UNICODE);
 $doc=a9_http($gate.'?route=export/docx','POST',$body,array_merge($token,$okOrigin,['Content-Type: application/json']));
 a9_expect('docx_over_https',$doc,200,$results);
 if(stripos($doc['headers'],'application/vnd.openxmlformats-officedocument.wordprocessingml.document')===false)throw new RuntimeException('docx_mime_missing');
 if(stripos($doc['headers'],'content-disposition:')===false)throw new RuntimeException('docx_disposition_missing');
 $temp=tempnam(sys_get_temp_dir(),'a9-8-docx-');
 if(!is_string($temp))throw new RuntimeException('docx_temp_failed');
 try{
  if(file_put_contents($temp,$doc['body'])!==strlen($doc['body']))throw new RuntimeException('docx_write_failed');
  $z=new ZipArchive();
  if($z->open($temp)!==true)throw new RuntimeException('docx_zip_invalid');
  $xml=$z->getFromName('word/document.xml');$z->close();
  if(!is_string($xml)||strpos($xml,'&amp; &lt;中文&gt;')===false||strpos($xml,'✅')===false)throw new RuntimeException('docx_xml_invalid');
 }finally{@unlink($temp);}
 $results['docx_binary_zip_utf8']=true;
 a9_expect('docx_empty_rejected',a9_http($gate.'?route=export/docx','POST',json_encode(['title'=>'QA','text'=>'']),array_merge($token,$okOrigin,['Content-Type: application/json'])),422,$results);
 a9_expect('unknown_route',a9_http($gate.'?route=unexpected','GET',null,$token),404,$results);
 $status='HTTPS_QA_PASS';$reason='all_checks_complete';
 @file_put_contents($root.'/a9-8-https-qa.done','done',LOCK_EX);
 @chmod($root.'/a9-8-https-qa.done',0600);
}catch(Throwable $e){$reason=$e->getMessage();}
unset($key);
echo json_encode([
 'stage'=>'A9.8','status'=>$status,'reason'=>$reason,
 'results'=>$results,'actual_https_used'=>true,
 'public_tool_deployed'=>false,'paid_api_enabled'=>false,'openai_calls'=>0
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if($status!=='HTTPS_QA_PASS')exit(97);
