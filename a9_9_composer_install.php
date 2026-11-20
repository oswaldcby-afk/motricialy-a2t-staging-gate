<?php
declare(strict_types=1);
/* Stage A9.9: isolated Composer install of LOCKED PHP-only getID3 dependency.
   No APIs, no credentials, no modification of public_html, no plugins/scripts. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
if(getenv('A2T_ENABLE_LIVE')==='1')exit(92);
$root='/home/u342033576/.motricialy-a2t-stage-a9';
$private=$root.'/app-a9-7-full/private-server';
$out=['stage'=>'A9.9','action'=>'locked_composer2_install','paid_api_calls'=>0,'public_files_modified'=>false];
$proc=null;
try{
 if(!is_dir($private)||is_link($private)||(fileperms($private)&0777)!==0700)throw new RuntimeException('private_dir_invalid');
 $hashes=['composer.json'=>'0fd343c09bd85f349438b3720ea36e91b87d2ec4270d5cfd09572dbf7a776b45',
 'composer.lock'=>'8b8a6f616c4a075c57908d461f9d633b0496796d61e74f134240080ee5927698'];
 foreach($hashes as $f=>$hash)if(!is_file($private.'/'.$f)||is_link($private.'/'.$f)||hash_file('sha256',$private.'/'.$f)!==$hash)
 throw new RuntimeException('dependency_manifest_hash_mismatch');
 $composer='/usr/local/bin/composer2';
 $php='/opt/alt/php81/usr/bin/php';
 if(!is_file($composer)||!is_executable($composer)||!is_file($php)||!is_executable($php))throw new RuntimeException('composer2_not_available');
 $vendor=$private.'/vendor';
 if(is_link($vendor))throw new RuntimeException('vendor_symlink');
 $existing=is_file($vendor.'/autoload.php');
 if(!$existing) {
   foreach(['composer-home','composer-cache'] as $dir){
    $d=$root.'/'.$dir;
    if(!is_dir($d)&&!@mkdir($d,0700))throw new RuntimeException('cache_dir_failure');
    if(is_link($d))throw new RuntimeException('cache_symlink');
    @chmod($d,0700);
   }
   $log=$root.'/a9-9-composer-install.log';
   $cmd=[$php,$composer,'install','--no-dev','--prefer-dist','--no-plugins','--no-scripts','--no-interaction','--no-progress','--no-ansi','--optimize-autoloader'];
   $env=['HOME'=>$root,'PATH'=>'/usr/local/bin:/usr/bin:/bin',
     'COMPOSER_HOME'=>$root.'/composer-home',
     'COMPOSER_CACHE_DIR'=>$root.'/composer-cache','COMPOSER_NO_INTERACTION'=>'1'];
   $proc=@proc_open($cmd,[0=>['pipe','r'],1=>['file',$log,'w'],2=>['file',$log,'a']],$pipes,$private,$env,['bypass_shell'=>true]);
   if(!is_resource($proc))throw new RuntimeException('composer_start_blocked');
   fclose($pipes[0]);$start=microtime(true);$exit=-999;
   do{
     usleep(250000);
     $state=proc_get_status($proc);
     if(!$state['running']){$exit=(int)$state['exitcode'];break;}
     if(microtime(true)-$start>120){@proc_terminate($proc);throw new RuntimeException('composer_timeout');}
   }while(true);
   @proc_close($proc);$proc=null;
   $out['composer_exit']=$exit;
   $logContent=is_file($log)?(string)file_get_contents($log):'';
   $out['composer_network_error']=stripos($logContent,'curl error')!==false||stripos($logContent,'Could not resolve host')!==false;
   $out['composer_missing_extensions']=stripos($logContent,'requires ext-')!==false;
   $out['composer_lock_outdated']=stripos($logContent,'lock file is not up to date')!==false;
   @unlink($log);
   if($exit!==0)throw new RuntimeException('composer_install_failed');
 }
 foreach($hashes as $f=>$hash)if(hash_file('sha256',$private.'/'.$f)!==$hash)throw new RuntimeException('manifest_changed_after_install');
 if(!is_file($vendor.'/autoload.php')||is_link($vendor.'/autoload.php'))throw new RuntimeException('autoload_missing');
 $iter=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vendor,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);
 $count=0;
 foreach($iter as $entry){
   if($entry->isLink())throw new RuntimeException('vendor_symlink_found');
   @chmod($entry->getPathname(),$entry->isDir()?0700:0600);$count++;
   if($count>2000)throw new RuntimeException('vendor_tree_abnormally_large');
 }
 @chmod($vendor,0700);
 require_once $vendor.'/autoload.php';
 $out['getid3_autoload_available']=class_exists('getID3');
 $out['installed_vendor_file_count']=$count;
 $out['manifest_hashes_unchanged']=true;
 $out['vendor_outside_webroot']=true;
 $out['status']=$out['getid3_autoload_available']?'DEPENDENCY_READY':'AUTOLOAD_CLASS_MISSING';
}catch(Throwable $e){$out['status']='DEPENDENCY_NOT_READY';$out['reason']=$e->getMessage();}
finally{if(is_resource($proc)){@proc_terminate($proc);@proc_close($proc);}}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']!=='DEPENDENCY_READY')exit(97);
