/* Motricialy Text to Speech v2.5 - isolated UI enhancement */
(function(){
  'use strict';
  if(window.__motricialyTtsV25Applied)return;
  window.__motricialyTtsV25Applied=true;
  var css=`
@media (min-width:901px){
  .tts-page.studio-page{max-width:1490px!important}
  .tts-page .studio-workspace{grid-template-columns:minmax(0,1.7fr) minmax(420px,1fr)!important}
  .tts-page .studio-script{min-height:0!important}
  .tts-page .studio-writing{flex:none!important;min-height:0!important;padding:18px 0 13px!important}
  .tts-page .studio-writing textarea{flex:none!important;height:300px!important;min-height:240px!important}
  .tts-page .studio-footer{display:none!important}
  .tts-page .tts-v25-inline-action{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;margin:21px 0 0}
  .tts-page .tts-v25-inline-action .studio-generate{min-height:52px;min-width:230px;padding:12px 20px;border-radius:12px;background:#6c47da;border-color:#6c47da;box-shadow:0 6px 18px #6f4be824;font-size:17px;font-weight:790}
  .tts-page .tts-v25-inline-action .studio-generate:hover:not(:disabled){background:#5731c4;border-color:#5731c4}
  .tts-page .tts-v25-inline-quota{font-size:14px;color:#737b91;line-height:1.5}
}
@media (min-width:901px) and (max-width:1100px){.tts-page .studio-workspace{grid-template-columns:minmax(0,1.4fr) minmax(380px,1fr)!important}}
@media (max-width:900px){.tts-page .tts-v25-inline-action{display:none!important}}
.tts-page .studio-output.output{scroll-margin-top:92px}
@media (prefers-reduced-motion:reduce){.tts-page .tts-v25-inline-action .studio-generate{transition:none!important}}
`;
  var style=document.createElement('style');style.id='tts-v25-enhancement-css';style.textContent=css;
  document.head.appendChild(style);
  function resultScroll(){
    var root=document.getElementById('root')||document.body;
    var observer=null,timer=null,done=false;
    function finish(){if(done)return;done=true;if(observer)observer.disconnect();if(timer)clearTimeout(timer);}
    function check(){
      var output=document.querySelector('.tts-page .studio-output.output');
      if(!output)return;
      finish();
      requestAnimationFrame(function(){
        try{output.scrollIntoView({behavior:window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'start'});}catch(e){output.scrollIntoView();}
      });
    }
    observer=new MutationObserver(check);
    observer.observe(root,{childList:true,subtree:true});
    timer=setTimeout(finish,30000);
    requestAnimationFrame(check);
  }
  function attach(){
    var script=document.querySelector('.tts-page .voice-studio .studio-script');
    var original=document.querySelector('.tts-page .voice-studio .studio-footer .studio-generate');
    if(!script||!original)return false;
    if(document.getElementById('tts-v25-inline-action'))return true;
    var wrapper=document.createElement('div');
    wrapper.id='tts-v25-inline-action';wrapper.className='tts-v25-inline-action';
    var quota=document.createElement('span');quota.className='tts-v25-inline-quota';
    var btn=document.createElement('button');
    btn.id='tts-v25-generate';btn.type='button';btn.className=original.className;
    btn.setAttribute('aria-label','Generate speech');
    wrapper.appendChild(quota);wrapper.appendChild(btn);script.appendChild(wrapper);
    var usage=document.querySelector('.tts-page .studio-allowance [data-live-user-state="quota"]');
    function mirror(){
      btn.disabled=original.disabled;
      btn.innerHTML=original.innerHTML;
      quota.textContent=usage&&usage.textContent?usage.textContent:'Free daily allowance';
    }
    mirror();
    var obs=new MutationObserver(mirror);
    obs.observe(original,{attributes:true,attributeFilter:['disabled'],childList:true,characterData:true,subtree:true});
    if(usage)obs.observe(usage,{childList:true,characterData:true,subtree:true});
    original.addEventListener('click',function(){if(!original.disabled)resultScroll();},true);
    btn.addEventListener('click',function(){if(!original.disabled)original.click();});
    return true;
  }
  function init(){
    if(attach())return;
    var wait=new MutationObserver(function(){if(attach())wait.disconnect();});
    wait.observe(document.body,{childList:true,subtree:true});
    setTimeout(function(){wait.disconnect();},20000);
  }
  if(document.readyState==='complete')init();
  else window.addEventListener('load',init,{once:true});
})();
