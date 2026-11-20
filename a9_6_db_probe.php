<?php
declare(strict_types=1);
/* A9.6: one-shot isolated PHP/MySQL proof. Password is an RSA-OAEP ciphertext
   in a denied directory. Private key is generated and kept OUTSIDE webroot.
   Never print secrets; never invoke OpenAI or edit any production table. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120'){exit(90);}
if(getenv('A2T_ENABLE_LIVE')==='1'){echo "FAIL_LIVE_FLAG\n";exit(91);}
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false){echo "FAIL_GATE\n";exit(92);}
$dir='/home/u342033576/.motricialy-a2t-stage-a9';
$keyfile=$dir.'/transport-private.pem';
$secretfile=$dir.'/mysql-a9.json';
$marker=$dir.'/mysql-a9-probe.done';
$cipherfile=__DIR__.'/a9_6_db_credential.encrypted.b64';
if(!is_dir($dir)||(fileperms($dir)&0777)!==0700){echo "FAIL_PRIVATE_DIR\n";exit(93);}
if(is_file($marker)){echo "A9_6_ALREADY_COMPLETED\n";exit;}
if(!is_file($keyfile)||!is_file($cipherfile)||is_link($keyfile)||is_link($cipherfile)){echo "FAIL_PROVISION_FILES\n";exit(94);}
if(is_file($secretfile)){echo "FAIL_EXISTING_CONFIG_NOT_OVERWRITTEN\n";exit(95);}
try{
 $keyRaw=@file_get_contents($keyfile);
 $private=is_string($keyRaw)?openssl_pkey_get_private($keyRaw):false;
 unset($keyRaw);
 if($private===false){throw new RuntimeException('key');}
 $d=openssl_pkey_get_details($private);
 if(!is_array($d)||($d['bits']??0)<3072||hash('sha256',(string)($d['key']??''))!=='c378c0b5ca8db16d2d05a1058447a96ad709be4c56857ee607825459e6010319'){
   throw new RuntimeException('key fingerprint');
 }
 $blob=trim((string)file_get_contents($cipherfile));
 if(strlen($blob)!==512 || !preg_match('/^[A-Za-z0-9+\/]+=*$/D',$blob))throw new RuntimeException('cipher format');
 $cipher=base64_decode($blob,true);
 if(!is_string($cipher)||strlen($cipher)!==384)throw new RuntimeException('cipher length');
 $ok=openssl_private_decrypt($cipher,$password,$private,OPENSSL_PKCS1_OAEP_PADDING);
 openssl_free_key($private);
 if($ok===false || !is_string($password) || strlen($password)<25 || strlen($password)>50)throw new RuntimeException('decrypt');
 $dsn='mysql:host=localhost;dbname=u342033576_a2t_stage_a9;charset=utf8mb4';
 $user='u342033576_a2t_a9_user';
 $pdo=new PDO($dsn,$user,$password,[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES=>false,
  PDO::ATTR_TIMEOUT=>8,
 ]);
 $tables=$pdo->query("SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA='u342033576_a2t_stage_a9' AND TABLE_NAME IN ('a2t_budget_pool','a2t_daily_quota','a2t_jobs') ORDER BY TABLE_NAME")->fetchAll();
 if(count($tables)!==3||count(array_filter($tables,static fn(array $row)=>$row['ENGINE']==='InnoDB'))!==3)throw new RuntimeException('schema');
 $when='2099-04-01';$pool='a9_6_probe';
 $check=$pdo->prepare('SELECT COUNT(*) FROM a2t_budget_pool WHERE cycle_start=? AND pool_name=?');
 $check->execute([$when,$pool]);
 if((int)$check->fetchColumn()!==0)throw new RuntimeException('probe collision');
 $pdo->beginTransaction();
 try {
  $ins=$pdo->prepare('INSERT INTO a2t_budget_pool(cycle_start,pool_name,spent_micro,reserved_micro) VALUES(?,?,123,456)');
  $ins->execute([$when,$pool]);
  $lock=$pdo->prepare('SELECT spent_micro,reserved_micro FROM a2t_budget_pool WHERE cycle_start=? AND pool_name=? FOR UPDATE');
  $lock->execute([$when,$pool]);
  $row=$lock->fetch();
  if(!is_array($row)||(int)$row['spent_micro']!==123||(int)$row['reserved_micro']!==456)throw new RuntimeException('locked values');
 } finally {
  if($pdo->inTransaction())$pdo->rollBack();
 }
 $check->execute([$when,$pool]);
 if((int)$check->fetchColumn()!==0)throw new RuntimeException('rollback persisted');
 // Persist only to private /home path (0700 parent, 0600 file), never public_html or git.
 $config=['dsn'=>$dsn,'user'=>$user,'password'=>$password,'paid_enabled'=>false,'environment'=>'isolated-a9-test'];
 $json=json_encode($config,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
 $temp=tempnam($dir,'.config-');
 if(!is_string($temp))throw new RuntimeException('secure config temp');
 @chmod($temp,0600);
 if(file_put_contents($temp,$json,LOCK_EX)!==strlen($json)){@unlink($temp);throw new RuntimeException('secure config write');}
 if(!rename($temp,$secretfile)){@unlink($temp);throw new RuntimeException('secure config rename');}
 @chmod($secretfile,0600);
 if((fileperms($secretfile)&0777)!==0600)throw new RuntimeException('permissions');
 unset($password,$json,$config);
 // Destroy ephemeral RSA secret after verified provisioning. Historical ciphertext cannot be opened.
 @unlink($keyfile);
 file_put_contents($marker,'completed',LOCK_EX);@chmod($marker,0600);
 echo json_encode(['stage'=>'A9.6','status'=>'PASS','database'=>'isolated-a9-only',
  'tables'=>array_column($tables,'TABLE_NAME'),'engine'=>'InnoDB','for_update'=>'PASS',
  'rollback'=>'PASS','persisted_test_rows'=>0,'private_config_outside_webroot'=>true,
  'private_config_mode'=>'0600','paid_enabled'=>false,'transport_private_key_removed'=>!is_file($keyfile),
  'openai_api_calls'=>0],JSON_UNESCAPED_SLASHES).PHP_EOL;
}catch(Throwable $e){
 if(isset($pdo)&&$pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();
 // Only classification, never exception text: PDO errors can contain host/user values.
 echo json_encode(['stage'=>'A9.6','status'=>'FAIL','type'=>get_class($e),'no_api_calls'=>true]).PHP_EOL;exit(97);
}
