// Cancel actually terminates the CPU worker; row-level AbortSignal alone cannot interrupt JS.
export function createDraftSession({workerFactory=()=>new Worker(new URL('./alignment-worker.mjs',import.meta.url),{type:'module'})}={}) {
  let generation=0,worker=null,pending=null;
  function cancel() {
    generation++;worker?.terminate();worker=null;
    pending?.reject(new DOMException('Canceled','AbortError'));pending=null;
  }
  return {
    cancel,
    start({optIn,transcript,chunks,duration}) {
      if(optIn!==true) return Promise.reject(new Error('explicit_opt_in_required'));
      cancel();const id=generation;
      return new Promise((resolve,reject)=>{
        pending={resolve,reject};
        try {
          worker=workerFactory();
          worker.onmessage=({data})=>{
            if(id!==generation||data.id!==id) return;
            worker.terminate();worker=null;pending=null;
            if(data.error) reject(new Error('alignment_rejected'));else resolve(data.result);
          };
          worker.onerror=()=>{
            if(id!==generation)return;
            worker.terminate();worker=null;pending=null;reject(new Error('alignment_worker_failed'));
          };
          worker.postMessage({id,transcript,chunks,duration});
        } catch {worker?.terminate();worker=null;pending=null;reject(new Error('alignment_worker_failed'));}
      });
    }
  };
}
