<?php
declare(strict_types=1);
/* Safe one-shot A9.7 loopback diagnostic; never touches customer data or paid APIs. */
if (PHP_SAPI !== 'cli' || basename(__DIR__) !== 'audio-to-text-a9-private-gate-20261120') exit(90);
if (strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied') === false) exit(91);
if (getenv('A2T_ENABLE_LIVE') === '1') exit(92);
$base='/home/u342033576/.motricialy-a2t-stage-a9';
if (!is_dir($base) || is_link($base) || (fileperms($base)&0777)!==0700) exit(93);
$out=['stage'=>'A9.7','check'=>'loopback_diagnostic','paid_api_calls'=>0];
$proc=null;$dir=null;$pipes=[];$port=random_int(20000,46000);
try {
 $out['php_cli']=PHP_SAPI==='cli';
 $out['proc_open_available']=function_exists('proc_open');
 $out['curl_available']=function_exists('curl_init');
 $out['stream_socket_available']=function_exists('stream_socket_server');
 $errno=0;$errstr='';
 if (function_exists('stream_socket_server')) {
  $sock=@stream_socket_server('tcp://127.0.0.1:0',$errno,$errstr,STREAM_SERVER_BIND|STREAM_SERVER_LISTEN);
  $out['local_bind_available']=is_resource($sock);
  $out['local_bind_error_number']=$out['local_bind_available']?0:$errno;
  if (is_resource($sock))fclose($sock);
 }
 $dir=$base.'/a9-7-loopback-'.bin2hex(random_bytes(8));
 if (!@mkdir($dir,0700)) throw new RuntimeException('temp_dir_unavailable');
 @chmod($dir,0700);
 file_put_contents($dir.'/index.html','A9_LOOPBACK_OK',LOCK_EX);
 @chmod($dir.'/index.html',0600);
 $stdout=$dir.'/stdout.log';$stderr=$dir.'/stderr.log';
 $io=[0=>['pipe','r'],1=>['file',$stdout,'a'],2=>['file',$stderr,'a']];
 $cmd=[PHP_BINARY,'-S','127.0.0.1:'.$port,'-t',$dir];
 $proc=@proc_open($cmd,$io,$pipes,$dir,null,['bypass_shell'=>true]);
 $out['php_http_process_spawned']=is_resource($proc);
 if (!is_resource($proc)) throw new RuntimeException('spawn_blocked');
 if(isset($pipes[0])&&is_resource($pipes[0]))fclose($pipes[0]);
 $connected=false;$curlErr=0;$lastHttp=0;
 for($i=0;$i<12;$i++){
  usleep(250000);
  if(!function_exists('curl_init')) break;
  $ch=curl_init('http://127.0.0.1:'.$port.'/index.html');
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>1,
    CURLOPT_TIMEOUT=>2,CURLOPT_PROXY=>'',CURLOPT_NOPROXY=>'127.0.0.1']);
  $body=curl_exec($ch);
  $curlErr=curl_errno($ch);
  $lastHttp=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
  curl_close($ch);
  if($body==='A9_LOOPBACK_OK'&&$lastHttp===200){$connected=true;break;}
  $ps=proc_get_status($proc);
  if(!$ps['running'])break;
 }

 $directErr=0;$directError='';
 $direct=@stream_socket_client('tcp://127.0.0.1:'.$port,$directErr,$directError,2,STREAM_CLIENT_CONNECT);
 $out['direct_tcp_connected']=is_resource($direct);
 $out['direct_tcp_errno']=is_resource($direct)?0:$directErr;
 if(is_resource($direct)){
   stream_set_timeout($direct,2);
   fwrite($direct,"GET /index.html HTTP/1.0\r\nHost: localhost\r\nConnection: close\r\n\r\n");
   $response=(string)stream_get_contents($direct);
   $out['direct_http_success']=str_contains($response,'A9_LOOPBACK_OK');
   fclose($direct);
 }
 $out['loopback_http_success']=$connected;
 $out['curl_errno']=$curlErr;
 $out['last_http_status']=$lastHttp;
 $ps=proc_get_status($proc);
 $out['child_still_running']=($ps['running']??false);
 $out['child_exit_code']=($ps['exitcode']??-1);
 $stderrTxt=is_file($stderr)?(string)file_get_contents($stderr):'';
 $out['stderr_started']=str_contains($stderrTxt,'Development Server') || str_contains($stderrTxt,'started');
 $out['stderr_size_bytes']=strlen($stderrTxt);
 $out['stderr_failed_to_listen']=str_contains($stderrTxt,'Failed to listen');
 $out['stderr_address_in_use']=str_contains($stderrTxt,'Address already in use');
 $out['stderr_permission_denied']=str_contains(strtolower($stderrTxt),'permission denied');
 $out['status']=$connected?'DIAGNOSTIC_PASS':'DIAGNOSTIC_BLOCKED';
}catch(Throwable $e){$out['status']='DIAGNOSTIC_FAILED';$out['reason']=$e->getMessage();}
finally{
 if(is_resource($proc)){@proc_terminate($proc);@proc_close($proc);}
 if(is_string($dir)&&is_dir($dir)){
  foreach(['index.html','stdout.log','stderr.log'] as $x){if(is_file($dir.'/'.$x))@unlink($dir.'/'.$x);}
  @rmdir($dir);
 }
}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
