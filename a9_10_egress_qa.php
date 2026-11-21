<?php
declare(strict_types=1);
/* A9.10 no-key outbound TLS check. No paid provider request. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
if(!function_exists('curl_init'))exit(93);
$ch=curl_init('https://api.openai.com/v1/models');
curl_setopt_array($ch,[
 CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>false,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>15,
 CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,
 CURLOPT_HTTPHEADER=>['Accept: application/json','User-Agent: Motricialy-A9.10-NoKey-Egress-QA']
]);
$raw=curl_exec($ch);$err=curl_errno($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
$time=round((float)curl_getinfo($ch,CURLINFO_TOTAL_TIME),3);
curl_close($ch);
$ok=$err===0&&$status===401;
echo json_encode(['stage'=>'A9.10','check'=>'outbound_openai_tls_without_key',
 'status'=>$ok?'EGRESS_TLS_PASS':'EGRESS_TLS_BLOCKED',
 'http_status'=>$status,'curl_errno'=>$err,'seconds'=>$time,
 'authorization_header_sent'=>false,'paid_api_calls'=>0,'response_body_retained'=>false],JSON_UNESCAPED_SLASHES).PHP_EOL;
if(!$ok)exit(97);
