<?php
declare(strict_types=1);
/* Stage A9.9: server-only runtime and synthetic media diagnostic.
   This script never loads config/secrets, invokes the API, or calls OpenAI.
   It tests only the isolated private A9 installation and deletes its own temp data. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$private=$root.'/app-a9-7-full/private-server';
$expected=['media.php'=>'03420827736b5097be09ece684516c28a6610496cc08225c1ea783aeb65a0265',
  'bootstrap.php'=>'1e33ef157a57c898e17bf89fd45bd147712df405b10049fe5c3de0e2efaa8698',
  'composer.json'=>'0fd343c09bd85f349438b3720ea36e91b87d2ec4270d5cfd09572dbf7a776b45'];
$out=['stage'=>'A9.9','scope'=>'CLI_ONLY_SYNTHETIC_MEDIA','paid_api_called'=>false,'temp_cleanup'=>null];
$tmp=null;
function a99run(array $cmd,int $timeoutSeconds=12):array{
 $proc=@proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,null,['bypass_shell'=>true]);
 if(!is_resource($proc))return ['spawned'=>false,'exit'=>null,'stdout'=>'','stderr'=>''];
 fclose($pipes[0]);
 stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);
 $buf='';$err='';$started=microtime(true);$state=null;
 do{
  $read=[$pipes[1],$pipes[2]];$write=null;$except=null;
  @stream_select($read,$write,$except,0,200000);
  foreach($read as $stream){
    $chunk=(string)fread($stream,8192);
    if($stream===$pipes[1])$buf.=substr($chunk,0,max(0,32768-strlen($buf)));
    else $err.=substr($chunk,0,max(0,8192-strlen($err)));
  }
  $state=proc_get_status($proc);
  if(!$state['running'])break;
  if(microtime(true)-$started>$timeoutSeconds){
    @proc_terminate($proc);break;
  }
 }while(true);
 $buf.=substr((string)stream_get_contents($pipes[1]),0,max(0,32768-strlen($buf)));
 $err.=substr((string)stream_get_contents($pipes[2]),0,max(0,8192-strlen($err)));
 fclose($pipes[1]);fclose($pipes[2]);
 $status=proc_get_status($proc);
 $exit=($status['running']??false)?-999:($status['exitcode']??-999);
 @proc_close($proc);
 return ['spawned'=>true,'exit'=>$exit,'stdout'=>$buf,'stderr'=>$err];
}
try{
 if(!is_dir($root)||is_link($root)||(fileperms($root)&0777)!==0700)throw new RuntimeException('private_root_not_secure');
 $out['php_version_major_minor']=PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
 $out['php_sapi']=PHP_SAPI;
 foreach(['fileinfo','zip','mbstring','curl','pdo_mysql'] as $ext)$out['extensions'][$ext]=extension_loaded($ext);
 foreach(['upload_max_filesize','post_max_size','max_file_uploads','memory_limit','max_execution_time','max_input_time','upload_tmp_dir','open_basedir'] as $name){
   $value=(string)ini_get($name);
   // Do not expose absolute private path or other account path through diagnostics.
   if(in_array($name,['upload_tmp_dir','open_basedir'],true))$value=$value===''?'default_or_unset':'configured';
   $out['ini_cli'][$name]=$value;
 }
 $out['proc_open_available']=function_exists('proc_open');
 $out['exec_available']=function_exists('exec');
 $out['shell_exec_available']=function_exists('shell_exec');
 foreach($expected as $name=>$sha)$out['source_integrity'][$name]=is_file($private.'/'.$name)&&hash_file('sha256',$private.'/'.$name)===$sha;
 if(in_array(false,$out['source_integrity'],true))throw new RuntimeException('source_integrity_failed');
 $composer=json_decode((string)file_get_contents($private.'/composer.json'),true,512,JSON_THROW_ON_ERROR);
 $out['composer_packages']=array_keys($composer['require']??[]);
 $out['vendor_autoload_exists']=is_file($private.'/vendor/autoload.php');
 $source=(string)file_get_contents($private.'/media.php');
 preg_match_all('/function\\s+([A-Za-z_][A-Za-z0-9_]*)\\s*\\(/',$source,$matches);
 $out['candidate_media_function_names']=$matches[1];
 $out['candidate_mentions_getid3']=stripos($source,'getid3')!==false;
 $out['candidate_mentions_ffprobe']=stripos($source,'ffprobe')!==false;
 $out['candidate_mentions_ffmpeg']=stripos($source,'ffmpeg')!==false;
 foreach(['ffprobe','ffmpeg','composer'] as $name){
   $probe=a99run([$name,$name==='composer'?'--version':'-version'],8);
   $ok=$probe['spawned']&&$probe['exit']===0;
   $out['cli_binaries'][$name]=['available'=>$ok,'exit'=>$probe['exit'],
     'recognized_output'=>$name==='composer'?
       stripos($probe['stdout'].$probe['stderr'],'Composer')!==false:
       stripos($probe['stdout'].$probe['stderr'],$name.' version')!==false];
 }
 $tmp=$root.'/a9-9-media-'.bin2hex(random_bytes(8));
 if(!mkdir($tmp,0700))throw new RuntimeException('cannot_create_temp');
 chmod($tmp,0700);
 $rate=16000;$seconds=2;$samples=$rate*$seconds;
 $data='';for($i=0;$i<$samples;$i++){
   $amplitude=(int)(min(1,$i/400)*min(1,($samples-$i)/400)*2500*sin(2*M_PI*440*$i/$rate));
   $data.=pack('v',$amplitude&65535);
 }
 $wav='RIFF'.pack('V',36+strlen($data)).'WAVEfmt '.pack('VvvVVvv',16,1,1,$rate,$rate*2,2,16).'data'.pack('V',strlen($data)).$data;
 $src=$tmp.'/fixture.wav';
 if(file_put_contents($src,$wav,LOCK_EX)!==strlen($wav))throw new RuntimeException('wav_write');
 chmod($src,0600);
 $finfo=new finfo(FILEINFO_MIME_TYPE);
 $out['synthetic_wav']=['bytes'=>strlen($wav),'mime'=>(string)$finfo->file($src),'riff_header'=>substr($wav,0,4)==='RIFF'];
 if($out['cli_binaries']['ffprobe']['available']){
   $q=a99run(['ffprobe','-v','error','-show_entries','format=duration','-of','default=noprint_wrappers=1:nokey=1',$src]);
   $duration=(float)trim($q['stdout']);
   $out['ffprobe_real_fixture']=['success'=>$q['exit']===0&&$duration>=1.9&&$duration<=2.1,'duration_seconds'=>round($duration,3)];
 }
 if($out['cli_binaries']['ffmpeg']['available']){
   $dst=$tmp.'/normalized.mp3';
   $q=a99run(['ffmpeg','-nostdin','-hide_banner','-loglevel','error','-y','-i',$src,'-ac','1','-ar','16000','-t','3',$dst],12);
   $out['ffmpeg_normalization']=['success'=>$q['exit']===0&&is_file($dst)&&filesize($dst)>100,
     'output_exists'=>is_file($dst),'exit'=>$q['exit']];
 }
 $out['status']='RUNTIME_INSPECTED';
}catch(Throwable $e){
 $out['status']='RUNTIME_DIAGNOSTIC_FAILED';
 $out['reason']=$e->getMessage();
}finally{
 if(is_string($tmp)&&is_dir($tmp)){
   foreach(['fixture.wav','normalized.mp3'] as $file){
      if(is_file($tmp.'/'.$file))@unlink($tmp.'/'.$file);
   }
   @rmdir($tmp);
   $out['temp_cleanup']=!file_exists($tmp);
 }
}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']==='RUNTIME_DIAGNOSTIC_FAILED')exit(97);
