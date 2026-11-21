<?php
declare(strict_types=1);
/* A9.11 manual emergency stop: only when explicitly executed by a Hostinger admin CLI job.
   Does not expose secrets, alter WordPress, or delete quota records.
 */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$base='/home/u342033576/.motricialy-a2t-stage-a9';
$flag=$base.'/a9-11-public-paused.flag';
if(!is_dir($base)||is_link($base)||(fileperms($base)&0777)!==0700)exit(92);
if(!is_file($flag)){
 $fp=@fopen($flag,'x');if(!$fp)exit(93);
 @chmod($flag,0600);$ok=fwrite($fp,'paused')===6;fclose($fp);@chmod($flag,0600);
 if(!$ok){@unlink($flag);exit(94);}
}
echo json_encode(['stage'=>'A9.11','status'=>'PUBLIC_TRANSCRIPTION_EMERGENCY_PAUSED',
 'flag_mode'=>'0600','api_requests_paused'=>true,'other_motricialy_tools_unchanged'=>true],JSON_UNESCAPED_SLASHES).PHP_EOL;
