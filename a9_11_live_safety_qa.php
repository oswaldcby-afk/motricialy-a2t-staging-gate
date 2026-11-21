<?php
declare(strict_types=1);
/* A9.11 live public safety smoke. No paid provider request. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$url='https://motricialy.com/tools/audio-to-text/';
$o=['stage'=>'A9.11','scope'=>'anonymous_live_release_http','paid_requests'=>0];
function rq(string $url,string $method='GET',array $headers=[],?string $body=null):array{
 $ch=curl_init($url);if(!$ch)throw new RuntimeException('curl');
 curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,
 CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,
 CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>16,CURLOPT_FOLLOWLOCATION=>false,
 CURLOPT_MAXREDIRS=>0,CURLOPT_CUSTOMREQUEST=>$method,
 CURLOPT_HTTPHEADER=>array_merge(['Accept: application/json','User-Agent: Motricialy-A9.11-Post-Launch-Safety'], $headers)]);
 if($body!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,$body);
 $raw=curl_exec($ch);$errno=curl_errno($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
 $n=(int)curl_getinfo($ch,CURLINFO_HEADER_SIZE);curl_close($ch);
 if(!is_string($raw)||$errno!==0)throw new RuntimeException('network_'.$errno);
 return ['http'=>$code,'headers'=>substr($raw,0,$n),'body'=>substr($raw,$n)];
}
try{
 $flag=$root.'/a9-11-public-enabled.flag';
 $o['flag_valid']=is_file($flag)&&(fileperms($flag)&0777)===0600&&trim((string)file_get_contents($flag))==='enabled';
 if(!$o['flag_valid'])throw new RuntimeException('not_public_enabled');
 $p=rq($url);
 $o['page_200']=$p['http']===200;
 $o['indexable']=stripos($p['body'],'name="robots" content="noindex')===false&&stripos($p['headers'],'X-Robots-Tag: noindex')===false;
 $o['one_h1']=preg_match_all('/<h1\\b/i',$p['body'])===1;
 $o['canonical_correct']=str_contains($p['body'],'https://motricialy.com/tools/audio-to-text/');
 $c=rq($url.'api/config');
 $data=json_decode($c['body'],true);
 $o['anonymous_config_ready']=$c['http']===200&&is_array($data)&&($data['live_api_ready']??false)===true&&($data['ai_paused']??true)===false;
 $o['config_budget_remaining_usd']=$data['budget']['public_remaining_estimated_usd']??null;
 $o['daily_files_limit']=$data['max_files_daily']??null;
 $o['max_file_minutes']=$data['max_file_minutes']??null;
 $x=rq($url.'api/process','POST',['Origin: https://motricialy.com','Content-Type: application/json'],json_encode(['text'=>'Hi','mode'=>'summary']));
 $o['advanced_text_disabled']=$x['http']===503;
 $y=rq($url.'api/transcribe','POST',['Origin: https://invalid.example','Content-Type: application/json'],'{}');
 $o['cross_site_post_denied']=$y['http']===403;
 $z=rq($url.'api/transcribe','POST',['Origin: https://motricialy.com','Content-Type: application/x-www-form-urlencoded'],'foo=bar');
 $o['missing_audio_denied']=in_array($z['http'],[413,422],true);
 $w=rq($url.'api/export/docx','POST',['Origin: https://motricialy.com','Content-Type: application/json'],
  json_encode(['title'=>'Live QA','text'=>'Hello & <中文>'],JSON_UNESCAPED_UNICODE));
 $o['docx_http_download']=$w['http']===200&&str_starts_with($w['body'],'PK');
 $o['existing_tts_200']=rq('https://motricialy.com/tools/text-to-speech/')['http']===200;
 $outOk=true;
 foreach($o as $k=>$v)if(is_bool($v)&&!$v)$outOk=false;
 $o['status']=$outOk?'LIVE_PUBLIC_SAFETY_PASS':'LIVE_PUBLIC_SAFETY_FAILED';
}catch(Throwable $e){$o['status']='LIVE_PUBLIC_SAFETY_ERROR';$o['failure_category']=$e->getMessage();}
echo json_encode($o,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if($o['status']!=='LIVE_PUBLIC_SAFETY_PASS')exit(97);
