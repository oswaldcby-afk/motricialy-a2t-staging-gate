<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(9);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(10);
$marker=__DIR__.'/.a9-5-php-cli-once';
$h=@fopen($marker,'x');if($h===false){echo "READINESS_ALREADY_EXECUTED\n";exit;}
fwrite($h,'1');fclose($h);
$envKeys=['A2T_PRIVATE_PATH','A2T_MYSQL_DSN','A2T_MYSQL_USER','A2T_MYSQL_PASSWORD','A2T_HMAC_KEY','OPENAI_API_KEY','A2T_BILLING_DAY','A2T_ENABLE_LIVE'];
$config=[];
foreach($envKeys as $k){$v=getenv($k);$config[$k]=is_string($v)&&$v!=='';}
$out=['task'=>'stage_a9_5_readiness','php_version'=>PHP_VERSION,
 'pdo_mysql'=>in_array('mysql',PDO::getAvailableDrivers(),true),
 'zip'=>class_exists('ZipArchive'),'curl'=>extension_loaded('curl'),
 'fileinfo'=>extension_loaded('fileinfo'),'mbstring'=>extension_loaded('mbstring'),
 'env_keys_present'=>$config,'paid_operations_disabled'=>getenv('A2T_ENABLE_LIVE')!=='1',
 'private_source_deployed'=>false,'db_transaction_performed'=>false,'openai_api_requests'=>0];
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
