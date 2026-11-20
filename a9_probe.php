<?php
declare(strict_types=1);
// Staging-only CLI probe. No database connection, user data or API calls.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
if (basename(__DIR__) !== 'audio-to-text-a9-private-gate-20261120') { echo "INVALID_DIR\n"; exit(7); }
$access = @file_get_contents(__DIR__.'/.htaccess');
if (!is_string($access) || strpos($access,'Require all denied') === false) { echo "GATE_NOT_CONFIRMED\n"; exit(8); }
$marker=__DIR__.'/.a9-cli-probe-once';
if (file_exists($marker)) { echo "A9_PROBE_ALREADY_COMPLETED\n"; exit; }
$drivers=class_exists('PDO') ? PDO::getAvailableDrivers() : [];
$out=[
 'task'=>'a9_private_php_environment_probe','version'=>1,'php_version'=>PHP_VERSION,
 'access_gate'=>'Require all denied',
 'pdo_mysql'=>in_array('mysql',$drivers,true),
 'curl'=>extension_loaded('curl'),
 'fileinfo'=>extension_loaded('fileinfo'),
 'zip'=>class_exists('ZipArchive'),
 'mbstring'=>extension_loaded('mbstring')
];
foreach(['ffmpeg','ffprobe'] as $exe){
  $out[$exe]='unavailable_or_disabled';
  if(!function_exists('proc_open'))continue;
  try{
    $pipes=[];
    $p=@proc_open([$exe,'-version'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,null,['bypass_shell'=>true]);
    if(is_resource($p)){
      fclose($pipes[0]);$line=fgets($pipes[1],300);fclose($pipes[1]);fclose($pipes[2]);
      $code=proc_close($p);
      $out[$exe]=($code===0 && is_string($line))?'available':'failed_to_invoke';
    }
  }catch(Throwable $e){$out[$exe]='failed_to_invoke';}
}
$out['docx_package']='unavailable';
if(class_exists('ZipArchive')){
  $tmp=tempnam(sys_get_temp_dir(),'a2ta9-');
  if(is_string($tmp)){
    try{
      $z=new ZipArchive();
      if($z->open($tmp,ZipArchive::OVERWRITE|ZipArchive::CREATE)===true){
        $z->addFromString('[Content_Types].xml','<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $z->addFromString('_rels/.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $z->addFromString('word/document.xml','<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>QA</w:t></w:r></w:p></w:body></w:document>');
        $z->close();
        $q=new ZipArchive();
        if($q->open($tmp)===true){$out['docx_package']=$q->locateName('word/document.xml')!==false?'zip_roundtrip_pass':'missing_xml';$q->close();}
      }
    }finally{@unlink($tmp);}
  }
}
@file_put_contents($marker,'1',LOCK_EX);
echo json_encode($out,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
