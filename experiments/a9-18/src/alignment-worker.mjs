import {alignTranscript} from './alignment.mjs';
self.onmessage=({data})=>{
  try { self.postMessage({id:data.id,result:alignTranscript(data.transcript,data.chunks,data.duration)}); }
  catch { self.postMessage({id:data.id,error:'alignment_rejected'}); } // No content/error echo.
};
