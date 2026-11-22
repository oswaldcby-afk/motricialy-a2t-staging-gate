# Codex handoff — Motricialy Audio to Text (2026-11-22)

> Status: handoff / **not approved to deploy**.
> Start from **Stage A9.18**, carrying forward A9.17 experimental evidence. Do not restart market research or rebuild the public MVP from scratch.

## 1. Source-of-truth and branches

- Production URL: https://motricialy.com/tools/audio-to-text/
- Code repository: https://github.com/oswaldcby-afk/motricialy-a2t-staging-gate
- Current production release: `release/audio-to-text-v1`, baseline commit `b1ac40ab66775e9b1bf1b8944b2d465fecdd97f2` (A9.16).
- A9.17 experiment: `feature/a2t-a9-17-timing-diarization-evaluation`, **three commits ahead of the production release**, adding `experiments/a9-17/` only.
- Work in **`codex/a2t-a9-18-continuation-20261122`** (branched from A9.17 experiments). Open a draft PR targeting `release/audio-to-text-v1` when there is code and tests to review. **Do not merge or deploy** unless the user separately authorizes it.
- The GitHub default branch `main` contains an emergency pause/guard only. **Do not treat `main` as the full production source.**
- Hostinger public target, only after explicit approval: `public_html/tools/audio-to-text/`. Preserve independent React/HTML/CSS? **The current public build is plain HTML + JS + CSS**, not a React codebase. Verify actual files before altering.
- The public `api/index.php` is a gateway depending on **private Hostinger PHP code outside `public_html`**, e.g. `/home/u342033576/.motricialy-a2t-stage-a9/app-a9-7-full/private-server/`. That complete private backend is NOT present in this release repository. Record source-completeness gaps; never invent backend implementation details or copy secrets into GitHub. Reconstruct only from verified existing source and a securely obtained sanitized backup if needed. The current deployment must keep working.

### Production file map

```text
index.html
.htaccess
api/.htaccess
api/index.php
assets/audio-to-text.js
assets/audio-to-text.css
assets/a2t-captions.js
assets/a2t-video.js
assets/video-extract.mjs
assets/third_party/{license,notices}
experiments/a9-17/{README.md,align_existing_transcript.mjs}  # NOT PUBLIC
```

Additional QA/research repository (do not confuse with production):
https://github.com/oswaldcby-afk/motricialy-ai-humanizer-tests

## 2. Verified historical state — do not duplicate

**A9.15 shipped:** upload, English/Mandarin/mixed language control, local audio preview and playback-speed control, browser microphone WAV capture, editable raw transcript versus separate AI-clean version, optional summary/key points/action items, TXT/Markdown/DOCX export, quota readout. Public paid text operations were validated; a separate end-to-end paid test of a physical microphone recording on an actual phone is NOT evidenced.

**A9.16 shipped:** manually timed cue editor, cue-level seek and optional manually selected speaker labels, editable SRT/VTT export, local video-to-audio extraction using self-hosted Mediabunny, cancellation, audio preview. Browser QA and a one-shot real paid synthetic video-to-transcription test passed. **Neither automatic timestamps nor automatic speaker diarization is public.** Support for all codecs/500 MB files on low-RAM phones is NOT established.

**A9.17 research completed, NOT released:** local English Whisper Base q8 candidate word timing, conservative aligner against the existing authoritative transcript, and 11/11 alignment safety assertions. Synthetic sample: 15 words, 8.283s; Base EN q8 raw WER 6.67%, max onset error 0.182s, 15/15 words aligned after strictly matching number words with their exact numeral equivalents. These are tiny synthetic-only results; do not report as real-world accuracy. Energy-only speech detection was rejected (instrumental bursts false positives). Automatic speaker diarization NOT approved.

Primary retained reports:
- A9.15 release: https://github.com/oswaldcby-afk/motricialy-ai-humanizer-tests/blob/main/audio_to_text/stage_a9_15/RELEASE_NOTES_2026-11-21.md
- A9.16 release: https://github.com/oswaldcby-afk/motricialy-ai-humanizer-tests/blob/main/audio_to_text/stage_a9_16/RELEASE_NOTES_2026-11-21.md
- A9.17 verification: https://github.com/oswaldcby-afk/motricialy-ai-humanizer-tests/blob/main/audio_to_text/stage_a9_17/A9_17_TECHNICAL_VERIFICATION_2026-11-21.md
- A9.17 full test/benchmark scripts: `audio_to_text/stage_a9_17/` in that QA repository.
- Research-only source in working branch: `experiments/a9-17/`.

## 3. Current model/API policy (verified 2026-11-22)

Use the current OpenAI official file-transcription guide as the source of truth:
https://developers.openai.com/api/docs/guides/speech-to-text

- Keep `gpt-transcribe` as the **authoritative original transcript** unless a supported model is shown to be better with real evidence. Do not silently rewrite it using a second-pass model.
- **Do not introduce new production dependencies** on deprecated `whisper-1`, `gpt-4o-transcribe`, `gpt-4o-mini-transcribe`, or `gpt-4o-transcribe-diarize`; these are scheduled to shut down **2027-02-26**. Experimental *open-source local Whisper weights* are a separate option, subject to license/performance/quality evaluation, not the retiring OpenAI API endpoint.
- `gpt-transcribe` does not currently provide production-grade word timestamps or speaker tags in the recommended file-transcription workflow. Verify the docs again before final design rather than fabricating a provider capability.

## 4. Stage A9.18 — required next execution, in order

### A. Reproducible baseline and regression checks

