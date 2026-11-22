# A9.18 verification — 2026-11-22

**NO DEPLOYMENT. Research implementation is reviewable; automatic timestamps, diarization and A9.19 integration remain BLOCKED.** This report is fresh local evidence, separate from historical A9.15–A9.17 claims. The user confirmed that no authorized recordings, human annotations or physical phone test devices are available in this task.

## Scope and source integrity

Working branch: `codex/a2t-a9-18-continuation-20261122`. Baseline: `release/audio-to-text-v1`, commit `b1ac40ab66775e9b1bf1b8944b2d465fecdd97f2`. Initial handoff commit: `e84c785`. QA source inspected at `267ac0dd3b5aaa7920e7adedaaf19857d54457bd` in `oswaldcby-afk/motricialy-ai-humanizer-tests`.

Every production tracked file is unchanged against the baseline after normalizing checkout line endings; hashes are in [verification.json](a9-18-evidence/verification.json). Changes are isolated to research and documentation. Existing static HTML/JS/CSS architecture, captions, video extraction, API gateway, limits and tracking were preserved. No product research, production rebuild, paid API test, Hostinger write, merge or release occurred.

## Gate results

| Gate | Status | Fresh evidence and limit |
|---|---|---|
| Production file preservation | PASS | All baseline files compared by SHA-256; no production diff |
| Static SEO / assets / masking | PASS | Single H1 and canonical, JSON-LD, one central tracking and chrome script, linked local files, masks for transcript/summary/hints/filenames/cues |
| A9.17 safety assertions | PASS | 11/11 rerun against unchanged legacy module; [result](a9-18-evidence/a9-17-safety.json) |
| A9.17 summary replay | PASS, limited | Recomputed lexical rate 1/15 = 6.67%, numeric-aware edit rate 0, maximum recorded **phrase** onset difference 0.182s from retained text/anchors; NOT a new model run or word-boundary measurement |
| A9.17 exact inference reproduction | BLOCKED | Original generated PCM, phrase WAV inputs, full word chunks and immutable model revisions were not retained in the QA checkout; generating new speech would be a different benchmark |
| A9.18 unit/regression suite | PASS | 53/53; [TAP](a9-18-evidence/unit-tests.tap). Safety, timing/DER arithmetic, numeric ambiguity, cancellation, synchronization, media replacement, anonymous labels and corpus rejection |
| Existing browser functionality | PASS, local mock | 41/41 total checks in Chrome 1440×900 and 390×844; [result](a9-18-evidence/browser-regression.json) |
| Actual Worker / audio synchronization | PASS, synthetic harness | Opt-in, immutable original text, actual module Worker result/cancel, cue/word seek and highlight; no model inference |
| Desktop alignment performance | PASS, bounded smoke | 100/1,000/2,500 units: about 7/40/197 ms in retained run, Node v24.21.0 Windows; two DP matrices at maximum size ~30 MB combined, excluding JS input/runtime. Not model memory or device battery data |
| PHP gateway syntax | PASS | PHP 8.4.27 `-l api/index.php`; private backend not executed |
| >=30 rights-cleared human-annotated natural recordings | BLOCKED | Zero eligible; machine-readable empty corpus deliberately remains BLOCKED |
| Real word/cue timing accuracy and listening quality | BLOCKED | No eligible ground truth; no empirical >=95% / P90 pass claimed |
| Real 2- / 4-speaker DER | BLOCKED | Hand-authored arithmetic fixtures only; no real diarization model outputs or human-annotated meetings |
| Real iPhone Safari / Android Chrome | BLOCKED | No physical devices; desktop mobile viewport and fake microphone are not substitutes |
| Live Site Chrome / footer / analytics runtime | BLOCKED for fresh acceptance | Integration preserved statically; isolated regression stubs chrome/tracking and blocks external traffic; no fresh live-shell fidelity or actual Clarity event inspection claimed |
| Complete private backend / ledger / hard budget test | BLOCKED | Private source absent; source-completeness recovery plan below |
| Publication / A9.19 | BLOCKED | No quality gate waiver, production feature flag, deploy artifact or automatic accuracy claims |

There are no unresolved reproducible failures in the implemented local suite. BLOCKED means missing evidence; it is not a successful release verdict.

## Changes and safety trade-offs

