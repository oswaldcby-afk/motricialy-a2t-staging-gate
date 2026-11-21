<?php
declare(strict_types=1);
/* A9.10: PHP 8.1 real FFmpeg-free direct-media path; no paid provider, private synthetic WAV only. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$p=$root.'/app-a9-7-full/private-server';
if(hash_file('sha256',$p.'/media.php')!=='03420827736b5097be09ece684516c28a6610496cc08225c1ea783aeb65a0265')exit(93);
define('A2T_ENTRY',true);define('A2T_DURATION_SECONDS',900);define('A2T_UPLOAD_BYTES',24000000);
require_once $p.'/vendor/autoload.php';
require_once $p.'/media.php';
$dir=$root.'/a9-10-direct-'.bin2hex(random_bytes(8));
if(!mkdir($dir,0700))exit(94);
@chmod($dir,0700);
$res=['stage'=>'A9.10','test'=>'actual_prepare_direct','openai_calls'=>0,'valid'=>[],'rejected'=>[]];
function a910wav(string $path,int $seconds,int $sampleRate=8000):void{
 $length=$seconds*$sampleRate;
 $head='RIFF'.pack('V',36+$length).'WAVEfmt '.pack('VvvVVvv',16,1,1,$sampleRate,$sampleRate,1,8).'data'.pack('V',$length);
 $h=fopen($path,'wb');
 if(!$h||fwrite($h,$head)!==strlen($head)||!ftruncate($h,44+$length))throw new RuntimeException('fixture_write_failed');
 fclose($h);chmod($path,0600);
}
try {
 $valid=$dir.'/valid.wav';a910wav($valid,2);
 $v=a2t_prepare_direct($valid,'wav');
 $res['valid']=['duration'=>round((float)($v['duration']??0),3),
  'reservation_seconds'=>$v['seconds_for_reservation']??null,
  'extension'=>$v['extension']??null,
  'has_audio'=>$v['has_audio']??false,
  'provider_mime'=>$v['provider_mime']??null,
  'within_size_limit'=>(($v['media_bytes']??0)<=A2T_UPLOAD_BYTES)];
 if($res['valid']['duration']<1.9||$res['valid']['duration']>2.1||$res['valid']['reservation_seconds']!==2||!$res['valid']['has_audio'])throw new RuntimeException('valid_wav_incorrect');
 foreach([
  ['name'=>'mismatched_extension','ext'=>'mp3','file'=>$valid],
  ['name'=>'unsupported_extension','ext'=>'exe','file'=>$valid]
 ] as $t){
  $rejected=false;
  try{a2t_prepare_direct($t['file'],$t['ext']);}catch(InvalidArgumentException $e){$rejected=true;}
  $res['rejected'][$t['name']]=$rejected;
  if(!$rejected)throw new RuntimeException($t['name'].'_not_rejected');
 }
 $corrupt=$dir.'/corrupt.wav';file_put_contents($corrupt,str_repeat('not-a-valid-wav',16),LOCK_EX);chmod($corrupt,0600);
 try{a2t_prepare_direct($corrupt,'wav');$res['rejected']['corrupt_wav']=false;}
 catch(InvalidArgumentException $e){$res['rejected']['corrupt_wav']=true;}
 if(!$res['rejected']['corrupt_wav'])throw new RuntimeException('corrupt_wav_accepted');
 $long=$dir.'/too-long.wav';a910wav($long,901);
 try{a2t_prepare_direct($long,'wav');$res['rejected']['901_seconds']=false;}
 catch(InvalidArgumentException $e){$res['rejected']['901_seconds']=true;}
 if(!$res['rejected']['901_seconds'])throw new RuntimeException('overlong_wav_accepted');
 $res['supported_extensions']=array_keys(a2t_supported_media());
 $res['status']='DIRECT_PATH_PASS';
}catch(Throwable $e){
 $res['status']='DIRECT_PATH_FAILED';
 $res['error_type']=get_class($e);
 $res['error_category']=in_array($e->getMessage(),['valid_wav_incorrect','corrupt_wav_accepted','overlong_wav_accepted'],true)?$e->getMessage():'other';
}finally{
 foreach(['valid.wav','corrupt.wav','too-long.wav'] as $f)if(is_file($dir.'/'.$f))@unlink($dir.'/'.$f);
 @rmdir($dir);$res['test_files_deleted']=!file_exists($dir);
}
echo json_encode($res,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($res['status']!=='DIRECT_PATH_PASS'||!$res['test_files_deleted'])exit(97);
