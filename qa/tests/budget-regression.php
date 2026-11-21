<?php
declare(strict_types=1);
/* Isolated SQLite regression tests: never reads or modifies production ledger. */
if(!extension_loaded('pdo_sqlite')){echo "NO_SQLITE\n";exit(2);}
$base=sys_get_temp_dir().'/tts-v213-'.bin2hex(random_bytes(8));
@mkdir($base.'/public_html',0700,true);
$_SERVER['DOCUMENT_ROOT']=$base.'/public_html';
require_once __DIR__.'/../api/budget.php';
$db=tts_db();$n=0;
function assure(bool $v,string $msg):void{global $n;$n++;if(!$v)throw new RuntimeException($msg);}
function req213(int $i):array{return ['action'=>'full','requestId'=>'v213_test_'.$i.'_x','segments'=>[['rendered'=>'Short test']]];}
$cfg=['cycle_start'=>gmdate('Y-m-01'),'cycle_end'=>gmdate('Y-m-01',strtotime('+1 month')),'phase'=>'operating','provider_floor'=>0.0];
$a=str_repeat('a',32);$b=str_repeat('b',32);
try {
  $r=tts_reserve($db,$cfg,$a,req213(1),10,1);
  assure($r['reserved']>.055,'reserve');
  tts_settle($db,$r['id'],.085,true); // known overrun, previously threw error
  $s=tts_budget_status($db,$a)['budget'];
  assure($s['unresolved']===0,'known_overrun_not_unresolved');
  assure($s['accountedOverruns']===1,'overrun_auditable');
  assure(abs($s['dailySpentUsd']-.085)<.0000001,'actual_price_fully_charged');
  $r2=tts_reserve($db,$cfg,$b,req213(2),10,1);
  tts_settle($db,$r2['id'],.002,true);
  assure(tts_budget_status($db,$b)['budget']['unresolved']===0,'other_visitor_not_blocked');
  $r3=tts_reserve($db,$cfg,$a,req213(3),10,1);
  tts_settle($db,$r3['id'],null,false);
  $db->prepare("UPDATE reservations SET status='ambiguous' WHERE id=?")->execute([$r3['id']]);
  assure(tts_budget_status($db,$a)['budget']['unresolved']===0,'legacy_ambiguous_fully_charged');
  $r4=tts_reserve($db,$cfg,$b,req213(4),10,1);
  tts_settle($db,$r4['id'],.002,true);
  $db->prepare('UPDATE reservations SET charged=0 WHERE id=?')->execute([$r3['id']]);
  assure(tts_budget_status($db,$a)['budget']['unresolved']===1,'unpriced_detected');
  try{tts_reserve($db,$cfg,$b,req213(5),10,1);throw new Exception('unpriced_was_admitted');}
  catch(RuntimeException $e){assure(str_contains($e->getMessage(),'Unaccounted'),'unpriced_still_blocked');}
  $db->prepare('UPDATE reservations SET charged=reserved WHERE id=?')->execute([$r3['id']]);
  $r5=tts_reserve($db,$cfg,$a,req213(6),10,1);
  tts_settle($db,$r5['id'],2.91,true);
  try{tts_reserve($db,$cfg,$b,req213(7),10,1);throw new Exception('over_cap_admitted');}
  catch(DomainException $e){assure(str_contains($e->getMessage(),'Daily TTS budget cap'),'daily_hard_cap');}
  echo json_encode(['PASS'=>$n,'isolated'=>true,'known_overrun'=>'charged','unpriced'=>'blocked','daily_cap'=>'enforced'])."\n";
}catch(Throwable $e){echo "TTS_V213_QA_FAIL ".$e->getMessage()."\n";exit(1);}
