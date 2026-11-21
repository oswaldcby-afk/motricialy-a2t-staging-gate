<?php
declare(strict_types=1);
/* A9.11 RC HTTPS regression, CLI-only, all paid routes stay disabled. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$base='https://motricialy.com/staging/audio-to-text-rc-20261121/';
$out=['stage'=>'A9.11','mode'=>'public_https_release_candidate_paid_off','paid_provider_calls'=>0];
function get_page(string $url,string $method='GET',?string $body=null,array $headers=[]):array{
 $ch=curl_init($url);
 if(!$ch)throw new RuntimeException('curl_unavailable');
 $h=['User-Agent: Motricialy-A9.11-RC-QA/1.0','Accept: */*'];
 $h=array_merge($h,$headers);
 curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,
  CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_SSL_VERIFYPEER=>true,
  CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>24,CURLOPT_FOLLOWLOCATION=>false,
  CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$h]);
 if($body!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,$body);
 $v=curl_exec($ch);$e=curl_errno($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
 $n=(int)curl_getinfo($ch,CURLINFO_HEADER_SIZE);curl_close($ch);
 if(!is_string($v)||$e!==0)throw new RuntimeException('http_transport_failure_'.$e);
 return ['code'=>$code,'headers'=>substr($v,0,$n),'body'=>substr($v,$n)];
}
try{
 $p=get_page($base);
 $html=$p['body'];$out['page_http_200']=$p['code']===200;
 $out['h1_once']=preg_match_all('/<h1\\b/i',$html)===1;
 $out['seo_content']=
  str_contains($html,'SoftwareApplication')&&str_contains($html,'BreadcrumbList')&&
  str_contains($html,'How to convert audio to text')&&str_contains($html,'Audio to Text FAQ')&&
  str_contains($html,'Explore more Motricialy tools');
 $out['site_chrome_mounts']=str_contains($html,'data-motricialy-site-header')&&str_contains($html,'data-motricialy-site-footer')
  &&substr_count($html,'/assets/motricialy-site-chrome.js')===1;
 $out['robots_noindex']=stripos($p['headers'],'noindex')!==false||stripos($html,'noindex,nofollow')!==false;
 $out['mobile_viewport']=str_contains($html,'name="viewport"');
 $out['css']=$thisCss=get_page($base.'assets/audio-to-text.css')['code']===200;
 $out['js']=get_page($base.'assets/audio-to-text.js')['code']===200;
 $cfg=get_page($base.'api/config');
 $json=json_decode($cfg['body'],true);
 $out['config_disabled']=$cfg['code']===200&&is_array($json)&&($json['live_api_ready']??true)===false;
 $headers=['Origin: https://motricialy.com','Content-Type: application/x-www-form-urlencoded'];
 $tr=get_page($base.'api/transcribe','POST','', $headers);
 $out['transcription_503']=$tr['code']===503;
 $doc=get_page($base.'api/export/docx','POST',
  json_encode(['title'=>'A9.11','text'=>'Hello & <中文>'],JSON_UNESCAPED_UNICODE),
  ['Origin: https://motricialy.com','Content-Type: application/json']);
 $out['docx_http_200']=$doc['code']===200&&str_starts_with($doc['body'],'PK');
 $out['existing_tts_unchanged']=get_page('https://motricialy.com/tools/text-to-speech/')['code']===200;
 $out['chrome_asset_present']=get_page('https://motricialy.com/assets/motricialy-site-chrome.js')['code']===200;
 $ok=true;
 foreach($out as $k=>$v)if(is_bool($v)&&!$v)$ok=false;
 $out['status']=$ok?'RELEASE_RC_HTTPS_PASS':'RELEASE_RC_HTTPS_FAILED';
}catch(Throwable $e){$out['status']='RELEASE_RC_TEST_EXCEPTION';$out['error_type']=get_class($e);$out['error_category']=$e->getMessage();}
echo json_encode($out,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
if($out['status']!=='RELEASE_RC_HTTPS_PASS')exit(97);
