<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$private=$root.'/app-a9-7-full/private-server';
$out=['stage'=>'A9.9','test'=>'getid3_and_media_function_introspection','paid_api_called'=>false];
$temp=null;
try{
 if(hash_file('sha256',$private.'/media.php')!=='03420827736b5097be09ece684516c28a6610496cc08225c1ea783aeb65a0265')throw new RuntimeException('media_source_hash_changed');
 if(!is_file($private.'/vendor/autoload.php'))throw new RuntimeException('autoload_missing');
 require_once $private.'/vendor/autoload.php';
 $out['getID3_available']=class_exists('getID3');
 if(!$out['getID3_available'])throw new RuntimeException('getID3_unavailable');
 define('A2T_ENTRY',true);
 require_once $private.'/media.php';
 $names=['a2t_preflight_file','a2t_supported_media','a2t_webm_has_bounded_segment','a2t_prepare_direct'];
 foreach($names as $name){
  $r=new ReflectionFunction($name);
  $params=[];
  foreach($r->getParameters() as $param)$params[]=['name'=>$param->getName(),'type'=>(string)$param->getType(),'optional'=>$param->isOptional()];
  $out['functions'][$name]=['params'=>$params,'return'=>(string)$r->getReturnType()];
 }
 $temp=$root.'/a9-9-getid3-'.bin2hex(random_bytes(8));
 if(!mkdir($temp,0700))throw new RuntimeException('temp_create_failed');
 chmod($temp,0700);
 $seconds=2;$rate=16000;$data='';
 for($i=0;$i<$seconds*$rate;$i++)$data.=pack('v',((int)(sin($i*2*M_PI*440/$rate)*5000))&65535);
 $wav='RIFF'.pack('V',36+strlen($data)).'WAVEfmt '.pack('VvvVVvv',16,1,1,$rate,$rate*2,2,16).'data'.pack('V',strlen($data)).$data;
 $file=$temp.'/sample.wav';file_put_contents($file,$wav,LOCK_EX);chmod($file,0600);
 $analyzer=new getID3();
 $info=$analyzer->analyze($file);
 $duration=(float)($info['playtime_seconds']??0);
 $out['getid3_wav']=['duration_seconds'=>round($duration,3),'fileformat'=>$info['fileformat']??null,
 'audio_channels'=>$info['audio']['channels']??null,'sample_rate'=>$info['audio']['sample_rate']??null,
 'duration_valid'=>$duration>=1.9&&$duration<=2.1];
 // Deliberately mislabeled extension: should not be accepted merely due to filename.
 $fake=$temp.'/not-really-mp3.mp3';
 file_put_contents($fake,'NOT AN AUDIO FILE',LOCK_EX);chmod($fake,0600);
 $bad=$analyzer->analyze($fake);
 $out['getid3_rejects_fake_mp3']=!isset($bad['playtime_seconds'])||($bad['playtime_seconds']??0)<=0;
 $lock=json_decode((string)file_get_contents($private.'/composer.lock'),true);
 $packages=[];
 foreach($lock['packages']??[] as $p)$packages[$p['name']??'unknown']=$p['version']??null;
 $out['locked_packages']=$packages;
 $out['status']=($out['getid3_wav']['duration_valid']&&$out['getid3_rejects_fake_mp3'])?'GETID3_WAV_PASS':'GETID3_VALIDATION_FAILED';
}catch(Throwable $e){$out['status']='TEST_FAILED';$out['reason']=$e->getMessage();}
finally{
 if(is_string($temp)&&is_dir($temp)){
  foreach(['sample.wav','not-really-mp3.mp3'] as $name)if(is_file($temp.'/'.$name))@unlink($temp.'/'.$name);
  @rmdir($temp);
  $out['temp_files_deleted']=!file_exists($temp);
 }
}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']!=='GETID3_WAV_PASS')exit(97);
