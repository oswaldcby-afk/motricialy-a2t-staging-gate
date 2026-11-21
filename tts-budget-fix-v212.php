<?php
declare(strict_types=1);
/* TTS v2.12: account for unknown provider usage at full reserved cost;
   do not globally freeze the site for a single failed or timed-out request.
   CLI only, read-only --check before --apply. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$root='/home/u342033576/domains/motricialy.com';
$budget=$root.'/public_html/tools/text-to-speech/api/budget.php';
$ledger=$root.'/motricialy-tts-private/ledger.sqlite';
$before='5fd37ac79a95fc73ba650b28ac3baf6631879645fb6d9c9e24cd22f73f877c81';
$after='d79b15e15c3211b303c2ca68b8d3d6d577f6dc9cb20b4eb425840d87e16670ee';
function failFix(string $why):never{echo "TTS_BUDGET_FIX_FAIL_".$why."\n";exit(20);}
function swapFix(string $source,string $old,string $new,int $count=1):string{
 if(substr_count($source,$old)!==$count)failFix('SOURCE_PATTERN');
 return str_replace($old,$new,$source);
}
try{
 if(!is_file($budget)||!is_file($ledger))failFix('MISSING_INPUT');
 $old=file_get_contents($budget);
 if(!is_string($old))failFix('UNREADABLE_BUDGET');
 $hash=hash('sha256',$old);
 if(!in_array($hash,[$before,$after],true))failFix('BASELINE_HASH');
 $patched=$old;
 if($hash===$before){
  $patched=swapFix($patched,<<<'OLD'
    $spent=(float)$db->query('SELECT COALESCE(SUM(charged),0) FROM reservations WHERE status IN (\'completed\',\'ambiguous\',\'overrun\')')->fetchColumn();
OLD,<<<'NEW'
    $spent=(float)$db->query('SELECT COALESCE(SUM(charged),0) FROM reservations WHERE status IN (\'completed\',\'ambiguous\',\'overrun\',\'estimated\')')->fetchColumn();
NEW);
  $patched=swapFix($patched,"status IN ('completed','ambiguous','overrun')","status IN ('completed','ambiguous','overrun','estimated')",2);
  $patched=swapFix($patched,<<<'OLD'
      $status=(!$completed||$actual===null)?'ambiguous':($overrun?'overrun':'completed');
OLD,<<<'NEW'
      // When usage is unknown, conservatively charge the entire reserved maximum
      // as an ESTIMATE. A failed provider request must not globally disable TTS.
      $status=(!$completed||$actual===null)?'estimated':($overrun?'overrun':'completed');
NEW);
  $patched=swapFix($patched,"$"."charged=$"."status==='ambiguous'?(float)$"."r['reserved']:(float)$"."actual;","$"."charged=$"."status==='estimated'?(float)$"."r['reserved']:(float)$"."actual;");
  $patched=swapFix($patched,<<<'OLD'
      // First request following worker failure holds every uncertain reservation as spent.
OLD,<<<'NEW'
      // A stale worker request is conservatively charged at full reserve.
      // It must not block unrelated users indefinitely.
NEW);
  $patched=swapFix($patched,"UPDATE reservations SET status='ambiguous',charged=reserved,updated_at=?","UPDATE reservations SET status='estimated',charged=reserved,updated_at=?");
 }
 if(hash('sha256',$patched)!==$after)failFix('PATCH_CHECKSUM');
 $db=new PDO('sqlite:'.$ledger,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>10]);
 $db->exec('PRAGMA busy_timeout=10000');
 $db->exec('PRAGMA query_only=ON');
 $stats=$db->query("SELECT status,COUNT(*) AS n,ROUND(SUM(reserved),6) AS reserved,ROUND(SUM(charged),6) AS charged FROM reservations WHERE status IN ('ambiguous','overrun','pending') GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
 $ambiguous=$db->query("SELECT id,reserved,charged,updated_at FROM reservations WHERE status='ambiguous' ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);
 $overruns=(int)$db->query("SELECT COUNT(*) FROM reservations WHERE status='overrun'")->fetchColumn();
 $pending=(int)$db->query("SELECT COUNT(*) FROM reservations WHERE status='pending'")->fetchColumn();
 if(in_array('--check',$argv,true)){
    echo "TTS_BUDGET_FIX_CHECK_OK CODE_HASH=".$hash." UNRESOLVED=".json_encode($stats,JSON_UNESCAPED_SLASHES)."\n";
    exit(0);
 }
 if(!in_array('--apply',$argv,true)){echo "USAGE --check or --apply\n";exit(1);}
 if($overruns!==0||$pending!==0||count($ambiguous)!==1)failFix('UNSAFE_LEDGER_STATE');
 $entry=$ambiguous[0];
 if(abs((float)$entry['charged']-(float)$entry['reserved'])>0.000001||(float)$entry['reserved']<=0)failFix('CHARGE_MISMATCH');
 if(time()-(int)$entry['updated_at']<120)failFix('REQUEST_NOT_STALE');
 // Backup the actual ledger in a consistent SQLite snapshot, not a WAL-unsafe file copy.
 $db=null;
 $db=new PDO('sqlite:'.$ledger,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>10]);
 $db->exec('PRAGMA busy_timeout=10000');
 $snapshot=$root.'/motricialy-tts-private/ledger-pre-v212-20261121.sqlite';
 if(!is_file($snapshot)){
    $quoted=$db->quote($snapshot);
    $db->exec('VACUUM INTO '.$quoted);
    @chmod($snapshot,0600);
 }
 $saved=$root.'/tts-budget-pre-v212-20261121.php';
 if(!is_file($saved)&&!copy($budget,$saved))failFix('CODE_BACKUP');
 @chmod($saved,0600);
 if($hash===$before){
    $tmp=dirname($budget).'/.tts-budget-v212-'.getmypid().'.tmp';
    if(file_put_contents($tmp,$patched,LOCK_EX)!==strlen($patched)||!rename($tmp,$budget)||hash_file('sha256',$budget)!==$after)failFix('WRITE_BUDGET');
 }
 $db->exec('BEGIN IMMEDIATE');
 try{
    $stmt=$db->prepare("UPDATE reservations SET status='estimated',updated_at=? WHERE id=? AND status='ambiguous' AND ABS(charged-reserved)<0.000001");
    $stmt->execute([time(),$entry['id']]);
    if($stmt->rowCount()!==1)throw new RuntimeException('RECONCILE_CHANGED');
    $audit=json_encode(['method'=>'conservative_full_reserve','count'=>1,'chargeUsd'=>(float)$entry['charged'],'appliedAtUtc'=>gmdate('c')],JSON_THROW_ON_ERROR);
    $stmt=$db->prepare('INSERT OR IGNORE INTO meta(name,value) VALUES(?,?)');
    $stmt->execute(['tts_v212_reconciliation_audit',$audit]);
    $db->exec('COMMIT');
 }catch(Throwable $e){try{$db->exec('ROLLBACK');}catch(Throwable $ignored){}throw $e;}
 echo "TTS_V212_BUDGET_RESTORED CONSERVATIVE_ESTIMATE_USD=".round((float)$entry['charged'],6)." BACKUPS=YES DAILY_SPEND_UNCHANGED=YES\n";
}catch(Throwable $e){failFix('EXCEPTION_'.get_class($e));}
