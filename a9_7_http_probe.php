<?php
declare(strict_types=1);
// Stage A9.7, loopback-only real PHP HTTP smoke test from protected CLI.
// No secrets, no paid provider, no public web endpoint.
if (PHP_SAPI !== 'cli' || basename(__DIR__) !== 'audio-to-text-a9-private-gate-20261120') exit(90);
if (strpos((string)@file_get_contents(__DIR__.'/.htaccess'), 'Require all denied') === false) exit(91);
if (getenv('A2T_ENABLE_LIVE') === '1') exit(92);
$root = '/home/u342033576/.motricialy-a2t-stage-a9';
$install = $root.'/app-a9-7-full';
$documentRoot = $install.'/test-public';
$private = $install.'/private-server';
$entry = $documentRoot.'/tools/audio-to-text/api/index.php';
$marker = $root.'/a9-7-http-qa.done';
if (file_exists($marker)) {echo "ALREADY_COMPLETED\n"; exit;}
if (!is_file($entry) || !is_file($private.'/bootstrap.php') || !is_file($private.'/docx.php')) exit(93);
if ((fileperms($root)&0777)!==0700 || (fileperms($private)&0777)!==0700) exit(94);
if (!function_exists('proc_open') || !function_exists('curl_init') || !class_exists('ZipArchive')) exit(95);
$router = $root.'/.a9-7-router-'.bin2hex(random_bytes(5)).'.php';
$server = null;
$port = random_int(21000, 43000);
$src = <<<'ROUTER'
<?php
putenv('A2T_PRIVATE_PATH=/home/u342033576/.motricialy-a2t-stage-a9/app-a9-7-full/private-server');
putenv('A2T_ENABLE_LIVE=0');
putenv('A2T_PAUSE_AI=1');
putenv('OPENAI_API_KEY');
if (!str_starts_with((string)parse_url($_SERVER['REQUEST_URI']??'', PHP_URL_PATH), '/tools/audio-to-text/api/')) {
    http_response_code(404); echo 'Not found'; return;
}
require '/home/u342033576/.motricialy-a2t-stage-a9/app-a9-7-full/test-public/tools/audio-to-text/api/index.php';
ROUTER;
$results = [];
$failed = false;
try {
    if (file_put_contents($router, $src, LOCK_EX) !== strlen($src)) throw new RuntimeException('router write');
    @chmod($router, 0600);
    $cmd = [PHP_BINARY, '-S', '127.0.0.1:'.$port, '-t', $documentRoot, $router];
    $io = [0=>['pipe','r'], 1=>['file','/dev/null','a'], 2=>['file','/dev/null','a']];
    $server = @proc_open($cmd, $io, $pipes, $root, null, ['bypass_shell'=>true]);
    if (!is_resource($server)) throw new RuntimeException('loopback server launch unavailable');
    fclose($pipes[0]);
    $request = static function(string $route,string $method='GET',?string $payload=null,array $headers=[])use($port):array {
        $url='http://127.0.0.1:'.$port.'/tools/audio-to-text/api/'.$route;
        $h=curl_init($url);
        if(!$h)throw new RuntimeException('curl init');
        curl_setopt_array($h,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,
            CURLOPT_CONNECTTIMEOUT=>2,CURLOPT_TIMEOUT=>6,CURLOPT_MAXREDIRS=>0,
            CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROXY=>'',CURLOPT_NOPROXY=>'127.0.0.1',
            CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$headers]);
        if($payload!==null) curl_setopt($h,CURLOPT_POSTFIELDS,$payload);
        $response=curl_exec($h);
        $status=(int)curl_getinfo($h,CURLINFO_HTTP_CODE);
        $n=(int)curl_getinfo($h,CURLINFO_HEADER_SIZE);
        $errno=curl_errno($h);
        curl_close($h);
        if(!is_string($response)||$errno)throw new RuntimeException('loopback HTTP unavailable');
        return ['status'=>$status,'headers'=>substr($response,0,$n),'body'=>substr($response,$n)];
    };
    $connected=false;
    for($i=0;$i<24;$i++) {
        try{$x=$request('config');if($x['status']===200){$connected=true;break;}}
        catch(Throwable $e){}
        usleep(180000);
    }
    if(!$connected)throw new RuntimeException('loopback server did not start');
    $config=json_decode($x['body'],true);
    if(!is_array($config)||($config['live_api_ready']??true)!==false||($config['ai_paused']??false)!==true)
      throw new RuntimeException('config is not fail-closed');
    $results['config_live_disabled']=true;
    $headers=['Origin: https://motricialy.com','Content-Type: application/json'];
    $a=$request('transcribe','POST','', ['Origin: https://motricialy.com','Content-Type: application/x-www-form-urlencoded']);
    if($a['status']!==503)throw new RuntimeException('transcription not disabled');
    $results['paid_transcription_http_503']=true;
    $a=$request('process','POST',json_encode(['text'=>'Hello', 'mode'=>'clean']),$headers);
    if($a['status']!==503)throw new RuntimeException('AI process not disabled');
    $results['paid_text_processing_http_503']=true;
    $a=$request('process','POST',json_encode(['text'=>'Hello','mode'=>'clean']),['Origin: https://another.example','Content-Type: application/json']);
    if($a['status']!==403)throw new RuntimeException('cross-origin not rejected');
    $results['origin_block_http_403']=true;
    $a=$request('export/docx','POST',json_encode(['title'=>'中文 QA','text'=>'Hello & <中文> ✅'],JSON_UNESCAPED_UNICODE),$headers);
    if($a['status']!==200||strpos($a['headers'],'application/vnd.openxmlformats-officedocument.wordprocessingml.document')===false)
      throw new RuntimeException('DOCX HTTP status or content type');
    $temp=tempnam(sys_get_temp_dir(),'a9docx-');
    if(!is_string($temp))throw new RuntimeException('temp');
    try{
        file_put_contents($temp,$a['body']);
        $z=new ZipArchive();if($z->open($temp)!==true)throw new RuntimeException('invalid docx package');
        $xml=$z->getFromName('word/document.xml');$z->close();
        if(!is_string($xml)||strpos($xml,'&amp; &lt;中文&gt;')===false||strpos($xml,'✅')===false)
            throw new RuntimeException('docx escaped text invalid');
    }finally{unlink($temp);}
    $results['docx_real_http_download_and_utf8']=true;
    $a=$request('export/docx','POST',json_encode(['title'=>'QA','text'=>'']),$headers);
    if($a['status']!==422)throw new RuntimeException('empty DOCX validation');
    $results['docx_invalid_input_422']=true;
    $a=$request('unexpected','GET');
    if($a['status']!==404)throw new RuntimeException('unknown route');
    $results['unknown_route_404']=true;
    $results['http_server_bound_to_loopback_only']=true;
    @file_put_contents($marker,'done',LOCK_EX);
    @chmod($marker,0600);
    echo json_encode(['stage'=>'A9.7','status'=>'LOOPBACK_HTTP_PASS','tests'=>$results,
        'public_url_published'=>false,'openai_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
}catch(Throwable $e){
    echo json_encode(['stage'=>'A9.7','status'=>'LOOPBACK_HTTP_FAILED','reason_class'=>get_class($e),
      'passed_tests'=>$results,'openai_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
    $failed = true;
}finally{
    if (is_resource($server)) { @proc_terminate($server); @proc_close($server); }
    @unlink($router);
}

if ($failed) exit(97);
