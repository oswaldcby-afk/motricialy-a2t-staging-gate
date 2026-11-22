import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import assert from 'node:assert/strict';
import {fileURLToPath,pathToFileURL} from 'node:url';
import {createRequire} from 'node:module';
import {execFileSync} from 'node:child_process';
const root=fileURLToPath(new URL('../../../',import.meta.url));
const out=path.join(root,'docs/a9-18-evidence');
const tmp=process.env.A9_TMP || (process.platform==='win32'?'G:/codex/temp/motricialy-a2t-a9-18':path.join(os.tmpdir(),'motricialy-a2t-a9-18'));
fs.mkdirSync(tmp,{recursive:true});fs.mkdirSync(out,{recursive:true});
const require=createRequire(import.meta.url);
const pw=process.env.PLAYWRIGHT_MODULE ? await import(pathToFileURL(process.env.PLAYWRIGHT_MODULE).href) : require('playwright');
const checks=[],network=[],errors=[];
const check=(name,value)=>{checks.push({name,pass:!!value});assert(value,name);};
const server=http.createServer((req,res)=>{
 const pathname=decodeURIComponent(new URL(req.url,'http://localhost').pathname);
 const relative=pathname.replace(/^\/tools\/audio-to-text\//,'').replace(/^\//,'')||'index.html';
 const file=path.resolve(root,relative);
 if(!file.startsWith(path.resolve(root)+path.sep)){res.writeHead(403);res.end();return;}
 try{res.setHeader('Content-Type',file.endsWith('.html')?'text/html':/\.m?js$/.test(file)?'text/javascript':file.endsWith('.css')?'text/css':'application/octet-stream');res.end(fs.readFileSync(file));}
 catch{res.writeHead(404);res.end();}
});
await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
const origin='http://127.0.0.1:'+server.address().port;
let browser;
try {
 browser=await pw.chromium.launch({executablePath:process.env.CHROME_BIN || (process.platform==='win32'?'C:/Program Files/Google/Chrome/Application/chrome.exe':undefined),headless:true,args:['--use-fake-device-for-media-stream','--use-fake-ui-for-media-stream']});
 const video=path.join(tmp,'synthetic-tone.mp4');
 execFileSync('ffmpeg',['-hide_banner','-loglevel','error','-y','-f','lavfi','-i','color=c=black:s=160x120:r=10:d=4','-f','lavfi','-i','sine=frequency=440:sample_rate=12000:duration=4','-c:v','libx264','-c:a','aac','-shortest',video],{stdio:'pipe'});
 const wav=Buffer.alloc(44+12000*4*2);wav.write('RIFF');wav.writeUInt32LE(wav.length-8,4);wav.write('WAVEfmt ',8);wav.writeUInt32LE(16,16);wav.writeUInt16LE(1,20);wav.writeUInt16LE(1,22);wav.writeUInt32LE(12000,24);wav.writeUInt32LE(24000,28);wav.writeUInt16LE(2,32);wav.writeUInt16LE(16,34);wav.write('data',36);wav.writeUInt32LE(wav.length-44,40);
 for(let i=0;i<48000;i++) wav.writeInt16LE(Math.round(1200*Math.sin(i*Math.PI*2*440/12000)),44+i*2);
 for(const [mode,width,height] of [['desktop',1440,900],['mobile-emulation',390,844]]) {
  const context=await browser.newContext({viewport:{width,height},acceptDownloads:true});
  let mockedPosts=0,mockedAudioPosts=0;
  await context.route('**/*',async route=>{
   const req=route.request(),url=new URL(req.url());
   if(url.origin!==origin) {network.push({kind:'blocked-external',method:req.method(),host:url.host});await route.abort();return;}
   if(url.pathname==='/assets/motricialy-tracking.js'||url.pathname==='/assets/motricialy-site-chrome.js') {await route.fulfill({contentType:'text/javascript',body:'/* offline regression: shared runtime intentionally stubbed */'});return;}
   if(url.pathname.includes('/api/')) {
    const endpoint=url.pathname.split('/').at(-1);network.push({kind:'local-mock',method:req.method(),endpoint});
    let result;
    if(endpoint==='config') result={live_api_ready:true,ai_paused:false,max_files_daily:2,max_daily_minutes:15,budget:{public_remaining_estimated_usd:10}};
    else if(endpoint==='quota') result={files_remaining:2,minutes_remaining:15,text_operations_remaining:2};
    else if(endpoint==='capabilities') result={text_processing_ready:true,max_text_characters:12000,max_text_operations_daily:2};
    else if(endpoint==='transcribe'&&req.method()==='POST') {mockedAudioPosts++;await new Promise(r=>setTimeout(r,100));result={text:'Synthetic mocked transcription.',duration_seconds:4};}
    else if(endpoint==='process'&&req.method()==='POST') {mockedPosts++;const body=req.postDataJSON();result={mode:body.mode,text:body.mode==='clean'?'Clean synthetic text.':'Summary: synthetic only.'};}
    else {await route.abort();return;}
    await route.fulfill({contentType:'application/json',body:JSON.stringify(result)});return;
   }
   await route.continue();
  });
  const page=await context.newPage();page.on('pageerror',e=>errors.push(e.name+': '+e.message.slice(0,180)));
  const started=performance.now();await page.goto(origin+'/tools/audio-to-text/');
  await page.waitForFunction(()=>!document.getElementById('ai-tools').hidden);
  check(mode+' first frame under 5s',performance.now()-started<5000);
  check(mode+' no horizontal overflow',await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+3));
  check(mode+' no paid request on load',mockedPosts===0);
  await page.locator('#transcript').fill('First sentence. Second sentence.');
  await page.locator('#clean-btn').click();await page.waitForFunction(()=>document.getElementById('tab-clean').getAttribute('aria-selected')==='true');
  await page.locator('#tab-original').click();check(mode+' original preserved after mocked clean',await page.locator('#transcript').inputValue()==='First sentence. Second sentence.');
  await page.locator('#summary-btn').click();await page.waitForFunction(()=>!document.getElementById('summary-panel').hidden);
  check(mode+' original preserved after mocked summary',await page.locator('#transcript').inputValue()==='First sentence. Second sentence.');
  await page.locator('#caption-workspace > summary').click();await page.locator('#caption-split').click();
  check(mode+' two untimed manual captions',await page.locator('.cue-item').count()===2 && await page.locator('.cue-times input').evaluateAll(inputs=>inputs.every(x=>x.value==='')));
  await page.locator('#caption-srt').click();check(mode+' untimed export rejected',(await page.locator('#caption-status').textContent()).includes('mark both'));
  const inputs=page.locator('.cue-times input');for(const [i,value] of ['00:00:00.100','00:00:01.500','00:00:01.500','00:00:03.700'].entries()) await inputs.nth(i).fill(value);
  await page.locator('.cue-speaker select').first().selectOption('Speaker 1');
  for(const format of ['srt','vtt']) {
   const download=page.waitForEvent('download');await page.locator('#caption-'+format).click();const d=await download;
   const file=path.join(tmp,mode+'.'+format);await d.saveAs(file);const content=fs.readFileSync(file,'utf8');
   check(mode+' '+format+' timestamps and anonymous manual label',content.includes(format==='srt'?'00:00:00,100 --> 00:00:01,500':'00:00:00.100 --> 00:00:01.500')&&content.includes('Speaker 1: First sentence.')&&(format==='srt'||content.startsWith('WEBVTT')));
  }
  await page.locator('#audio-file').setInputFiles({name:'synthetic-tone.wav',mimeType:'audio/wav',buffer:wav});
  await page.waitForFunction(()=>Number.isFinite(document.getElementById('audio-player').duration));
  await page.locator('.cue-play').nth(1).click();check(mode+' manual click seeks',await page.locator('#audio-player').evaluate(a=>a.currentTime>=1.5&&a.currentTime<2.5));
  await page.locator('#audio-player').evaluate(a=>a.pause());
  await inputs.nth(2).fill('00:00:01.000');await page.locator('#caption-srt').click();check(mode+' overlap export rejected',(await page.locator('#caption-status').textContent()).includes('overlap'));await inputs.nth(2).fill('00:00:01.500');
  await page.locator('#video-extract-panel > summary').click();await page.locator('#video-file-input').setInputFiles(video);await page.locator('#video-extract').click();
  await page.waitForFunction(()=>document.getElementById('video-extract-status').textContent.startsWith('Audio extracted locally.'),{},{timeout:30000});
  check(mode+' local video extraction WAV preview',await page.locator('#file-name').textContent()==='synthetic-tone-audio.wav'&&await page.locator('#audio-player').evaluate(a=>a.src.startsWith('blob:')));
  check(mode+' only explicit mocked text requests',mockedPosts===2);
  await page.screenshot({path:path.join(out,mode+'.png'),fullPage:true});
  // Use actual module Worker and real media element on the loopback origin.
  const research=await page.evaluate(async()=>{
   const {createDraftSession}=await import('/experiments/a9-18/src/draft-session.mjs');
   const {createTimeline,bindPlayback}=await import('/experiments/a9-18/src/synchronization.mjs');
   const s=createDraftSession();let optIn=false;try{await s.start({optIn:false});}catch{optIn=true;}
   const original='Hello world';const result=await s.start({optIn:true,transcript:original,chunks:[{text:'hello',start:0,end:1},{text:'world',start:1,end:2}],duration:4});
   const audio=document.getElementById('audio-player');await new Promise(resolve=>{if(Number.isFinite(audio.duration))resolve();else audio.addEventListener('loadedmetadata',resolve,{once:true});});
   const timeline=createTimeline(result.mappedWords,audio.duration);let active=null;const sync=bindPlayback(audio,timeline,x=>active=x);
   const button=document.createElement('button');button.textContent='Synthetic word seek';button.onclick=()=>sync.seek(1);document.body.append(button);button.click();
   const sought=audio.currentTime===1&&active===1;sync.dispose();button.remove();
   const words=Array.from({length:2500},(_,i)=>({text:'unit'+i,start:i*.3,end:i*.3+.2}));
   const pending=s.start({optIn:true,transcript:words.map(x=>x.text).join(' '),chunks:words,duration:900});s.cancel();let canceled=false;try{await pending;}catch(e){canceled=e.name==='AbortError';}
   return {optIn,unchanged:result.transcript===original,label:result.label,sought,canceled};
  });
  check(mode+' real Worker opt-in, preserved text, seek/highlight and cancellation',research.optIn&&research.unchanged&&research.sought&&research.canceled&&research.label==='Draft timings — review before export');
    await page.evaluate(()=>{const form=document.getElementById('upload-form');form.dispatchEvent(new Event('submit',{cancelable:true}));form.dispatchEvent(new Event('submit',{cancelable:true}));});
  await page.waitForFunction(()=>document.getElementById('transcript').value==='Synthetic mocked transcription.');
  check(mode+' duplicate-submit guard one local mock request',mockedAudioPosts===1);
  await page.locator('#recording-panel > summary').click();await page.locator('#record-start').click();
  await page.waitForFunction(()=>!document.getElementById('record-stop').disabled);
  await page.waitForTimeout(1500);await page.locator('#record-stop').click();
  check(mode+' fake microphone produces local WAV',await page.locator('#file-name').textContent()==='recording.wav');
  await page.locator('#record-discard').click();check(mode+' recording discard removes media',await page.locator('#audio-player').evaluate(a=>!a.hasAttribute('src')));
  await page.locator('#video-file-input').setInputFiles({name:'corrupt.mp4',mimeType:'video/mp4',buffer:Buffer.alloc(256)});
  await page.locator('#video-extract').click();await page.waitForFunction(()=>document.getElementById('video-extract-status').classList.contains('error'));
  check(mode+' corrupt media recovers controls',await page.locator('#video-choose').isEnabled());
  const abortedExtraction=await page.evaluate(async()=>{
    const {extractVideoAudio}=await import('/assets/video-extract.mjs');
    try{await extractVideoAudio(new File([new Uint8Array(256)],'synthetic.mp4'),()=>{},AbortSignal.abort());return false;}catch{return true;}
  });
  check(mode+' extraction pre-cancellation rejects',abortedExtraction);
  check(mode+' recording and extraction add no API calls',mockedPosts===2&&mockedAudioPosts===1);
  await context.close();
 }
 check('no uncaught browser errors',errors.length===0);
} catch(e) {errors.push(e.name+': '+e.message.slice(0,200));process.exitCode=1;}
finally {
 await browser?.close();await new Promise(resolve=>server.close(resolve));
 const result={scope:'LOCAL Chrome regression; APIs mocked; shared chrome/tracking stubbed; mobile viewport NOT physical phone',checks,errors,network,paidApiCalls:0,pass:checks.length>0&&checks.every(c=>c.pass)&&errors.length===0};
 fs.writeFileSync(path.join(out,'browser-regression.json'),JSON.stringify(result,null,2)+'\n');console.log(JSON.stringify({pass:result.pass,checks:checks.length,errors}));
}