1. Inspect production branch and QA repo, verify exact A9.15/A9.16 shipped behavior, `index.html` SEO text and linked JS/CSS consistency, API/Hostinger dependency boundaries, release-vs-experiment diff.
2. Identify missing source files for complete reproducible build (especially private PHP gateway dependencies), propose a **secret-free recovery plan** without attempting to access or publish production credentials.
3. Run existing unit/browser checks where feasible. Report passing tests, failures, and untested cases distinctly; do not treat old evidence or desktop emulation as fresh device results.
4. Check and preserve Motricialy Tool Page Standard, single H1/canonical/JSON-LD, Site Chrome header/footer, `/assets/motricialy-tracking.js`, GA4 + Microsoft Clarity, Clarity masking for audio/text filenames and transcripts. Do not add duplicate tracking scripts.

### B. Automatic timestamps / original-transcript alignment

1. Reproduce A9.17 benchmark and 11/11 safety cases. Refactor experimental code into testable modules without enabling any public UI.
2. Build or assemble a **rights-cleared, human-ground-truth dataset of at least 30 recordings**: Mandarin, English, real code-switching, accents, natural fast speech, quiet speech, music/noise, two- and four-speaker meetings, overlaps and pauses. Keep personal/sensitive user recordings out of public repositories.
3. Compare local on-device timestamp model(s) against manually annotated onset/end. Treat Chinese and mixed-language tokenization/word boundaries carefully, independent of English Base metrics.
4. Strict acceptance gates from A9.17: >=95% *safe* word coverage on representative cases, 90th-percentile onset error <=0.35s and offset error <=0.50s, zero negative/out-of-duration or nonmonotonic markers; never invent timestamps for unmatched words. Low coverage, mismatched numbers/paraphrases, overlap/uncertainty => manual review or reject.
5. Preserve original GPT transcript verbatim. Draft alignment must display `Draft timings — review before export`, be explicitly opt-in, editable and cancelable. Existing manual SRT/VTT is always available.
6. Separate transcription accuracy, lexical WER, word timing, cue timing and human-perceived subtitle usability in reporting; a numeric-equivalence matcher must never be presented as improved raw WER.

### C. Automatic speaker diarization — separate quality gate

1. Evaluate viable **nondeprecated**, legally usable local/OSS or supported provider alternatives (Ronda/pyannote/WeSpeaker, browser models, etc.) with license, real memory, model-download size and privacy constraints; do not assume GitHub PoCs are production-grade.
2. Independently compute diarization error rate (DER) on rights-cleared **2- and 4-speaker** human-annotated recordings, including overlap; break down false alarms, missed speech and confusion. Propose a product-level threshold justified by real listening and alternatives, rather than claiming one without measurements.
3. Show unknown-speaker fallback, manual relabel/merge/split workflow and confidence disclosures. Do not infer actual identities or silently assign names. If insufficient quality or technical feasibility, keep automated diarization **OFF** and document blocker.

### D. Audio–text synchronization and mobile constraints

1. Prototype cue/word click-to-seek and playhead highlighting **only where valid timestamps exist**; manual subtitles must remain functional without automated alignment.
2. Test real iPhone Safari and real Android Chrome: model download size, first load, cached load, decode/inference time, RAM/CPU/battery, short and 15-minute recordings, cancellation, long audio, huge video, unsupported codecs, offline/cached failures. Desktop browser simulation is NOT proof.
3. Protect audio privacy: no unexpected third-party model CDN requests or uploads; self-host/pin model assets with license/NOTICE if distributed; server transcribes only after explicit user action; redact content from logs/analytics and avoid accidental duplicate paid requests.

### E. Cost, quota, staging, regression and release verdict

Production limits to **preserve unless expressly approved**: max 24 MB uploaded audio, max 15-minute file, 2 files and 15 audio minutes per user/day, 2 text AI operations/day; $10 public per billing cycle and $15 overall hard ceiling. No unbounded retries or new paid testing. Local word-timing experiments should incur **zero extra OpenAI calls**. If a paid test is essential, seek explicit approval first and cap requests/cost.

Return a concrete gate report with **PASS / FAIL / BLOCKED** per tested feature, reference/fixture provenance, measurements, scripts, licensing, privacy and accurate limitations. **Do not merge, deploy to Hostinger, or publish automatic timestamp/diarization claims before quality gates and separate user approval.** If not feasible, ship nothing and explain what the manual features already cover.

## 5. Next stage after A9.18 (only if evidence passes)

A9.19: integrate behind an opt-in capability flag in a separate staging/demo branch, UI consistent with the Tool Page Standard, safe fallback to existing manual editor. Verify real end-to-end exports, seeking and accessible mobile UX.

A9.20: full regression + security/budget/analytics QA, compare source/production versions, provide deploy manifest and rollback instructions, request explicit go-live approval. Never automatically publish based only on passing synthetic CI.

If diarization fails but alignment passes, propose an **incremental release plan** that ships only validated timestamps while retaining manual speaker labels; do not block every feature on the weakest feature.

## 6. Codex deliverables

- Code + automated tests on this Codex branch, with logically separated commits.
- `docs/A9_18_VERIFICATION.md`: dataset descriptions/rights, benchmarks and evidence links, PASS/FAIL/BLOCKED, model/license/privacy review, cost measurement, what is untested.
- `docs/A9_18_REMAINING_WORK.md`: prioritized next issues (P0/P1/P2) and concrete blockers, especially private backend source completeness.
- A **draft PR** from this branch to `release/audio-to-text-v1` only after changes are ready for review. State **NO DEPLOYMENT**.
- Concise Chinese release recommendation and exact next action. If browser/device access, private source, or test data is unavailable, do maximum safe reproducible work then report the blocker **without fabricating passes**.

**Do not:** restart SERP research; modify unrelated tools, WordPress, global Chrome, global tracking; touch API secrets or production DB; replace original transcript with second ASR pass; advertise unproven automatic accuracy; assume a passing QA script means actual iOS hardware was tested.
