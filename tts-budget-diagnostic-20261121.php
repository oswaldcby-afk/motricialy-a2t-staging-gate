<?php
/* Read-only, no PII or content in output; intended for CLI on production host. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$path='/home/u342033576/domains/motricialy.com/motricialy-tts-private/ledger.sqlite';
if(!is_file($path)){echo "LEDGER_NOT_FOUND\n";exit(2);}
try {
 $db=new PDO('sqlite:'.$path);
 $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
 $db->exec('PRAGMA query_only=ON');
 $st=$db->query('SELECT status, COUNT(*) AS n, ROUND(SUM(reserved),6) AS reserved, ROUND(SUM(charged),6) AS charged FROM reservations GROUP BY status ORDER BY status');
 echo 'STATUS '.json_encode($st->fetchAll(PDO::FETCH_ASSOC),JSON_UNESCAPED_SLASHES)."\n";
 $st=$db->query("SELECT status,action,phase,chars,ROUND(reserved,6) AS reserve,ROUND(charged,6) AS charge,datetime(created_at,'unixepoch') AS created_utc,datetime(updated_at,'unixepoch') AS updated_utc, CAST(strftime('%s','now') AS INTEGER)-updated_at AS seconds_old FROM reservations WHERE status IN ('ambiguous','overrun','pending') ORDER BY created_at DESC LIMIT 12");
 echo 'UNRESOLVED '.json_encode($st->fetchAll(PDO::FETCH_ASSOC),JSON_UNESCAPED_SLASHES)."\n";
 $st=$db->query('SELECT COUNT(*) FROM daily_charges');echo 'DAILY_ROWS '.$st->fetchColumn()."\n";
}catch(Throwable $e){echo "DIAGNOSTIC_FAIL ".get_class($e)."\n";exit(3);}
