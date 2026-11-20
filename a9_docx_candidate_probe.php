<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(9);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(10);
$marker=__DIR__.'/.a9-docx-qa-once';
$fd=@fopen($marker,'x'); if($fd===false){echo "ALREADY_COMPLETED\n";exit;}fwrite($fd,'1');fclose($fd);
define('A2T_ENTRY',true);
// Exactly the DOCX encoding algorithm from candidate private-server/docx.php (no secret code/data).
function a2t_docx(string $title,string $body): string {
    if (!class_exists('ZipArchive')) throw new RuntimeException('DOCX export unavailable');
    $escape=static fn(string $s)=>htmlspecialchars($s,ENT_XML1|ENT_QUOTES,'UTF-8');
    $lines=preg_split('/\R/u',$body) ?: [];
    $xml='';
    foreach (array_merge([$title,''],$lines) as $idx=>$line) {
        if ($idx>10000)throw new InvalidArgumentException('Too many paragraphs');
        $xml.='<w:p><w:r><w:t xml:space="preserve">'.$escape($line).'</w:t></w:r></w:p>';
    }
    $doc='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$xml.'<w:sectPr/></w:body></w:document>';
    $temp=tempnam(sys_get_temp_dir(),'a2t-docx-');
    if ($temp===false)throw new RuntimeException('Temporary DOCX file unavailable');
    try {
        $z=new ZipArchive();
        if ($z->open($temp,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Cannot prepare DOCX archive');
        $z->addFromString('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $z->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $z->addFromString('word/document.xml',$doc);
        $z->close();
        $bytes=file_get_contents($temp);
        if (!is_string($bytes)||strlen($bytes)<100)throw new RuntimeException('Empty DOCX result');
        return $bytes;
    } finally { @unlink($temp); }
}
try{
  $body="上海与 Motricialy\nAC-42 & <中文> ✅\n最后一行";
  $data=a2t_docx('我的录音编辑 / Audio & Text',$body);
  $temp=tempnam(sys_get_temp_dir(),'a2ta9-qazip-');
  if(!is_string($temp))throw new RuntimeException('Temporary test file unavailable');
  try{
    file_put_contents($temp,$data);
    $zip=new ZipArchive();
    if($zip->open($temp)!==true)throw new RuntimeException('Invalid DOCX package');
    $text=$zip->getFromName('word/document.xml');
    $types=$zip->getFromName('[Content_Types].xml');
    $rels=$zip->getFromName('_rels/.rels');
    $zip->close();
    if(!is_string($text)||!is_string($types)||!is_string($rels))throw new RuntimeException('DOCX parts missing');
    foreach(['上海与 Motricialy','AC-42 &amp; &lt;中文&gt; ✅','最后一行','我的录音编辑'] as $literal)
      if(strpos($text,$literal)===false)throw new RuntimeException('DOCX UTF8/XML content mismatch');
    if(substr_count($text,'<w:p>')<4)throw new RuntimeException('DOCX missing paragraphs');
    echo json_encode(['task'=>'a9_3_docx_candidate_hostinger','status'=>'PASS','php'=>PHP_VERSION,
      'file_bytes'=>strlen($data),'parts'=>3,'unicode_and_xml_escaping'=>'PASS','paragraph_count'=>substr_count($text,'<w:p>'),'openai_calls'=>0]).PHP_EOL;
  }finally{@unlink($temp);}
}catch(Throwable $e){echo json_encode(['task'=>'a9_3_docx_candidate_hostinger','status'=>'FAILED','class'=>get_class($e)]).PHP_EOL;exit(12);}
