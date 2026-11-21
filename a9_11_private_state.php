<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$out=['stage'=>'A9.11','status'=>'PRIVATE_STATE'];
foreach(['a9-11-transport-private.pem','a9-11-openai-key.private','a9-11-production-hmac.private','a9-11-release-qa-gate.private','a9-11-mysql-production.json'] as $name){
 $p=$root.'/'.$name;$out['files'][$name]=['exists'=>is_file($p),'secure'=>is_file($p)&&((fileperms($p)&0777)===0600),'size_category'=>is_file($p)?(filesize($p)>0?'positive':'empty'):null];
}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
