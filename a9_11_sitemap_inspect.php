<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$p='/home/u342033576/domains/motricialy.com/public_html/tools-sitemap.xml';
if(!is_file($p)||is_link($p))exit(92);
$xml=(string)file_get_contents($p);
$target='https://motricialy.com/tools/audio-to-text/';
$o=['stage'=>'A9.11','check'=>'static_tools_sitemap','file_bytes'=>strlen($xml),
 'contains_audio_to_text'=>str_contains($xml,$target),
 'is_well_formed'=>false,'url_count'=>0,'start'=>substr($xml,0,350),'end'=>substr($xml,-550)];
try{
 $doc=new DOMDocument();$doc->loadXML($xml,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
 $o['is_well_formed']=$doc->documentElement->localName==='urlset';
 $o['url_count']=$doc->getElementsByTagName('url')->length;
}catch(Throwable $e){}
echo json_encode($o,JSON_UNESCAPED_SLASHES).PHP_EOL;
