<?php
declare(strict_types=1);
// Isolated Stage A9 test schema only. Uses temporary cron environment variable, never stores it.
if(PHP_SAPI!=='cli' || basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(9);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(10);
$fd=@fopen(__DIR__.'/.a9-schema-setup-once','x');
if($fd===false){echo "ALREADY_ATTEMPTED\n";exit;}
fwrite($fd,'1');fclose($fd);
$password=getenv('A2T_A9_TEMP_PASSWORD');
if(!is_string($password)||strlen($password)<16){echo "MISSING_TEST_PASSWORD\n";exit(11);}
$sql=[
"CREATE TABLE IF NOT EXISTS a2t_budget_pool (cycle_start DATE NOT NULL,pool_name VARCHAR(20) NOT NULL,spent_micro BIGINT UNSIGNED NOT NULL DEFAULT 0,reserved_micro BIGINT UNSIGNED NOT NULL DEFAULT 0,PRIMARY KEY(cycle_start,pool_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS a2t_daily_quota (day_utc DATE NOT NULL,subject_hash CHAR(64) NOT NULL,audio_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,audio_seconds INT UNSIGNED NOT NULL DEFAULT 0,text_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,PRIMARY KEY(day_utc,subject_hash)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS a2t_jobs (id CHAR(32) NOT NULL,cycle_start DATE NOT NULL,pool_name VARCHAR(20) NOT NULL,kind ENUM('transcribe','clean','summary') NOT NULL,seconds INT UNSIGNED NOT NULL DEFAULT 0,reserved_micro BIGINT UNSIGNED NOT NULL,actual_micro BIGINT UNSIGNED DEFAULT NULL,status ENUM('reserved','settled','uncertain') NOT NULL,created_at DATETIME NOT NULL,settled_at DATETIME DEFAULT NULL,PRIMARY KEY(id),KEY idx_cycle_status(cycle_start,status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];
try{
  $pdo=new PDO('mysql:host=localhost;dbname=u342033576_a2t_stage_a9;charset=utf8mb4','u342033576_a2t_a9_user',$password,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>8
  ]);
  unset($password);
  foreach($sql as $query)$pdo->exec($query);
  $rows=$pdo->query("SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA='u342033576_a2t_stage_a9' AND TABLE_NAME IN ('a2t_budget_pool','a2t_daily_quota','a2t_jobs') ORDER BY TABLE_NAME")->fetchAll();
  if(count($rows)!==3||count(array_filter($rows,fn($r)=>$r['ENGINE']==='InnoDB'))!==3)throw new RuntimeException('schema');
  $pdo->beginTransaction();
  $pdo->exec("INSERT INTO a2t_budget_pool(cycle_start,pool_name,spent_micro,reserved_micro) VALUES('2099-01-01','a9_tmp',100,0)");
  $pdo->exec("UPDATE a2t_budget_pool SET reserved_micro=500 WHERE cycle_start='2099-01-01' AND pool_name='a9_tmp'");
  $pdo->rollBack();
  $n=(int)$pdo->query("SELECT COUNT(*) FROM a2t_budget_pool WHERE cycle_start='2099-01-01' AND pool_name='a9_tmp'")->fetchColumn();
  echo json_encode(['task'=>'a9_isolated_mysql','status'=>'PASS','tables'=>array_column($rows,'TABLE_NAME'),'engine'=>'InnoDB','rollback'=>$n===0?'PASS':'FAIL','openai_calls'=>0]).PHP_EOL;
}catch(Throwable $e){
  echo json_encode(['task'=>'a9_isolated_mysql','status'=>'FAIL','stage'=>'sql_or_transaction','exception_class'=>get_class($e)]).PHP_EOL;
  exit(12);
}
