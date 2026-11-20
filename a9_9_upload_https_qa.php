<?php
declare(strict_types=1);
/* Stage A9.9 HTTPS multipart client. Server-private auth, synthetic WAV only. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$keyfile=$root.'/a9-9-http-key.hex';
if(!is_file($keyfile)||is_link($keyfile)||(fileperms($keyfile)&0777)!==0600)exit(93);
$key=(string)file_get_contents($keyfile);
if(strlen($key)!==64)exit(94);
$endpoint='https://motricialy.com/staging/audio-to-text-a9-private-gate-20261120/a9_9_upload_gateway.php';
$tmp=$root.'/a9-9-client-'.bin2hex(random_bytes(8));
if(!mkdir($tmp,0700))exit(95);
@chmod($tmp,0700);
$results=[];$status='HTTPS_UPLOAD_FAILED';$reason='not_complete';
function a99call(string $url,string $method='GET',array $headers=[],?array $multipart=null):array{
 $ch=curl_init($url);
 if(!$ch)throw new RuntimeException('curl_init');
 $opts=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_CONNECTTIMEOUT=>8,
  CURLOPT_TIMEOUT=>20,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,
  CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_SSL_VERIFYPEER=>true,
  CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_HTTPHEADER=>$headers,
  CURLOPT_USERAGENT=>'Motricialy-A9.9-PrivateMultipartQA'];
 if($multipart!==null)$opts[CURLOPT_POSTFIELDS]=$multipart;
 curl_setopt_array($ch,$opts);
 $response=curl_exec($ch);$errno=curl_errno($ch);
 $http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
 $headerSize=(int)curl_getinfo($ch,CURLINFO_HEADER_SIZE);
 curl_close($ch);
 if(!is_string($response)||$errno!==0)throw new RuntimeException('HTTPS transport error '.(string)$errno);
 $body=substr($response,$headerSize);
 return ['http'=>$http,'body'=>json_decode($body,true),'response_bytes'=>strlen($body)];
}
function a99check(string $label,array $r,array $codes,array &$results):void{
 $ok=in_array($r['http'],$codes,true);
 $results[$label]=['expected'=>$codes,'actual'=>$r['http'],'pass'=>$ok];
 if(!$ok)throw new RuntimeException($label.'_status_'.(string)$r['http']);
}
try{
 if(!function_exists('curl_file_create'))throw new RuntimeException('curl_file_missing');
 $rate=16000;$seconds=2;$data='';
 for($i=0;$i<$rate*$seconds;$i++)$data.=pack('v',((int)(sin(2*M_PI*440*$i/$rate)*3000))&65535);
 $wav='RIFF'.pack('V',36+strlen($data)).'WAVEfmt '.pack('VvvVVvv',16,1,1,$rate,$rate*2,2,16).'data'.pack('V',strlen($data)).$data;
 $file=$tmp.'/fixture.wav';$fake=$tmp.'/fake.wav';
 file_put_contents($file,$wav,LOCK_EX);@chmod($file,0600);
 file_put_contents($fake,'THIS IS NOT AUDIO',LOCK_EX);@chmod($fake,0600);
 $goodHash=hash_file('sha256',$file);$badHash=hash_file('sha256',$fake);
 $auth=['X-A2T-Stage-Key: '.$key];$origin=['Origin: https://motricialy.com'];
 a99check('deny_all_other_scripts',a99call(str_replace('a9_9_upload_gateway.php','a9_9_keygen.php',$endpoint)) ,[403],$results);
 a99check('gateway_requires_key',a99call($endpoint.'?action=capabilities'),[404],$results);
 a99check('gateway_rejects_bad_key',a99call($endpoint.'?action=capabilities','GET',['X-A2T-Stage-Key: '.str_repeat('0',64)]),[404],$results);
 $caps=a99call($endpoint.'?action=capabilities','GET',$auth);
 a99check('web_php_capabilities',$caps,[200],$results);
 $results['web_php_ini']=['php_sapi'=>$caps['body']['php_sapi']??null,
  'upload_max_filesize'=>$caps['body']['upload_max_filesize']??null,
  'post_max_size'=>$caps['body']['post_max_size']??null,
  'max_file_uploads'=>$caps['body']['max_file_uploads']??null,
  'getid3_vendor_exists'=>$caps['body']['getid3_autoload_exists']??false];
 $results['scoped_limits_applied']=($caps['body']['upload_max_filesize']??null)==='24M'&&
   ($caps['body']['post_max_size']??null)==='25M'&&($caps['body']['max_file_uploads']??null)==='1';
 $fixtureHeader=['X-A2T-Fixture-Sha256: '.$goodHash];
 $validFile=['audio'=>new CURLFile($file,'audio/wav','fixture.wav')];
 $valid=a99call($endpoint.'?action=upload','POST',array_merge($auth,$origin,$fixtureHeader),$validFile);
 a99check('real_multipart_actual_candidate_preflight',$valid,[200],$results);
 if(($valid['body']['status']??'')!=='REAL_MULTIPART_PREFLIGHT_PASS'||($valid['body']['temp_files_deleted']??false)!==true)
 throw new RuntimeException('candidate_preflight_or_cleanup_failed');
 $results['candidate_preflight_result']=$valid['body'];
 $badOrigin=a99call($endpoint.'?action=upload','POST',array_merge($auth,['Origin: https://evil.invalid'],$fixtureHeader),$validFile);
 a99check('origin_rejects_cross_site',$badOrigin,[403],$results);
 $missingOrigin=a99call($endpoint.'?action=upload','POST',array_merge($auth,$fixtureHeader),$validFile);
 a99check('origin_required',$missingOrigin,[403],$results);
 $fakeRequest=a99call($endpoint.'?action=upload','POST',array_merge($auth,$origin,['X-A2T-Fixture-Sha256: '.$badHash]),['audio'=>new CURLFile($fake,'audio/wav','fake.wav')]);
 a99check('corrupt_wav_rejected',$fakeRequest,[415,422],$results);
 $wrongExt=a99call($endpoint.'?action=upload','POST',array_merge($auth,$origin,$fixtureHeader),['audio'=>new CURLFile($file,'audio/mpeg','fake.mp3')]);
 a99check('extension_spoof_rejected',$wrongExt,[415],$results);
 $noFile=a99call($endpoint.'?action=upload','POST',array_merge($auth,$origin),['blank'=>'none']);
 a99check('missing_audio_rejected',$noFile,[422],$results);
 a99check('unlisted_route_rejected',a99call($endpoint.'?action=process','GET',$auth),[404],$results);
 $status='HTTPS_UPLOAD_PASS';$reason='all_test_cases_passed';
 @file_put_contents($root.'/a9-9-https-upload-qa.done','done',LOCK_EX);
 @chmod($root.'/a9-9-https-upload-qa.done',0600);
}catch(Throwable $e){$reason=$e->getMessage();}
finally{
 foreach(['fixture.wav','fake.wav'] as $n)if(is_file($tmp.'/'.$n))@unlink($tmp.'/'.$n);
 @rmdir($tmp);$results['client_temp_deleted']=!is_dir($tmp);
}
unset($key);
echo json_encode(['stage'=>'A9.9','status'=>$status,'reason'=>$reason,'results'=>$results,
 'openai_calls'=>0,'paid_api_enabled'=>false,'public_tool_deployed'=>false],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
if($status!=='HTTPS_UPLOAD_PASS')exit(97);
