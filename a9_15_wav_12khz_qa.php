<?php
declare(strict_types=1);
/* A9.15 WAV format gate: exactly the browser AudioContext file layout. No paid request. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$root='/home/u342033576/.motricialy-a2t-stage-a9';$private=$root.'/app-a9-7-full/private-server';
if(!is_file($private.'/media.php')||!is_file($private.'/vendor/autoload.php'))exit(92);
define('A2T_ENTRY',true);define('A2T_DURATION_SECONDS',900);define('A2T_UPLOAD_BYTES',24000000);
require_once $private.'/vendor/autoload.php';require_once $private.'/media.php';
$tmp=$root.'/a9-15-wav-'.bin2hex(random_bytes(8));
if(!mkdir($tmp,0700))exit(93);
$path=$tmp.'/recording.wav';
$out=['stage'=>'A9.15','status'=>'WAV_TEST_NOT_READY','paid_calls'=>0];
try{
 $rate=12000;$seconds=4;$size=$rate*$seconds*2;
 $header='RIFF'.pack('V',36+$size).'WAVEfmt '.pack('VvvVVvv',16,1,1,$rate,$rate*2,2,16).'data'.pack('V',$size);
 $fp=fopen($path,'xb');if(!$fp)throw new RuntimeException('write_failed');
 chmod($path,0600);
 fwrite($fp,$header);
 $data='';
 for($i=0;$i<$rate*$seconds;$i++){
  $n=(int)round(sin(2*M_PI*440*$i/$rate)*1500);
  $data.=pack('v',$n&65535);
 }
 fwrite($fp,$data);fclose($fp);
 $meta=a2t_prepare_direct($path,'wav');
 $out['duration_seconds']=$meta['duration']??null;
 $out['reservation_seconds']=$meta['seconds_for_reservation']??null;
 $out['provider_mime']=$meta['provider_mime']??null;
 $out['total_bytes']=filesize($path);
 $out['status']=abs((float)$meta['duration']-4)<.1&&
   (int)$meta['seconds_for_reservation']===4&&
   ($meta['provider_mime']??null)==='audio/wav'?
   'WAV_12KHZ_DIRECT_VALIDATED':'WAV_DIRECT_MISMATCH';
}catch(Throwable $e){$out['status']='WAV_DIRECT_EXCEPTION';$out['error_type']=get_class($e);}
finally{if(is_file($path))@unlink($path);@rmdir($tmp);$out['temp_deleted']=!file_exists($tmp);}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']!=='WAV_12KHZ_DIRECT_VALIDATED'||!$out['temp_deleted'])exit(97);
