<?php
declare(strict_types=1);
/* A9.7 read-only verification. CLI only, no mutating SQL, no paid calls, never print credentials. */
if (PHP_SAPI !== 'cli' || basename(__DIR__) !== 'audio-to-text-a9-private-gate-20261120') exit(90);
if (strpos((string)@file_get_contents(__DIR__.'/.htaccess'), 'Require all denied') === false) exit(91);
if (getenv('A2T_ENABLE_LIVE') === '1') exit(92);
$root = '/home/u342033576/.motricialy-a2t-stage-a9';
$cfg = $root.'/mysql-a9.json';
$ledger = $root.'/app-a9-7-full/private-server/ledger.php';
$out = [
 'stage'=>'A9.7',
 'probe'=>'readonly_state_verification',
 'paid_api_invoked'=>false,
 'ledger_expected_sha256'=>'90515ca4e986e2eb48a8d936b7d3b8385a13ccf9b14d360f99b05717e37d0185'
];
try {
 if (!is_file($cfg) || is_link($cfg) || (fileperms($cfg)&0777)!==0600) throw new RuntimeException('config_absent_or_insecure');
 if (!is_file($ledger) || is_link($ledger) || (fileperms($ledger)&0777)!==0600) throw new RuntimeException('ledger_absent_or_insecure');
 $out['ledger_hash_matches'] = hash_equals($out['ledger_expected_sha256'], (string)hash_file('sha256',$ledger));
 $c = json_decode((string)file_get_contents($cfg),true,512,JSON_THROW_ON_ERROR);
 if (!is_array($c) ||
    ($c['environment']??'')!=='isolated-a9-test' ||
    ($c['paid_enabled']??null)!==false ||
    ($c['dsn']??'')!=='mysql:host=localhost;dbname=u342033576_a2t_stage_a9;charset=utf8mb4' ||
    ($c['user']??'')!=='u342033576_a2t_a9_user') throw new RuntimeException('isolated_db_identity_mismatch');
 $pdo = new PDO($c['dsn'],$c['user'],$c['password'],[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_EMULATE_PREPARES=>false,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  PDO::ATTR_TIMEOUT=>6
 ]);
 unset($c);
 $db=(string)$pdo->query('SELECT DATABASE()')->fetchColumn();
 if ($db!=='u342033576_a2t_stage_a9') throw new RuntimeException('connected_to_wrong_db');
 $out['isolated_database_connected']=true;
 $counts=[];
 foreach (['a2t_budget_pool','a2t_daily_quota','a2t_jobs'] as $t) {
  $counts[$t]=(int)$pdo->query('SELECT COUNT(*) FROM '.$t)->fetchColumn();
 }
 $out['table_row_counts']=$counts;
 $out['all_test_rows_removed'] = array_sum($counts)===0;
 $marks=[
   'ledger'=>'a9-7-ledger-qa.done',
   'cli_router'=>'a9-7-cli-router-qa.done',
   'loopback_http'=>'a9-7-http-qa.done'
 ];
 foreach ($marks as $label=>$name) {
   $f=$root.'/'.$name;
   $out['completed_marker_'.$label] = is_file($f) && !is_link($f) &&
      (fileperms($f)&0777)===0600 && trim((string)file_get_contents($f))==='done';
 }
 $out['status']= $out['ledger_hash_matches'] && $out['all_test_rows_removed'] && $out['completed_marker_ledger']
   ? 'LEDGER_STATE_VERIFIED' : 'INCOMPLETE';
} catch (Throwable $e) {
 $out['status']='CHECK_FAILED';
 $out['failure_type']=$e instanceof PDOException?'DB_CHECK_FAILED':$e->getMessage();
}
unset($out['ledger_expected_sha256']);
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
