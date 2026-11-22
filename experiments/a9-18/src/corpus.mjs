const requiredTags=['english','mandarin','code-switching','accent','fast','quiet','music','noise','two-speaker','four-speaker','overlap','pauses'];
export function validateCorpus(manifest) {
  const blockers=[];
  if(!manifest || !Array.isArray(manifest.recordings)) return {status:'BLOCKED',eligibleRecordings:0,blockers:['recording_manifest_missing']};
  const ids=new Set(),hashes=new Set(),tags=new Set();let eligible=0;
  for(const row of manifest.recordings) {
    const valid= row && typeof row.id==='string' && /^[a-z0-9-]{1,80}$/.test(row.id) &&
      !ids.has(row.id) && typeof row.sha256==='string' && /^[a-f0-9]{64}$/.test(row.sha256) && !hashes.has(row.sha256) &&
      row.kind==='natural' && row.rights?.cleared===true && typeof row.rights.evidenceRef==='string' && row.rights.evidenceRef.trim().length>0 &&
      row.annotation?.humanReviewed===true && typeof row.annotation.reviewerRef==='string' && row.annotation.reviewerRef.trim().length>0 &&
      row.annotation.wordBoundaries===true && row.annotation.speakerTurns===true &&
      Number.isFinite(row.duration)&&row.duration>0&&row.duration<=900 &&
      [1,2,4].includes(row.speakers) && Array.isArray(row.tags) && row.tags.every(t=>requiredTags.includes(t));
    if(!valid) {blockers.push('invalid_or_unreviewed_recording');continue;}
    ids.add(row.id);hashes.add(row.sha256);eligible++;row.tags.forEach(t=>tags.add(t));
    if(row.tags.includes('two-speaker')&&row.speakers!==2 || row.tags.includes('four-speaker')&&row.speakers!==4) blockers.push('speaker_count_tag_mismatch');
  }
  if(eligible<30) blockers.push('fewer_than_30_natural_reviewed_recordings');
  for(const tag of requiredTags) if(!tags.has(tag)) blockers.push('missing_stratum:'+tag);
  return {status:blockers.length?'BLOCKED':'METADATA_READY_NOT_RELEASE_ACCEPTANCE',eligibleRecordings:eligible,blockers:[...new Set(blockers)],
    caveat:'Metadata attestations do not verify rights, audio hashes, annotations, subgroup accuracy, or device performance.'};
}
