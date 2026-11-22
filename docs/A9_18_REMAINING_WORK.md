# A9.18 remaining work — 2026-11-22

**NO DEPLOYMENT.** Review the isolated code now; keep automatic timestamps and speakers OFF. A9.19 is not authorized by synthetic tests.

## P0 — Evidence / source blockers

1. **Rights-cleared human ground truth (BLOCKED, user has none):** obtain >=30 distinct natural recordings with English, Mandarin, genuine code-switching, accents, fast/quiet speech, music/noise, pauses, 2-/4-speaker and overlap strata. Follow the protocol in A9_18_VERIFICATION.md. Store private consent/license evidence and audio outside Git; adjudicate manual word/speaker boundaries. The empty manifest must remain blocked until actual eligible recordings exist.
2. **Physical devices (BLOCKED, unavailable):** real iPhone Safari and Android Chrome, including low-RAM hardware. Record device/OS/browser, cold/cached model bytes, decode/inference time, peak RAM, CPU/battery, 30s and 15-minute cases, cancellation latency, huge video, unsupported codecs, offline/incomplete-cache behavior. Desktop Chrome/mobile emulation/fake mic do not close this item.
3. **Private backend source (BLOCKED, missing from release repo):** sanitized source-only secure export, then offline fake-provider and disposable DB integration tests. Include schema, locks, flag/config semantics and cleanup without keys, HMACs, DSNs/passwords or production rows. Verify $10 public/$15 total hard ceilings, daily quotas and idempotent/concurrent reservations before any deployment proposal.
4. **Reproducible model bundle and A9.17 inputs:** retain pinned model/config/tokenizer revisions and SHA-256, license/NOTICE, exact PCM and raw word chunks from a new zero-API run. The A9.17 summary cannot reproduce inference or word offsets. New generated speech is a new benchmark. Do not fetch from a model CDN in the user workflow. Account-gated terms must be handled by the account owner.

## P1 — Next technical evaluations once inputs exist

- Run local English and multilingual candidates against the held-out corpus. Existing English Base numbers do not validate Mandarin. Multi-character/segment chunks currently reject instead of manufacturing subword times; evaluate an explicit human-reviewed segmentation/boundary convention before adding another adapter.
- Score each language/noise/overlap stratum, not only an aggregate. Require >=95% safe coverage, onset P90 <=0.35s, offset P90 <=0.50s, zero invalid markers; separately report lexical WER/CER, timing, cue grouping and listening usability. The matcher is intentionally stricter and rejects any unmatched original unit; quantify abstention and human editing burden.
- Compare the DER scorer with dscore/pyannote.metrics under identical zero-collar, overlap-inclusive settings. Run actual 2- and 4-speaker model predictions with anonymous IDs and report false alarms/misses/confusion. Do not pick a product DER threshold until listening/correction-time evidence justifies it. Unknown is preferable to invented identities.
- Benchmark candidate licensing, downloaded asset bytes, actual memory and mobile energy; separate source license from each weight/conversion license. Keep recordings private and log metrics only.
- Full live Site Chrome/footer interaction and GA4/Clarity masking runtime checks remain unverified in the isolated harness; shared scripts are currently only statically preserved. Future public rollout requires these checks plus real backend/DOCX error/recovery tests.
- Add full automated-draft UI only in A9.19 after evidence passes: explicit opt-in, draft warning, editing/review before export, cancel on any input change, manual SRT/VTT fallback. Existing helper modules are not a production feature.

## P2 — Later release preparation

- Consider timestamp-only staging if alignment clears its independent gate while speakers remain blocked; preserve manual speaker labels.
- Establish model cache integrity/eviction/download retry bounds, peak-memory controls, and supported-codec/device disclosures from measurements.
- A9.20: complete regression/security/budget/analytics checks, prepare exact allowlisted deployment manifest and rollback. Exclude experiments, private data, tests and docs from any eventual public upload. Re-read canonical/live content only after separate deployment authorization.
- Obtain explicit go-live approval. Do not merge this Draft PR or upload the repository wholesale to Hostinger.

## Already completed; do not redo

- Preserved all A9.16 production files and retained original A9.17 module.
- 11 legacy assertions, 53 new unit cases and 41 local Chrome checks pass.
- Implemented strict/ambiguity-aware matching, metric arithmetic, safe seek/highlight, anonymous edit helpers, Worker cancellation and corpus metadata gate.
- Added reproducible scripts, privacy-scoped evidence, screenshots and backend recovery plan.

**Exact next action:** review this research PR while arranging the private corpus/annotation and physical-device work; the next runnable measurements use the supplied scripts once those real dependencies exist. No further production implementation should be inferred from the current PASS results.
