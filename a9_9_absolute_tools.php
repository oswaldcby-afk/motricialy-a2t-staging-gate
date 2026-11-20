<?php
declare(strict_types=1);
/* A9.9 read-only command discovery, no changes beyond own execution. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$paths=[
 'composer2','composer','ffmpeg','ffprobe',
 '/usr/local/bin/composer2','/usr/local/bin/composer',
 '/usr/bin/composer2','/usr/bin/composer',
 '/opt/alt/php81/usr/bin/php',
 '/usr/local/bin/ffmpeg','/usr/bin/ffmpeg',
 '/usr/local/bin/ffprobe','/usr/bin/ffprobe'
];
$out=['stage'=>'A9.9','probe'=>'absolute_tools','paid_api_calls'=>0];
foreach($paths as $p){
 $onDisk=str_starts_with($p,'/');
 $exists=$onDisk?is_file($p):null;
 $executable=$onDisk?is_executable($p):null;
 $cmd=[$p, str_contains($p,'composer')?'--version':($p==='/opt/alt/php81/usr/bin/php'?'--version':'-version')];
 $stdout=tempnam(sys_get_temp_dir(),'a9sout');
 $stderr=tempnam(sys_get_temp_dir(),'a9serr');
 if(!is_string($stdout)||!is_string($stderr))continue;
 $exit=null;$started=false;$outText='';
 try{
  $proc=@proc_open($cmd,[0=>['pipe','r'],1=>['file',$stdout,'w'],2=>['file',$stderr,'w']],$pipes,null,null,['bypass_shell'=>true]);
  if(is_resource($proc)){
   $started=true;fclose($pipes[0]);$exit=proc_close($proc);
   $outText=(string)file_get_contents($stdout).' '.(string)file_get_contents($stderr);
  }
 }finally{if(is_file($stdout))@unlink($stdout);if(is_file($stderr))@unlink($stderr);}
 $out['tools'][$p]=[
  'exists'=>$exists,'is_executable'=>$executable,
  'spawned'=>$started,'exit_code'=>$exit,
  'version_banner_recognized'=>(
    str_contains(strtolower($p),'composer')?stripos($outText,'Composer')!==false:
    (str_contains(strtolower($p),'ff')?stripos($outText,'ffmpeg version')!==false||stripos($outText,'ffprobe version')!==false:
    stripos($outText,'PHP')!==false)
  )
 ];
}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
