<?php
declare(strict_types=1);
/* Motricialy TTS v2.14: guarded, per-visitor once-daily failed-request allowance. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$root='/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech';
$target=$root.'/api/budget.php';
$bootstrap=$root.'/api/bootstrap.php';
$ledger='/home/u342033576/domains/motricialy.com/motricialy-tts-private/ledger.sqlite';
$old='62959341a9a0222be6db38b863da009dcd4b367d001de52c2ba790c2896142a5';
$new='9a8f5675da21053ef7a886693458375774c9adeb1e773ca52dd4472fafc18161';
function v214fail(string $code):never{echo 'TTS_V214_FAIL_'.$code."\n";exit(20);}
function one214(string $code,string $before,string $after):string {
 if(substr_count($code,$before)!==1)v214fail('ANCHOR');
 return str_replace($before,$after,$code);
}
$src=@file_get_contents($target);
if(!is_string($src)||hash('sha256',$src)!==$old)v214fail('LIVE_BASELINE');
$helper=<<<'PHPSOURCE'
/** Return one failed generation's daily quota, at most once per anonymous visitor per UTC day.
 * Call only after conservatively recording the request's full reserved cost, within
 * the surrounding settlement transaction. This does not refund API spending. */
function tts_failed_quota_grace(PDO $db,array $r):void {
    try {
      $action=(string)($r['action']??'');
      if($action!=='full'&&$action!=='partial')return;
      $db->exec('CREATE TABLE IF NOT EXISTS failure_grace (visitor TEXT NOT NULL, day TEXT NOT NULL, receipt_id TEXT NOT NULL UNIQUE, created_at INTEGER NOT NULL, PRIMARY KEY(visitor,day))');
      $q=$db->prepare('INSERT OR IGNORE INTO failure_grace(visitor,day,receipt_id,created_at) VALUES(?,?,?,?)');
      $q->execute([$r['visitor'],$r['admitted_day'],$r['id'],time()]);
      if($q->rowCount()!==1)return;
      $q=$db->prepare('UPDATE visitor_day SET full_count=MAX(0,full_count-?),partial_count=MAX(0,partial_count-?),chars=MAX(0,chars-?) WHERE visitor=? AND day=?');
      $q->execute([$action==='full'?1:0,$action==='partial'?1:0,(int)$r['chars'],$r['visitor'],$r['admitted_day']]);
      $db->exec("DELETE FROM failure_grace WHERE day < date('now','-45 days')");
    }catch(Throwable $error){error_log('Motricialy TTS grace '.get_class($error));}
}
PHPSOURCE;
$s=one214($src,'function tts_settle(PDO $db,string $id,?float $actual,bool $completed):void {',$helper."\n".'function tts_settle(PDO $db,string $id,?float $actual,bool $completed):void {');
$s=one214($s,
    '$q=$db->prepare(\'INSERT INTO daily_charges(day,charged) VALUES(?,?) ON CONFLICT(day) DO UPDATE SET charged=charged+excluded.charged\');$q->execute([$r[\'admitted_day\'],$charged]);$db->exec(\'COMMIT\');',
    '$q=$db->prepare(\'INSERT INTO daily_charges(day,charged) VALUES(?,?) ON CONFLICT(day) DO UPDATE SET charged=charged+excluded.charged\');$q->execute([$r[\'admitted_day\'],$charged]);'."\n      ".'if($status===\'estimated\')tts_failed_quota_grace($db,$r);'."\n      ".'$db->exec(\'COMMIT\');'
);
$s=one214($s,'$q->execute([$r[\'admitted_day\'],$r[\'reserved\']]);$count++;','$q->execute([$r[\'admitted_day\'],$r[\'reserved\']]);tts_failed_quota_grace($db,$r);$count++;');
if(hash('sha256',$s)!==$new)v214fail('PATCH_HASH');
if(in_array('--check',$argv,true)){echo "TTS_V214_CHECK_OK QUOTA_GRACE=ONE_PER_VISITOR_DAY MONEY_UNCHANGED\n";exit(0);}
if(in_array('--stage-test',$argv,true)){
 $stage=__DIR__.'/qa/api';
 if(!is_dir($stage)&&!mkdir($stage,0700,true))v214fail('STAGE_DIR');
 if(file_put_contents($stage.'/budget.php',$s,LOCK_EX)!==strlen($s)||!copy($bootstrap,$stage.'/bootstrap.php'))v214fail('STAGE_WRITE');
 echo "TTS_V214_QA_READY\n";exit(0);
}
if(!in_array('--apply',$argv,true)){echo "USAGE --check | --stage-test | --apply\n";exit(1);}
if(!is_file($ledger))v214fail('LEDGER_MISSING');
try{
 $db=new PDO('sqlite:'.$ledger,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>10]);
 $db->exec('PRAGMA query_only=ON');
 $pending=(int)$db->query("SELECT COUNT(*) FROM reservations WHERE status='pending'")->fetchColumn();
 $unpriced=(int)$db->query("SELECT COUNT(*) FROM reservations WHERE status IN ('ambiguous','overrun') AND (charged IS NULL OR charged<=0 OR (status='ambiguous' AND charged+0.00000001<reserved))")->fetchColumn();
 if($pending>0||$unpriced>0)v214fail('ACTIVE_OR_UNPRICED_RESERVATIONS');
 $db=null;
}catch(Throwable $e){v214fail('LEDGER_GATE');}
$stageFile=__DIR__.'/qa/api/budget.php';
if(!is_file($stageFile)||hash_file('sha256',$stageFile)!==$new)v214fail('QA_NOT_PREPARED');
$backup='/home/u342033576/domains/motricialy.com/tts-budget-before-v214-20261121.php';
if(!is_file($backup)&&!copy($target,$backup))v214fail('BACKUP');
@chmod($backup,0600);
$tmp=dirname($target).'/.tts-v214-'.getmypid().'.tmp';
if(file_put_contents($tmp,$s,LOCK_EX)!==strlen($s)||!rename($tmp,$target)||hash_file('sha256',$target)!==$new)v214fail('WRITE');
echo "TTS_V214_DEPLOY_OK ONE_DAILY_FAILED_REQUEST_GRACE=ON NO_FINANCIAL_REFUND=YES CAPS_UNCHANGED=YES BACKUP=YES\n";
