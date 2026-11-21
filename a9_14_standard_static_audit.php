<?php
declare(strict_types=1);
/* A9.14 read-only production standard audit: no personal files or paid requests. */
if(PHP_SAPI!=='cli'||basename(__DIR__)!=='audio-to-text-a9-private-gate-20261120')exit(90);
if(strpos((string)@file_get_contents(__DIR__.'/.htaccess'),'Require all denied')===false)exit(91);
$web='/home/u342033576/domains/motricialy.com/public_html';
$root=$web.'/tools/audio-to-text';
$checks=[];
$out=['stage'=>'A9.14','audit'=>'production_standard_static','paid_api_calls'=>0];
try{
 $html=(string)file_get_contents($root.'/index.html');
 $css=(string)file_get_contents($root.'/assets/audio-to-text.css');
 $js=(string)file_get_contents($root.'/assets/audio-to-text.js');
 $tracking=$web.'/assets/motricialy-tracking.js';
 $chrome=$web.'/assets/motricialy-site-chrome.js';
 $tracker=is_file($tracking)?(string)file_get_contents($tracking):'';
 $chromeJS=is_file($chrome)?(string)file_get_contents($chrome):'';
 $canonical='https://motricialy.com/tools/audio-to-text/';
 $icon='https://motricialy.com/wp-content/uploads/2023/07/cropped-motricialy-site-icon-32x32.png';
 $checks['canonical']=substr_count($html,'<link rel="canonical" href="'.$canonical.'">')===1;
 $checks['one_h1']=preg_match_all('/<h1\\b/i',$html)===1;
 $checks['one_tracking_head']=substr_count($html,'<script src="/assets/motricialy-tracking.js"></script>')===1 &&
   strpos($html,'motricialy-tracking.js')<strpos($html,'</head>');
 $checks['tracking_asset_exists']=is_file($tracking)&&strlen($tracker)>400;
 $checks['tracking_owns_ga4']=preg_match('/G-[A-Z0-9]{6,}/',$tracker)===1;
 $checks['tracking_owns_clarity']=str_contains(strtolower($tracker),'clarity');
 $checks['no_page_level_ga4_bootstrap']=!str_contains($html,'googletagmanager.com')&&!str_contains($html,"gtag('config'");
 $checks['no_page_level_clarity_bootstrap']=!str_contains($html,'clarity.ms/tag/');
 $checks['favicon_exactly_once']=substr_count($html,'<link rel="icon" href="'.$icon.'" type="image/png">')===1;
 $checks['favicon_exists_on_disk']=is_file($web.'/wp-content/uploads/2023/07/cropped-motricialy-site-icon-32x32.png');
 $checks['og_url']=substr_count($html,'property="og:url" content="'.$canonical.'"')===1;
 $checks['og_title_desc']=str_contains($html,'property="og:title"')&&str_contains($html,'property="og:description"');
 $checks['social_twitter_card']=str_contains($html,'name="twitter:card"');
 $checks['page_indexable']=stripos($html,'noindex')===false&&
   stripos((string)file_get_contents($root.'/.htaccess'),'noindex')===false;
 $checks['breadcrumb_jsonld_only']=str_contains($html,'"@type":"BreadcrumbList"') &&
   !str_contains($html,'<nav class="crumbs"');
 $checks['software_application_jsonld']=str_contains($html,'"@type":"SoftwareApplication"');
 $checks['site_chrome_script_one']=substr_count($html,'motricialy-site-chrome.js')===1 &&
   is_file($chrome)&&strlen($chromeJS)>2000;
 $checks['site_chrome_outside_tool']=strpos($html,'data-motricialy-site-header')<strpos($html,'<main id="a2t-tool">') &&
   strpos($html,'data-motricialy-site-footer')>strpos($html,'</main>');
 $checks['no_local_analytics_implementations']=stripos($js,'gtag(')===false&&stripos($js,'clarity(')===false;
 $checks['sensitive_areas_explicitly_masked']=substr_count($html,'data-clarity-mask="true"')===4;
 $checks['content_sections']=str_contains($html,'How to Use Motricialy Audio to Text') &&
   str_contains($html,'>Key Features</h2>')&&str_contains($html,'>Frequently Asked Questions</h2>') &&
   str_contains($html,'>Related Tools</h2>');
 $checks['three_steps']=preg_match_all('/<span class="step-index">/', $html)===3;
 $checks['six_features_without_icons']=preg_match('/<div class="features-grid">(.*?)<\/div>/s',$html,$m)===1 &&
   substr_count($m[1],'<article><h3>')===6&&substr_count($m[1],'<article><span>')===0;
 $checks['six_related']=preg_match('/<div class="related-links">(.*?)<\/div>/s',$html,$m)===1 &&
   substr_count($m[1],'<a href="/tools/')===6;
 $checks['faq_count']=substr_count($html,'<details><summary>')===6;
 $checks['workspace_keyboard_and_dnd']=str_contains($html,'id="drop-zone" role="button" tabindex="0"') &&
   str_contains($js,"drop.addEventListener('drop'")&&str_contains($js,"drop.addEventListener('keydown'");
 $checks['no_extra_dev_labels']=!str_contains($html,'Service available')&&!str_contains($html,'STEP 01');
 $checks['api_routes_unmodified']=str_contains($js,"api+'transcribe'")&&str_contains($js,"api+'export/docx'")&&
   is_file($root.'/api/index.php');
 $checks['api_scoped_limits']=str_contains((string)file_get_contents($root.'/api/.htaccess'),'LimitRequestBody 27262976') &&
   str_contains((string)file_get_contents($root.'/api/.htaccess'),'php_value upload_max_filesize 24M');
 $checks['sitemap_included_once']=substr_count((string)file_get_contents($web.'/tools-sitemap.xml'),$canonical)===1;
 $out['chrome_runtime']=[
   'bytes'=>strlen($chromeJS),
   'v16_key_present'=>str_contains($chromeJS,'motricialy-live-chrome-v16'),
   'v14_key_present'=>str_contains($chromeJS,'motricialy-live-chrome-v14'),
   'v10_key_present'=>str_contains($chromeJS,'motricialy-live-chrome-v10'),
   'has_aria_expanded'=>str_contains($chromeJS,'aria-expanded'),
   'has_legacy_footer_normalizer'=>str_contains($chromeJS,'normalizeLiveFooter')
 ];
 $out['tracking_asset']=[
    'bytes'=>strlen($tracker),
    'ga4_id_count'=>preg_match_all('/G-[A-Z0-9]{6,}/',$tracker),
    'has_clarity_tag'=>str_contains($tracker,'clarity.ms'),
    'script_bootstraps_gtag'=>str_contains($tracker,'gtag'),
    'source_is_site_shared'=>true
 ];
 $out['checks']=$checks;
 $out['passed']=count(array_filter($checks));
 $out['total']=count($checks);
 $out['status']=!in_array(false,$checks,true)?'STATIC_STANDARD_PASS':'STATIC_STANDARD_PARTIAL';
}catch(Throwable $e){
 $out['status']='STATIC_STANDARD_EXCEPTION';
 $out['error_type']=get_class($e);
}
echo json_encode($out,JSON_UNESCAPED_SLASHES).PHP_EOL;
if($out['status']==='STATIC_STANDARD_EXCEPTION')exit(97);
