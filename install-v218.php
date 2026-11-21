<?php
declare(strict_types=1);
/* TTS v2.18 production hotfix: only validator and Realtime fidelity check.
   --check is read-only. No API credentials, budgets or media are modified. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit(1);}
$root='/home/u342033576/domains/motricialy.com';
$api=$root.'/public_html/tools/text-to-speech/api';
$validatorPath=$api.'/validator.php';$realtimePath=$api.'/realtime.php';
$oldV='67fbae8a5997842631ab945eb2eeb2e1274d641d8e72e662358f45e55a839963';
$oldR='2fdf2844f3bf77da6daac892bb7b6edaa8979f33bbf23e7aadb81eff29d648fd';
$newV='b937e0f2cc919dc98796d7a0b54d30b4e8c204e1986f4fc7a465d913335b4377';
$newR='f36e82db3d58beeb9739d3af97816fd8148e180aea79965116b6f65d6a8f01d3';
function fail218(string $reason):never{echo "TTS_V218_FAIL_".$reason."\n";exit(20);}
function patch218(string $file,string $old,string $new,string $name):string {
 if(substr_count($file,$old)!==1)fail218('PATTERN_'.$name);
 return str_replace($old,$new,$file);
}
$oldValidator=@file_get_contents($validatorPath);
$oldRealtime=@file_get_contents($realtimePath);
if(!is_string($oldValidator)||!is_string($oldRealtime))fail218('SOURCE_READ');
if(hash('sha256',$oldValidator)!==$oldV||hash('sha256',$oldRealtime)!==$oldR)fail218('SOURCE_HASH');
$validatorFunctions=<<<'PHPVAL'
/** Split a visible Section into shorter model calls without changing edit length. */
function tts_speech_units(string $speech,int $limit):array {
    $chars=preg_split('//u',trim($speech),-1,PREG_SPLIT_NO_EMPTY);
    if(!is_array($chars)||!$chars)throw new InvalidArgumentException('Speech text is empty');
    $out=[];$offset=0;$total=count($chars);
    while($offset<$total){
        $remaining=$total-$offset;
        if($remaining<=$limit){$out[]=trim(implode('',array_slice($chars,$offset)));break;}
        $cap=min($limit,$remaining);$sentence=0;$clause=0;$space=0;
        for($i=1;$i<=$cap;$i++){
            $last=$chars[$offset+$i-1];$next=$chars[$offset+$i]??'';
            // Honor complete sentences even when the boundary is early.
            if(preg_match('/[.!?。！？]/u',$last)&&($next===''||preg_match('/[\s\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u',$next)))$sentence=$i;
            if(preg_match('/[,，、;；:：]/u',$last)&&($next===''||preg_match('/[\s\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u',$next)))$clause=$i;
            if(preg_match('/\s/u',$last))$space=$i;
        }
        if($sentence>0)$take=$sentence;
        elseif($clause>=(int)($limit*.35))$take=$clause;
        elseif($space>=(int)($limit*.5))$take=$space;
        else $take=$cap;
        $piece=trim(implode('',array_slice($chars,$offset,$take)));
        if($piece==='')throw new InvalidArgumentException('Unable to split speech text');
        $out[]=$piece;$offset+=$take;
        while($offset<$total&&preg_match('/\s/u',$chars[$offset]))$offset++;
        if(count($out)>24)throw new InvalidArgumentException('Too many speech pieces');
    }
    return $out;
}
/** Build provider calls at ~180 characters, relaxing only for long scripts. */
function tts_plan_speech_units(array $segments):array {
    foreach([180,200,220,240,280,320,360,400,480] as $limit){
        $planned=$segments;$count=0;
        foreach($planned as &$segment){
            $source=$segment['rendered'];$language=$segment['language'];
            $max=$language==='zh'||$language==='ja'||$language==='ko'?min(220,$limit):$limit;
            $sentences=$segment['sentencePause']>0?(preg_split('/(?<=[.!?。！？])\s+/u',$source,-1,PREG_SPLIT_NO_EMPTY)?:[$source]):[$source];
            $units=[];
            foreach($sentences as $sentenceIndex=>$sentence){
                $pieces=tts_speech_units($sentence,$max);
                foreach($pieces as $pieceIndex=>$piece){
                    $units[]=['text'=>$piece,'pauseBefore'=>($sentenceIndex>0&&$pieceIndex===0)?$segment['sentencePause']:0];
                }
            }
            $segment['units']=$units;$count+=count($units);
        }
        unset($segment);
        if($count<=12)return ['segments'=>$planned,'chunks'=>$count];
    }
    throw new InvalidArgumentException('Too many speech chunks for this script');
}
PHPVAL;
$validator=patch218($oldValidator,'function tts_segments(array $data):array {',$validatorFunctions."\n".'function tts_segments(array $data):array {','FUNCTIONS');
$validator=patch218($validator,<<<'OLD'
      $units=[];
      if($pause>0){$sentences=preg_split('/(?<=[.!?。！？])\s+/u',$spoken,-1,PREG_SPLIT_NO_EMPTY)?:[$spoken];
          foreach($sentences as $v){$v=trim($v);if($v!=='')$units[]=['text'=>$v,'pauseBefore'=>count($units)>0?$pause:0];}}
      else $units[]=['text'=>$spoken,'pauseBefore'=>0];
      if(count($units)>8)throw new InvalidArgumentException('Too many sentence splits');
      foreach($units as $u)if(tts_length($u['text'])>$maxSpoken)throw new InvalidArgumentException('Speech chunk too long');
      $chunks+=count($units);$chars+=$len;
OLD,<<<'NEW'
      $units=[];
      $chars+=$len;
NEW,'SEGMENTS');
$validator=patch218($validator,<<<'OLD'
    if($chars>TTS_LIMITS[$action])throw new InvalidArgumentException('Script exceeds free limit');
    if($chunks>12)throw new InvalidArgumentException('Too many speech chunks');
    return ['segments'=>$out,'action'=>$action,'requestId'=>$requestId,'chars'=>$chars,'chunks'=>$chunks];
OLD,<<<'NEW'
    if($chars>TTS_LIMITS[$action])throw new InvalidArgumentException('Script exceeds free limit');
    $planned=tts_plan_speech_units($out);
    return ['segments'=>$planned['segments'],'action'=>$action,'requestId'=>$requestId,'chars'=>$chars,'chunks'=>$planned['chunks']];
NEW,'REPACK');
$tailFunctions=<<<'PHPTAIL'
/** Inspect real PCM (not provider self-transcript) for a truncated silent ending. */
function tts_pcm_tail_silence_seconds(string $pcm):float {
    $samples=intdiv(strlen($pcm),2);
    if($samples<24000*4)return 0.0;
    $block=12000;$silent=0;
    for($end=$samples;$end>0;$end-=$block){
        $start=max(0,$end-$block);$audible=false;
        for($i=$start;$i<$end;$i+=48){
            $u=unpack('v',substr($pcm,$i*2,2))[1];
            $amplitude=$u>32767?65536-$u:$u;
            if($amplitude>550){$audible=true;break;}
        }
        if($audible)break;
        $silent+=($end-$start)/24000;
    }
    return $silent;
}
function tts_pcm_has_truncated_tail(string $pcm):bool {
    $duration=strlen($pcm)/48000;
    $tail=tts_pcm_tail_silence_seconds($pcm);
    return $tail>=3.5 && $tail/max(1.0,$duration)>=.13;
}
PHPTAIL;
$realtime=patch218($oldRealtime,'function tts_synthesize(array $settings,string $text,array $cfg):array {',$tailFunctions."\n".'function tts_synthesize(array $settings,string $text,array $cfg):array {','TAIL');
$realtime=patch218($realtime,"'verified'=>tts_fidelity_matches(\$text,\$spokenTranscript)","'verified'=>tts_fidelity_matches(\$text,\$spokenTranscript)&&!tts_pcm_has_truncated_tail(\$pcm)",'FIDELITY');
if(hash('sha256',$validator)!==$newV||hash('sha256',$realtime)!==$newR)fail218('NEW_HASH');
if(in_array('--check',$argv,true)){echo "TTS_V218_CHECK_OK FIVE_SHORT_UNITS_LAST_SENTENCE_SEPARATE AUDIO_TAIL_GUARD=ON\n";exit(0);}
if(!in_array('--apply',$argv,true)){echo "USAGE --check | --apply\n";exit(1);}
$ledger=$root.'/motricialy-tts-private/ledger.sqlite';
if(!is_file($ledger))fail218('LEDGER_MISSING');
try{
 $db=new PDO('sqlite:'.$ledger,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>10]);
 $db->exec('PRAGMA query_only=ON');
 if((int)$db->query("SELECT COUNT(*) FROM reservations WHERE status='pending'")->fetchColumn()>0)fail218('GENERATIONS_ACTIVE');
 $db=null;
}catch(Throwable $e){fail218('LEDGER_CHECK');}
$backup=$root.'/tts-v218-backup-20261121';
if(!is_dir($backup)&&!mkdir($backup,0700,true))fail218('BACKUP_DIRECTORY');
foreach(['validator.php'=>$oldValidator,'realtime.php'=>$oldRealtime] as $file=>$data){
 $dst=$backup.'/'.$file;
 if(!is_file($dst)&&file_put_contents($dst,$data,LOCK_EX)!==strlen($data))fail218('BACKUP_'.$file);
}
function store218(string $file,string $data):void{
 $tmp=dirname($file).'/.tts-v218-'.basename($file).'-'.getmypid().'.tmp';
 if(file_put_contents($tmp,$data,LOCK_EX)!==strlen($data)||!rename($tmp,$file)||hash_file('sha256',$file)!==hash('sha256',$data))fail218('WRITE_'.basename($file));
}
store218($validatorPath,$validator);
store218($realtimePath,$realtime);
echo "TTS_V218_DEPLOY_OK SHORT_PROVIDER_CHUNKS=ON SILENT_TAIL_REJECT=ON EDIT_LIMIT_400=UNCHANGED BUDGET_CAPS=UNCHANGED BACKUP=YES\n";
