<?php
declare(strict_types=1);
$dir='/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech';
$p=$dir.'/index.html';
$s=$dir.'/tts-v25-layout.js';
if(!is_file($p)){echo "FAIL:no HTML\n";exit(1);}
$h=file_get_contents($p);
if($h===false || strpos($h,'studio-workspace')===false || strpos($h,'motricialy-tracking.js')===false || strpos($h,'index-37YlTEFD.js')===false){echo "FAIL:baseline mismatch\n";exit(2);}
$script=gzuncompress(base64_decode('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',true));
if($script===false || hash('sha256',$script)!=='8ab1df4d0f4356f1d5e68c10bbcd5968584b9d4e91b714a6ee4a0042050a5312') {echo "FAIL:asset hash\n";exit(3);}
if(file_put_contents($s,$script,LOCK_EX)!==strlen($script)){echo "FAIL:JS write\n";exit(4);}
if(hash_file('sha256',$s)!=='8ab1df4d0f4356f1d5e68c10bbcd5968584b9d4e91b714a6ee4a0042050a5312') {echo "FAIL:JS verify\n";exit(5);}
$marker='<script defer src="/tools/text-to-speech/tts-v25-layout.js"></script>';
if(strpos($h,$marker)!==false){echo "TTS_V25_ALREADY_ENABLED\n";exit(0);}
if(!preg_match('~</body>~i',$h)){echo "FAIL:no closing body\n";exit(6);}
$backup='/home/u342033576/domains/motricialy.com/tts-v25-before-index.html';
if(!is_file($backup) && !copy($p,$backup)){echo "FAIL:backup\n";exit(7);}
$new=preg_replace('~</body>~i',"$marker\n</body>",$h,1);
$tmp=$dir.'/.index-v25-tmp-'.getmypid();
if(file_put_contents($tmp,$new,LOCK_EX)!==strlen($new) || !rename($tmp,$p)){echo "FAIL:HTML update\n";exit(8);}
clearstatcache();
$read=file_get_contents($p);
if(strpos($read,$marker)===false){echo "FAIL:verify marker\n";exit(9);}
echo "TTS_V25_DEPLOY_OK JS=8ab1df4d0f4356f1d5e68c10bbcd5968584b9d4e91b714a6ee4a0042050a5312 BACKUP=present\n";
