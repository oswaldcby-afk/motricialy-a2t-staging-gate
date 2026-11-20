<?php
declare(strict_types=1);
/* Actual A9.5 PHP API entrypoint with CLI-synthesized request variables.
   Does NOT establish an HTTP listener. Tests fail-closed controls only. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$base='/home/u342033576/.motricialy-a2t-stage-a9';
$target=$base.'/app-a9-7-full';
$private=$target.'/private-server';
$docroot=$target.'/test-public';
$entry=$docroot.'/tools/audio-to-text/api/index.php';
$marker=$base.'/a9-7-cli-router-qa.done';
if(is_file($marker)){echo "ALREADY_TESTED\n";exit;}
if(!is_file($entry)||!is_file($private.'/bootstrap.php')||!is_file($private.'/docx.php'))exit(93);
$results=[];
$run = static function(string $method,string $route,?string $origin=null)use($base,$entry,$private,$docroot):array {
    $script=$base.'/.a9-7-case-'.bin2hex(random_bytes(6)).'.php';
    $server=['DOCUMENT_ROOT'=>$docroot,'REQUEST_URI'=>'/tools/audio-to-text/api/'.$route,
      'REQUEST_METHOD'=>$method,'REMOTE_ADDR'=>'127.0.0.1','CONTENT_LENGTH'=>0];
    if($origin!==null)$server['HTTP_ORIGIN']=$origin;
    $source="<?php\n".
      "putenv('A2T_ENABLE_LIVE=0'); putenv('A2T_PAUSE_AI=1'); putenv('OPENAI_API_KEY');\n".
      'putenv('.var_export('A2T_PRIVATE_PATH='.$private,true).');' ."\n".
      '$_SERVER=array_merge($_SERVER,'.var_export($server,true).');' ."\n".
      'register_shutdown_function(static function(){echo "\\n__A2T_CLI_STATUS__=".http_response_code()."\\n";});' ."\n".
      'require '.var_export($entry,true).';' ."\n";
    if(file_put_contents($script,$source,LOCK_EX)!==strlen($source))throw new RuntimeException('case write');
    @chmod($script,0600);
    try{
      $cmd=[PHP_BINARY,$script];
      $proc=@proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,null,['bypass_shell'=>true]);
      if(!is_resource($proc))throw new RuntimeException('case start');
      fclose($pipes[0]);
      $stdout=(string)stream_get_contents($pipes[1]);
      $stderr=(string)stream_get_contents($pipes[2]);
      fclose($pipes[1]);fclose($pipes[2]);
      $code=proc_close($proc);
      if($code!==0||$stderr!=='')throw new RuntimeException('case execution failed');
      $needle="\n__A2T_CLI_STATUS__=";
      $pos=strrpos($stdout,$needle);
      if($pos===false)throw new RuntimeException('case status missing');
      $status=(int)trim(substr($stdout,$pos+strlen($needle)));
      $content=trim(substr($stdout,0,$pos));
      return ['status'=>$status,'json'=>json_decode($content,true),'body'=>$content];
    }finally{@unlink($script);}
};
try{
 $cfg=$run('GET','config');
 if($cfg['status']!==200||!is_array($cfg['json'])||($cfg['json']['live_api_ready']??true)!==false||($cfg['json']['ai_paused']??false)!==true)
   throw new RuntimeException('config invalid');
 $results['config_200_paused']=true;
 $r=$run('POST','process','https://motricialy.com');
 if($r['status']!==503||!is_array($r['json']))throw new RuntimeException('process not disabled');
 $results['ai_process_503']=true;
 $r=$run('POST','transcribe','https://motricialy.com');
 if($r['status']!==503||!is_array($r['json']))throw new RuntimeException('transcribe not disabled');
 $results['audio_transcribe_503']=true;
 $r=$run('POST','process','https://evil.example');
 if($r['status']!==403)throw new RuntimeException('cross-site not denied');
 $results['cross_site_403']=true;
 $r=$run('POST','process');
 if($r['status']!==403)throw new RuntimeException('missing origin not denied');
 $results['missing_origin_403']=true;
 $r=$run('GET','no-such-route');
 if($r['status']!==404)throw new RuntimeException('invalid path not denied');
 $results['unknown_route_404']=true;
 define('A2T_ENTRY',true);
 require_once $private.'/docx.php';
 $bytes=a2t_docx('QA 中英','Hello & <中文> ✅');
 $tmp=tempnam(sys_get_temp_dir(),'a9docx');
 if(!is_string($tmp))throw new RuntimeException('temp file');
 try{
  file_put_contents($tmp,$bytes);
  $z=new ZipArchive();if($z->open($tmp)!==true)throw new RuntimeException('docx archive');
  $xml=$z->getFromName('word/document.xml');$z->close();
  if(!is_string($xml)||strpos($xml,'&amp; &lt;中文&gt;')===false||strpos($xml,'✅')===false)
    throw new RuntimeException('docx XML');
 }finally{@unlink($tmp);}
 $results['actual_docx_php_utf8']=true;
 @file_put_contents($marker,'done',LOCK_EX);@chmod($marker,0600);
 echo json_encode(['stage'=>'A9.7','status'=>'CLI_ENTRYPOINT_PASS',
    'checks'=>$results,'server_http_listener_used'=>false,
    'provider_called'=>false,'openai_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
}catch(Throwable $e){
  echo json_encode(['stage'=>'A9.7','status'=>'CLI_ENTRYPOINT_FAILED',
      'reason'=>get_class($e),'checks_passed'=>array_keys($results),'openai_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
  exit(97);
}