The isolated matcher rejects zero-length, negative, out-of-duration, reversed and overlapping intervals without tolerance. It rejects multi-unit chunks instead of duplicating a segment timestamp onto several words/Chinese characters. Forward/backward edit costs identify ambiguous repeated tokens: a timestamp survives only if every optimal edit path maps the original unit to the same candidate. Wrong numeric sequences reject even when aggregate coverage exceeds 95%. Fullwidth and signed decimals are not silently turned into positive integers. Original string and UTF-16 spans survive unchanged.

The minimum coverage gate is 95%; the draft disposition is intentionally stricter: any unmatched/ambiguous target unit rejects the whole suggestion. Numbers, missing words, paraphrases and known uncertainty never acquire interpolated times. Exact text match is not evidence of acoustic correctness: human timing measurements remain mandatory. Chinese single-character matching metrics must not be described as Chinese word WER. Compound number expressions outside the small inherited cardinal vocabulary are deliberately not converted.

Playback uses valid half-open intervals only. Gaps and media errors clear highlights; replacing media invalidates bindings even if duration is identical. Manual timing remains independent. Human-selected split/merge/relabel helpers retain text and anonymous labels; unknown or overlapping speakers abstain. Worker cancellation terminates CPU work and ignores stale responses. No automatic export/UI was added; future callers must honor disposition, cancel on media/transcript edits, present the draft warning, permit edits and require review before export.

## Reproduced browser scope

The local harness adapts scenarios from A9.15 `wave1_ai_mock_qa.py` and A9.16 `manual_caption_qa.py` / `video_integration_qa.py`; it does not rerun the old production/paid scripts. It verifies original vs mocked clean/summary, two untimed manual cues, SRT/VTT file contents, manual speaker label, cue seek, overlap export rejection, AAC/MP4 → local WAV preview, corrupt-media recovery, pre-aborted extraction, fake microphone WAV and discard, duplicate submit guard (one local mock audio request), and no unintended API activity. All external browser requests are aborted; only local mock API endpoints are fulfilled. No actual provider response, DOCX backend, real microphone hardware or 15-minute inference is covered.

[Desktop screenshot](a9-18-evidence/desktop.png) and [mobile viewport screenshot](a9-18-evidence/mobile-emulation.png) were visually inspected: workspace/captions render without horizontal overflow; mobile controls stack. Shared header/footer are intentionally absent in this isolated harness, so these screenshots cannot prove live shell fidelity. Browser tests use authored text and a generated 4-second tone/video, not natural speech. Mock text and audio requests cost $0.

## Metrics and dataset provenance

- Timing: same authoritative-token-index vectors, null for unavailable predictions, strict bounds and chronology, absolute onset/offset errors, nearest-rank P90, missing units remain in denominator. Per-recording thresholds: coverage >=0.95, onset P90 <=0.35s, offset P90 <=0.50s, zero invalid markers. Overlapping reference words require a later explicit overlap policy; the current word scorer rejects them.
- DER: full recording, collar 0s, overlaps scored, globally optimal one-to-one label mapping, false alarm / missed speech / confusion divided by reference speaker-seconds. Tests include 2 and 4 anonymous speakers, overlapping turns, label permutations, collapsed speakers, silence and false alarms. Results test the scorer, not a model; validate against an established scorer before using benchmark claims.
- Separate still-unmeasured axes: ASR-vs-human lexical accuracy, word timing, grouped cue timing, subtitle readability and listening/identity mistakes. Numeric-aware agreement against GPT text is not raw ASR WER.
- Real corpus manifest: [corpus-manifest.json](../experiments/a9-18/fixtures/corpus-manifest.json), intentionally empty. No 30-recording dataset is claimed. New test fixtures were authored as mathematical/text examples; test media is a synthetic sine tone generated locally. No user recording, identifying speaker name or external speech data was committed.
- Protocol: acquire at least 30 distinct natural recordings, roughly 10 English / 10 Mandarin / 10 genuine code-switching; distribute accents, fast/quiet speech, music/noise, pauses, 2- and 4-person meetings and overlaps across them. Include short and near-15-minute samples. Obtain recording-use permission and redistribution scope privately; store audio hash, license/consent evidence reference, opaque ID, language/tags and duration. Human annotators independently mark words and overlapping speaker turns, adjudicate differences, freeze a held-out subset and retain boundary conventions. Synthetic/noisy derivatives do not increase unique-natural-recording count. Do not publish audio or consent documents by default.

