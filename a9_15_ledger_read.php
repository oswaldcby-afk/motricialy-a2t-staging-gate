<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$base='/home/u342033576/.motricialy-a2t-stage-a9/app-a9-7-full/private-server/';
$allow=['ledger.php','bootstrap.php'];
foreach($allow as $p){
 $name=$base.$p;
 if(!is_file($name)||is_link($name))exit(92);
 $src=(string)file_get_contents($name);
 echo '=== '.$p.' ==='.PHP_EOL;
 echo substr($src,0,15000).PHP_EOL;
}
