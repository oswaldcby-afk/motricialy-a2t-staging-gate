<?php
declare(strict_types=1);
/* Only a temporary isolated SQLite database, never production. */
if(!extension_loaded('pdo_sqlite')){echo "FAIL_SQLITE_MISSING\n";exit(2);}
$testRoot=sys_get_temp_dir().'/tts-v214-grace-test-'.bin2hex(random_bytes(8));
mkdir($testRoot.'/public_html',0700,true);
$_SERVER['DOCUMENT_ROOT']=$testRoot.'/public_html';
require_once __DIR__.'/../api/budget.php';
$db=tts_db();
$cfg=['cycle_start'=>gmdate('Y-m-01'),'cycle_end'=>gmdate('Y-m-01',strtotime('+1 month')),'phase'=>'operating','provider_floor'=>0.0];
$owner=str_repeat('a',32);$other=str_repeat('b',32);$third=str_repeat('c',32);
$n=0;
function ck(bool $x,string $reason):void {global $n;$n++;if(!$x)throw new RuntimeException($reason);}
function reserveTest(PDO $db,array $cfg,string $owner,int $num,string $action='full'):array{
 return tts_reserve($db,$cfg,$owner,['action'=>$action,'requestId'=>'grace-v214-'.$num,'segments'=>[['rendered'=>'Hello sample']]],18,1);
}
try {
 $r=reserveTest($db,$cfg,$owner,1);
 ck(tts_budget_status($db,$owner)['usage']['full']===1,'initial quota booked');
 tts_settle($db,$r['id'],null,false);
 $stat=tts_budget_status($db,$owner);
 ck($stat['usage']['full']===0&&$stat['usage']['chars']===0,'first failed request allowance restored');
 ck(abs($stat['budget']['dailySpentUsd']-$r['reserved'])<0.0000001,'first failure full reserved cost remains');
 ck((int)$db->query('SELECT COUNT(*) FROM failure_grace')->fetchColumn()===1,'first grace recorded once');
 $r2=reserveTest($db,$cfg,$owner,2);tts_settle($db,$r2['id'],null,false);
 ck(tts_budget_status($db,$owner)['usage']['full']===1,'second failure consumes daily allowance');
 ck((int)$db->query('SELECT COUNT(*) FROM failure_grace')->fetchColumn()===1,'same visitor not credited twice');
 $r3=reserveTest($db,$cfg,$owner,3,'partial');tts_settle($db,$r3['id'],null,false);
 ck(tts_budget_status($db,$owner)['usage']['partial']===1,'same visitor partial failure not credited again');
 $r4=reserveTest($db,$cfg,$other,4);tts_settle($db,$r4['id'],null,false);
 ck(tts_budget_status($db,$other)['usage']['full']===0,'different visitor gets own first grace');
 $r5=reserveTest($db,$cfg,$third,5);
 $db->prepare('UPDATE reservations SET updated_at=? WHERE id=?')->execute([time()-700,$r5['id']]);
 ck(tts_recover($db)>=1,'stale pending request recovered');
 ck(tts_budget_status($db,$third)['usage']['full']===0,'stale worker failure quota restored');
 ck(tts_budget_status($db,$third)['budget']['unresolved']===0,'stale request remains accounted');
 $r6=reserveTest($db,$cfg,$other,6);tts_settle($db,$r6['id'],2.8,true);
 try{reserveTest($db,$cfg,$owner,7);throw new RuntimeException('daily cap bypassed');}
 catch(DomainException $e){ck(str_contains($e->getMessage(),'Daily TTS budget cap'),'daily budget cap still enforced');}
 ck((int)$db->query('SELECT COUNT(*) FROM failure_grace')->fetchColumn()===3,'one entry per visitor');
 echo json_encode(['pass'=>$n,'isolated'=>true,'cases'=>['one_grace','second_not_free','partial_shared','stale_recovery','cost_accounted','daily_cap']])."\n";
}catch(Throwable $e){echo "TTS_V214_TEST_FAIL ".$e->getMessage()."\n";exit(1);}
