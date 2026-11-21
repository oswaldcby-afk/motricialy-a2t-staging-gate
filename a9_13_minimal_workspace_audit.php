<?php
declare(strict_types=1);
/* Read-only A9.13 live workspace regression: no paid API requests or mutations. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$base='/home/u342033576/domains/motricialy.com/public_html/tools/audio-to-text';
$out=['stage'=>'A9.13','type'=>'minimal_workspace_live_files','checks'=>[],'paid_api_calls'=>0];
try {
 $html=(string)file_get_contents($base.'/index.html');
 $css=(string)file_get_contents($base.'/assets/audio-to-text.css');
 $js=(string)file_get_contents($base.'/assets/audio-to-text.js');
 $within=static function(string $content,string $begin,string $end):string {
   $a=strpos($content,$begin);$b=$a===false?false:strpos($content,$end,$a);
   if($a===false||$b===false)throw new RuntimeException('sections_missing');
   return substr($content,$a,$b-$a);
 };
 $work=$within($html,'<section class="a2t-workspace"','<section class="a2t-seo"');
 $out['checks']['service_badge_removed']=!str_contains($work,'service-badge');
 $out['checks']['dev_step_labels_removed']=!str_contains($work,'STEP 01')&&!str_contains($work,'STEP 02');
 $out['checks']['no_ready_copy']=!str_contains($work,'Ready. Select')&&!str_contains($work,'Checking service');
 $out['checks']['workspace_headings']=str_contains($work,'<h2>Upload audio</h2>')&&str_contains($work,'<h2>Transcript</h2>');
 $out['checks']['upload_title']=str_contains($work,'Choose an audio file');
 $out['checks']['transcription_cta']=str_contains($work,'id="transcribe-label">Transcribe audio');
 $out['checks']['status_initially_hidden']=str_contains($work,'id="status-line" class="status-line" role="status" aria-live="polite" hidden');
 $out['checks']['editor_status_initially_hidden']=str_contains($work,'id="editor-status" class="status-line editor-status" role="status" aria-live="polite" hidden');
 $out['checks']['export_buttons_simplified']=str_contains($work,'id="copy-btn">Copy</button>')&&
   str_contains($work,'id="txt-btn">TXT</button>')&&str_contains($work,'id="docx-btn">DOCX</button>');
 $out['checks']['no_duplicate_editor_labels']=!str_contains($work,'Transcript editor')&&!str_contains($work,'>Editable</span>');
 $out['checks']['javascript_no_old_copy']=!str_contains($js,'Service available')&&!str_contains($js,'Ready. Select')&&
    !str_contains($js,'Checking availability')&&!str_contains($js,'Checking service');
 $out['checks']['javascript_status_hidden_toggle']=str_contains($js,'el.hidden=!s;');
 $out['checks']['api_routes_unchanged']=str_contains($js,"api+'config'")&&str_contains($js,"api+'transcribe'")&&
    str_contains($js,"api+'export/docx'");
 $out['checks']['workspace_only_css_override']=str_contains($css,'Audio to Text · Minimal workspace typography');
 $out['checks']['transcript_font_16px']=str_contains($css,'#a2t-tool .transcript-card #transcript')&&
    str_contains($css,'font-size: 16px;');
 $out['checks']['hidden_feedback_css']=str_contains($css,'.status-line[hidden] { display: none !important; }');
 $out['checks']['landing_how_to_still_present']=str_contains($html,'How to Use Motricialy Audio to Text');
 $out['checks']['landing_feature_count']=(substr_count($html,'<div class="features-grid"><article>')===1);
 $out['checks']['seo_h1_unique']=preg_match_all('/<h1\\b/i',$html)===1;
 $out['checks']['public_php_still_present']=is_file($base.'/api/index.php');
 $out['status']=!in_array(false,$out['checks'],true)?'MINIMAL_WORKSPACE_STATIC_PASS':'MINIMAL_WORKSPACE_STATIC_FAIL';
}catch(Throwable $e){$out['status']='MINIMAL_WORKSPACE_AUDIT_ERROR';$out['reason']=$e->getMessage();}
$out['passed']=count(array_filter($out['checks']));
$out['total']=count($out['checks']);
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']!=='MINIMAL_WORKSPACE_STATIC_PASS')exit(97);
