<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(9);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(10);
$marker=__DIR__.'/.a9-ffprobe-v2-once';
if(file_exists($marker)){echo "FFPROBE_ALREADY_DONE\n";exit;}
$out=['task'=>'a9_audio_executable_paths','proc_open_enabled'=>function_exists('proc_open'),'shell_exec_enabled'=>function_exists('shell_exec'),'paths'=>[]];
foreach(['ffmpeg','ffprobe'] as $binary){
 foreach(['/usr/bin/','/usr/local/bin/','/opt/bin/','/usr/local/ffmpeg/bin/'] as $prefix){
  $path=$prefix.$binary;
  $entry=['exists'=>is_file($path),'executable'=>is_executable($path)];
  if($entry['executable']&&function_exists('proc_open')){
   try{
    $pipes=[];
    $p=@proc_open([$path,'-version'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,null,['bypass_shell'=>true]);
    if(is_resource($p)){
      fclose($pipes[0]);fgets($pipes[1]);fclose($pipes[1]);fclose($pipes[2]);
      $entry['run_exit_success']=(proc_close($p)===0);
    }
   }catch(Throwable $e){$entry['run_exit_success']=false;}
  }
  $out['paths'][$path]=$entry;
 }
}
@file_put_contents($marker,'done',LOCK_EX);
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
