<?php
declare(strict_types=1);
/* A9.11 narrow, atomic static tools-sitemap.xml append with private backup.
   No WordPress changes, no paid requests, no other URL mutations. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$root='/home/u342033576/domains/motricialy.com/public_html';
$base='/home/u342033576/.motricialy-a2t-stage-a9';
$sitemap=$root.'/tools-sitemap.xml';
$backup=$base.'/tools-sitemap-before-a2t-20261121.xml';
$target='https://motricialy.com/tools/audio-to-text/';
$out=['stage'=>'A9.11','operation'=>'append_exactly_one_audio_to_text_url','paid_calls'=>0];
$tmp=null;
try{
 if(!is_file($sitemap)||is_link($sitemap)||!is_dir($base)||(fileperms($base)&0777)!==0700)throw new RuntimeException('scope_invalid');
 $old=(string)file_get_contents($sitemap);
 if(strlen($old)<1000||strlen($old)>100000)throw new RuntimeException('size_unexpected');
 $doc=new DOMDocument();
 if(!$doc->loadXML($old,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING)||$doc->documentElement->localName!=='urlset')
  throw new RuntimeException('old_xml_invalid');
 $existing=$doc->getElementsByTagName('url')->length;
 $out['urls_before']=$existing;
 if($existing<100||$existing>250)throw new RuntimeException('unexpected_tool_count');
 if(str_contains($old,$target)){
  $out['status']='ALREADY_INCLUDED';
  $out['urls_after']=$existing;
  echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;exit;
 }
 if(substr_count($old,'</urlset>')!==1)throw new RuntimeException('closing_tag_invalid');
 if(!is_file($backup)){
   $fp=@fopen($backup,'x');if(!$fp)throw new RuntimeException('backup_create');
   @chmod($backup,0600);
   $ok=fwrite($fp,$old)===strlen($old);fflush($fp);fclose($fp);@chmod($backup,0600);
   if(!$ok||hash_file('sha256',$backup)!==hash('sha256',$old))throw new RuntimeException('backup_integrity');
 }
 $line="  <url><loc>".$target."</loc></url>\n";
 $new=str_replace('</urlset>',$line.'</urlset>',$old);
 $validate=new DOMDocument();
 if(!$validate->loadXML($new,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING)||
 $validate->getElementsByTagName('url')->length!==$existing+1)
 throw new RuntimeException('new_xml_invalid');
 $tmp=tempnam($root,'a2t-sitemap-');
 if(!is_string($tmp))throw new RuntimeException('temp_create');
 if(file_put_contents($tmp,$new,LOCK_EX)!==strlen($new))throw new RuntimeException('write_failed');
 @chmod($tmp,(fileperms($sitemap)&0777));
 if(!rename($tmp,$sitemap))throw new RuntimeException('rename_failed');
 $tmp=null;
 if(hash_file('sha256',$sitemap)!==hash('sha256',$new))throw new RuntimeException('final_hash_invalid');
 $out['status']='SITEMAP_ONE_URL_ADDED';
 $out['urls_after']=$existing+1;
 $out['original_backup_private']=true;
 $out['sitemap_xml_valid']=true;
}catch(Throwable $e){$out['status']='SITEMAP_UPDATE_FAILED';$out['reason']=$e->getMessage();}
finally{if(is_string($tmp)&&is_file($tmp))@unlink($tmp);}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if(!in_array($out['status'],['SITEMAP_ONE_URL_ADDED','ALREADY_INCLUDED'],true))exit(97);
