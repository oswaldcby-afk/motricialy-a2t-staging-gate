<?php
declare(strict_types=1);
/* Read-only audit: Motricialy TTS raw HTML, React bundle and DOM-layer scripts. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$root='/home/u342033576/domains/motricialy.com/public_html/tools/text-to-speech';
$html=@file_get_contents("$root/index.html");
if(!is_string($html)||strlen($html)<30000){echo "AUDIT_FAIL_HTML\n";exit(2);}
preg_match_all('~<script\b[^>]*\bsrc="([^"]+)"~',$html,$sm);
preg_match_all('~<link\b[^>]*\brel="stylesheet"[^>]*\bhref="([^"]+)"~',$html,$cm);
$refs=$sm[1];$styles=$cm[1];$bundleRef='';
foreach($refs as $ref)if(str_contains($ref,'/assets/index-tts-v')){$bundleRef=$ref;break;}
$bundle=$bundleRef?@file_get_contents("$root/assets/".basename(parse_url($bundleRef,PHP_URL_PATH))):false;
if(!is_string($bundle)||strlen($bundle)<200000){echo "AUDIT_FAIL_REACT\n";exit(2);}
function cleanT(string $s):string{return trim(preg_replace('/\s+/u',' ',html_entity_decode(strip_tags($s),ENT_QUOTES|ENT_HTML5,'UTF-8'))??'');}
function arrayT(string $s,string $name):array{
 $needle=$name.'=[[';$p=strpos($s,$needle);
 if($p===false)return [];
 $start=$p+strlen($name)+1;$depth=0;$string=false;$escape=false;$end=-1;
 for($i=$start,$n=strlen($s);$i<$n;$i++){
  $c=$s[$i];
  if($string){if($escape){$escape=false;continue;}if($c==='\\'){$escape=true;continue;}if($c==='"')$string=false;continue;}
  if($c==='"'){$string=true;continue;}
  if($c==='[')$depth++;
  if($c===']'&&--$depth===0){$end=$i+1;break;}
 }
 if($end<0)return [];
 $data=json_decode(substr($s,$start,$end-$start),true);
 return is_array($data)?$data:[];
}
function cardsT(string $html,string $class):array{
 preg_match_all('~<article\b[^>]*\bclass="[^"]*'.preg_quote($class,'~').'[^"]*"[^>]*>(.*?)</article>~s',$html,$m);
 $out=[];foreach($m[1] as $entry)
  if(preg_match('~<h3[^>]*>(.*?)</h3>~s',$entry,$h)&&preg_match('~<p[^>]*>(.*?)</p>~s',$entry,$p))$out[]=[cleanT($h[1]),cleanT($p[1])];
 return $out;
}
preg_match_all('~<details\b[^>]*\bclass="faq"[^>]*>(.*?)</details>~s',$html,$fm);
$rawFaq=[];$links=[];
foreach($fm[1] as $e){
 if(!preg_match('~<summary>\s*<span>(.*?)</span>~s',$e,$q)||!preg_match('~</summary>\s*<p>(.*?)</p>~s',$e,$a))continue;
 $rawFaq[]=[cleanT($q[1]),cleanT($a[1])];
 preg_match_all('~<a\b[^>]*href="([^"]+)"~',$a[1],$lm);$links[]=$lm[1];
}
$jsFaq=arrayT($bundle,'jd');$normFaq=[];
foreach($jsFaq as $a){
 $normFaq[]=[$a[0],cleanT(str_replace(['[[TERMS]]','[[RIGHTS]]'],['Terms of Use','AI Content Usage Rights'],$a[1]))];
}
$rawRelated=[];preg_match_all('~<a\b[^>]*class="[^"]*related-card[^"]*"[^>]*>.*?</a>~s',$html,$rm);
foreach($rm[0] as $a)if(preg_match('~href="([^"]+)"~',$a,$url)&&preg_match('~<span[^>]*>(.*?)</span>~s',$a,$name))
 $rawRelated[]=[cleanT($name[1]),$url[1]];
$baseName=fn($s)=>basename(parse_url($s,PHP_URL_PATH)?:'');
$scriptLoaded=fn($name)=>count(array_filter($refs,fn($r)=>$baseName($r)===$name))===1;
$cssLoaded=fn($name)=>count(array_filter($styles,fn($r)=>$baseName($r)===$name))===1;
$status=@file_get_contents("$root/tts-v216-result-status.js")?:'';
$players=@file_get_contents("$root/tts-v216-players.js")?:'';
$picker=@file_get_contents("$root/tts-v28-voice-picker.js")?:'';
$voices=@file_get_contents("$root/tts-v28-voice-details.js")?:'';
$action=@file_get_contents("$root/tts-v25-layout.js")?:'';
$results=[];
function checkT(string $n,bool $ok){global $results;$results[$n]=$ok;}
checkT('seo_faq_six_and_synced',count($rawFaq)===6&&count($normFaq)===6&&$rawFaq===$normFaq);
checkT('seo_howto_synced',cardsT($html,'how-card')===arrayT($bundle,'xd'));
checkT('seo_features_synced',cardsT($html,'feature-card')===arrayT($bundle,'Zd'));
checkT('seo_related_synced',count($rawRelated)===6&&$rawRelated===arrayT($bundle,'Cd'));
checkT('seo_h1_unique',preg_match_all('~<h1\b~',$html)===1&&str_contains($html,'Text to Speech'));
checkT('seo_schema_and_tracking',str_contains($html,'SoftwareApplication')&&str_contains($html,'BreadcrumbList')&&str_contains($html,'motricialy-tracking.js')&&str_contains($html,'motricialy-site-chrome.js'));
checkT('faq_two_rights_links',count($links)===6&&count(array_intersect(['/terms-of-use/','/ai-content-usage-rights/'],$links[2]??[]))===2&&count(array_intersect(['/terms-of-use/','/ai-content-usage-rights/'],$links[4]??[]))===2);
checkT('faq_no_old_strings',!str_contains($html,'Can I download MP3 and WAV?')&&!str_contains($bundle,'Can I download MP3 and WAV?')&&!str_contains($html,'Are the voice previews free?')&&!str_contains($bundle,'Are the voice previews free?'));
checkT('faq_react_legal_links',str_contains($bundle,'href:"/terms-of-use/"')&&str_contains($bundle,'href:"/ai-content-usage-rights/"'));
checkT('full_2000_section_400',str_contains($bundle,'Ut={full:2e3,partial:400,takes:12}')&&str_contains($bundle,'function v17(')&&str_contains($bundle,'function k4(s,A=400)'));
checkT('player_live', $scriptLoaded('tts-v216-players.js')&&str_contains($players,'audio.currentTime=next')&&str_contains($players,'isPointerDragging')&&str_contains($players,'URL.createObjectURL'));
checkT('generating_result_mounted',str_contains($bundle,'P||Z.length>0||U||Nl||N||k&&!nt'));
checkT('generation_progress_live',$scriptLoaded('tts-v216-result-status.js')&&str_contains($status,'.tts-page section.studio-output.output')&&str_contains($status,'tts-v216-track'));
checkT('ready_text_green',str_contains($status,'tts-v216-ready')&&str_contains($status,'color:#147b52'));
checkT('voice_picker_live',$scriptLoaded('tts-v28-voice-picker.js')&&str_contains($picker,'Browse voices'));
checkT('voice_gender_mapping',str_contains($voices,"sage:{gender:'Female'")&&str_contains($voices,"alloy:{gender:'Male'")&&str_contains($voices,"ballad:{gender:'Male'")&&!str_contains($picker,'Neutral'));
checkT('voice_details_live',$scriptLoaded('tts-v28-voice-details.js'));
checkT('inline_generate_bridge',$scriptLoaded('tts-v25-layout.js')&&str_contains($action,'original.click()')&&str_contains($action,'tts-v25-generate'));
checkT('editor_fill_css_live',$cssLoaded('tts-v219-editor-layout.css'));
checkT('header_generate_css_live',$cssLoaded('tts-v220-generate-cta.css'));
checkT('no_deprecated_player_script',$scriptLoaded('tts-v216-players.js')&&!$scriptLoaded('tts-v211-seekable-player.js')&&!$scriptLoaded('tts-v215-seekable-player.js'));
checkT('no_deprecated_status_script',$scriptLoaded('tts-v216-result-status.js')&&!$scriptLoaded('tts-v210-result-status.js'));
$failed=array_keys(array_filter($results,fn($v)=>!$v));$out=['audit'=>'read-only-production','html_bytes'=>strlen($html),'react_bytes'=>strlen($bundle),'react_src'=>$bundleRef,'html_faq_titles'=>array_column($rawFaq,0),'react_faq_titles'=>array_column($normFaq,0),'counts'=>['faqs'=>count($rawFaq),'howto'=>count(cardsT($html,'how-card')),'features'=>count(cardsT($html,'feature-card')),'related'=>count($rawRelated)],'checks'=>$results,'failed'=>$failed,'scripts'=>$refs,'styles'=>$styles];
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if($failed)exit(3); // Fail future releases if raw HTML and React diverge.

