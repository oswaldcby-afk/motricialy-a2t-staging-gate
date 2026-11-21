<?php
declare(strict_types=1);
/**
 * Motricialy legal publication 2026-11-22.
 * --check: inspect WordPress pages and content only; --apply: back up and publish.
 * Must run via Hostinger authenticated hosting CLI, not via HTTP.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit(1); }
$webRoot='/home/u342033576/domains/motricialy.com/public_html';
$privateRoot='/home/u342033576/domains/motricialy.com/motricialy-legal-backups';
$addendumPath=__DIR__.'/terms-ai-addendum.html';
$guidePath=__DIR__.'/ai-content-usage-rights.html';
function stopLegal(string $why):never { echo "LEGAL_PUBLISH_FAIL_".$why."\n";exit(21); }
foreach([$addendumPath,$guidePath,$webRoot.'/wp-load.php'] as $needed){if(!is_file($needed))stopLegal('MISSING_FILE');}
$addendum=file_get_contents($addendumPath);
$guide=file_get_contents($guidePath);
if (!is_string($addendum)||!is_string($guide)||strlen($addendum)<2400||strlen($guide)<6500)stopLegal('SOURCE_LENGTH');
if(substr_count($addendum,'AI-Generated Output and Usage Rights')!==1||!str_contains($guide,'Special rules for Text to Speech')||!str_contains($guide,'/terms-of-use/'))stopLegal('SOURCE_CONTENT');
$_SERVER['HTTP_HOST']='motricialy.com';
$_SERVER['SERVER_NAME']='motricialy.com';
$_SERVER['HTTPS']='on';
$_SERVER['SERVER_PORT']='443';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['SCRIPT_NAME']='/index.php';
chdir($webRoot);
define('WP_USE_THEMES',false);
require_once $webRoot.'/wp-load.php';
if(!function_exists('wp_insert_post')||!function_exists('wp_update_post'))stopLegal('WP_NOT_LOADED');
$terms=get_page_by_path('terms-of-use',OBJECT,'page');
if(!$terms||$terms->ID!==1059||$terms->post_status!=='publish'||$terms->post_type!=='page')stopLegal('TERMS_ID_STATUS');
$original=(string)$terms->post_content;
if(strlen($original)<5000||!str_contains($original,'Your Files and Content')||!str_contains($original,'Keep a Backup of Important Files')||!str_contains($original,'September 18, 2026'))stopLegal('TERMS_BASELINE');
$existing=get_page_by_path('ai-content-usage-rights',OBJECT,'page');
if($existing&&($existing->post_title!=='AI Content Usage Rights'||$existing->post_type!=='page'))stopLegal('SLUG_CONFLICT');
$already=str_contains($original,'AI-Generated Output and Usage Rights');
if(!$already){
    if(substr_count($original,'Last updated: September 18, 2026')!==1)stopLegal('UPDATED_DATE_PATTERN');
    // Insert an entire valid Gutenberg block section immediately before the next H2.
    if(!preg_match_all('~<h2\b[^>]*>.*?</h2>~is',$original,$headings,PREG_OFFSET_CAPTURE))stopLegal('NO_H2');
    $insertAt=null;
    foreach($headings[0] as $match){
        if(trim(wp_strip_all_tags($match[0]))==='Keep a Backup of Important Files'){
            $insertAt=$match[1];break;
        }
    }
    if($insertAt===null)stopLegal('HEADING_NOT_FOUND');
    // If the original page uses Gutenberg heading comments, insert before its opening block marker.
    $prior=substr($original,0,$insertAt);
    $blockStart=strrpos($prior,'<!-- wp:heading');
    if($blockStart!==false&&$insertAt-$blockStart<350&&!str_contains(substr($prior,$blockStart),'<!-- /wp:heading'))$insertAt=$blockStart;
    $changed=substr($original,0,$insertAt)."\n\n".$addendum."\n\n".substr($original,$insertAt);
    $changed=str_replace('Last updated: September 18, 2026','Last updated: November 22, 2026',$changed);
    if(substr_count($changed,'AI-Generated Output and Usage Rights')!==1||!str_contains($changed,'/ai-content-usage-rights/'))stopLegal('INJECTION_SANITY');
}else{
    $changed=$original;
    if(!str_contains($changed,'/ai-content-usage-rights/'))stopLegal('PARTIAL_TERMS_UPDATE');
}
if(in_array('--check',$argv,true)){
    echo "LEGAL_CHECK_OK TERMS_ID=".$terms->ID." TERMS_BYTES=".strlen($original)." EXISTING_GUIDE=".($existing?$existing->ID:0)." ALREADY_UPDATED=".($already?'YES':'NO')." CHANGE_BYTES=".strlen($changed)."\n";
    exit(0);
}
if(!in_array('--apply',$argv,true)){echo "USAGE --check | --apply\n";exit(2);}
if(!is_dir($privateRoot)&&!mkdir($privateRoot,0700,true))stopLegal('BACKUP_DIRECTORY');
$backupPath=$privateRoot.'/terms-of-use-1059-20261122.html';
if(!is_file($backupPath)&&file_put_contents($backupPath,$original,LOCK_EX)!==strlen($original))stopLegal('BACKUP_WRITE');
@chmod($backupPath,0600);
// Publish the new guide first, so the Terms link never points at a missing page.
if(!$existing){
    $newId=wp_insert_post([
      'post_type'=>'page',
      'post_title'=>'AI Content Usage Rights',
      'post_name'=>'ai-content-usage-rights',
      'post_content'=>$guide,
      'post_excerpt'=>'Understand commercial use, AI-generated audio rights, copyright limitations, voice disclosure and third-party content rules on Motricialy.',
      'post_status'=>'publish',
      'post_author'=>(int)$terms->post_author,
      'comment_status'=>'closed',
      'ping_status'=>'closed'
    ],true);
    if(is_wp_error($newId)||!is_int($newId)||$newId<1)stopLegal('GUIDE_CREATE');
}else{
    $newId=(int)$existing->ID;
    if($existing->post_status!=='publish'||!str_contains($existing->post_content,'Special rules for Text to Speech'))stopLegal('EXISTING_GUIDE_NOT_OURS');
}
$guidePublished=get_post($newId);
if(!$guidePublished||$guidePublished->post_name!=='ai-content-usage-rights'||$guidePublished->post_status!=='publish')stopLegal('GUIDE_READBACK');
if(defined('WPSEO_VERSION')){
    update_post_meta($newId,'_yoast_wpseo_title','AI Content Usage Rights & Commercial Use | Motricialy');
    update_post_meta($newId,'_yoast_wpseo_metadesc','Learn whether Motricialy AI-generated audio, text and images can be used commercially, what copyright rights apply, and when disclosure is required.');
}
if(defined('RANK_MATH_VERSION')){
    update_post_meta($newId,'rank_math_title','AI Content Usage Rights & Commercial Use | Motricialy');
    update_post_meta($newId,'rank_math_description','Learn about commercial use, copyright, AI voice disclosure and third-party rights for Motricialy AI-generated content.');
}
if(!$already){
    $updated=wp_update_post(['ID'=>$terms->ID,'post_content'=>$changed],true);
    if(is_wp_error($updated)||(int)$updated!==1059)stopLegal('TERMS_UPDATE');
}
$readTerms=get_post(1059);
if(!$readTerms||!str_contains($readTerms->post_content,'AI-Generated Output and Usage Rights')||
   !str_contains($readTerms->post_content,'/ai-content-usage-rights/')||
   !str_contains($readTerms->post_content,'Last updated: November 22, 2026'))stopLegal('TERMS_READBACK');
echo "LEGAL_PUBLISH_OK TERMS_ID=1059 GUIDE_ID=".$newId." TERMS_BACKUP=YES ORIGINAL_SECTIONS_PRESERVED=YES GUIDE_PUBLISHED=YES\n";