## Current model/license/privacy review

Checked primary sources on 2026-11-22; no model is newly installed or distributed by this branch.

| Candidate | Verified source information | A9.18 disposition |
|---|---|---|
| Current OpenAI file transcription | [Official guide](https://developers.openai.com/api/docs/guides/speech-to-text) continues to distinguish recommended `gpt-transcribe` from deprecated specialized timestamp/diarization workflows; those legacy paths end 2027-02-26 | Preserve authoritative transcript, no new deprecated dependency, zero provider calls |
| Local Whisper Base EN ONNX | [Xenova model card](https://huggingface.co/Xenova/whisper-base.en) labels conversion Apache-2.0; [upstream Whisper](https://github.com/openai/whisper) is a separate code/model provenance source | English candidate only. Pin exact converted artifacts/revision and audit upstream + conversion notices before redistribution. Actual download bytes/RAM/current inference UNMEASURED |
| Ronda | [Primary repo](https://github.com/jdpinedac/ronda) explicitly release candidate; pyannote segmentation + WeSpeaker embeddings, requires speaker count. Its approximately 11.7 MB compressed first visit and desktop timings are **upstream reports**, not our measurements | Potential local experiment; code MIT does not settle every weight/access condition. Product DER, memory and phone behavior BLOCKED. Do not adopt identity enrollment/names |
| pyannote community-1 | [Model card](https://huggingface.co/pyannote/speaker-diarization-community-1) CC-BY-4.0, gated conditions/account information; supports offline copy after access | No conditions accepted on user's behalf. Check each bundled model license and access before local use. Actual download/RAM/DER UNMEASURED |
| WeSpeaker | [Primary repo](https://github.com/wenet-e2e/wespeaker) Apache-2.0 toolkit with embedding and diarization paths | Toolkit license is not blanket proof for chosen weights/training data. Needs fixed artifacts, segmentation/clustering evaluation, actual memory/download/DER |
| Nemotron browser proof of concept | [Primary repo](https://github.com/khawjaahmad/nemotron-diarization-web) browser ONNX prototype | Deferred; exact usable graph/license/device feasibility unverified here. No repeat of upstream performance as measured Motricialy evidence |

No defensible product DER threshold is established without real listening. Keep automated speakers OFF. Pre-register a threshold only after comparing error breakdowns and human correction effort with manual labels; independently inspect overlap and every 2-/4-speaker stratum. [dscore's DER definition](https://github.com/nryant/dscore) is the primary external metric reference; a future cross-check must use matching collar/overlap/scoring regions.

For any future model runner: explicit opt-in; local self-hosted/pinned assets with hashes and complete notices; fail closed offline rather than fall back to a third-party CDN; bound download, decode and worker memory; never upload audio for timing. This branch has no model-fetching code, telemetry, API client or credentials in the research modules.

## Source completeness and secret-free recovery

The committed gateway depends on an external private backend entry point and `private-server/bootstrap.php`; the repository has neither the full request handlers nor transitive packages/config/schema needed to reproduce provider calls, quota reservations or cleanup. Public gateway flags/env-variable handling are not proof of the private ledger's $10/$15 hard-ceiling behavior. A9.15/A9.16 backend PASS results remain historical only.

Recovery plan: obtain a sanitized source-only export through an authorized secure handoff, without reading/publishing production credentials or DB contents. Include private entry/router, bootstrap and transitive code, dependency locks and license notices, schema/migrations without rows, configuration **names/types only**, flag semantics, budget/quota/idempotency logic, temporary-media cleanup and test commands. Run secret scanning locally before import; replace secret values with inert examples, use an isolated database and fake provider. Verify default-deny, 24 MB/900s, 2 files/900s/day, 2 text ops/day, $10 public/$15 overall limits, concurrency/duplicate-request behavior and cleanup. Never reconstruct a guessed backend or change production to make local tests pass.

## Reproduction and cost

See [research README](../experiments/a9-18/README.md) for Node and browser commands. `verify.mjs` saves deterministic structure plus environment-dependent timings. PHP lint and `git diff --check` were also run successfully. No production build/ZIP is produced: this is A9.18 research, not an authorized release. No Hostinger or production DB access occurred. Paid provider requests: **0**; added API cost: **$0**; budgets and quotas unchanged. Public deployment and merge remain prohibited.
