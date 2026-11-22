# A9.18 isolated research

NO DEPLOYMENT. Production remains A9.16. Automatic timings and diarization remain OFF.

From the repository root (Node >=22):

```sh
node experiments/a9-18/scripts/a9-17-safety.mjs
node --test experiments/a9-18/tests/*.test.mjs
node experiments/a9-18/scripts/verify.mjs
```

`verify.mjs` compares every production file with baseline `b1ac40a`, checks SEO/privacy integrations, runs tests, measures desktop alignment CPU time, and writes `docs/a9-18-evidence/verification.json` and `unit-tests.tap`. No network, provider credentials, or dependencies are needed. PASS here is a software regression result, never model/release acceptance.

Browser regression requires Playwright, local Chrome and ffmpeg. Set `PLAYWRIGHT_MODULE` to an existing `playwright/index.mjs` if it is not normally resolvable; optional `CHROME_BIN` and `A9_TMP` select the browser and temporary media directory. Then run:

```sh
node experiments/a9-18/scripts/browser-regression.mjs
```

The server binds to 127.0.0.1 only and closes after testing. All API responses are local mocks; external requests are blocked and central Site Chrome/tracking are stubbed. Chrome microphone input is fake. The mobile viewport is desktop Chrome emulation. This script does not connect to production or prove backend, iPhone, Android, live chrome, or real speech quality. The screenshots contain authored synthetic text only.

## Modules and boundaries

- `alignment.mjs`: exact/limited numeric matching, unique optimal-edit-path safety, original UTF-16 spans, strict valid intervals. Han characters are matching units, not linguistic Chinese words. Multi-unit chunks and overlaps reject; never distribute a whole Chinese segment over individual characters. Rejected results may retain diagnostic mapped units but must NOT be presented/exported as an approved draft. Partial results never bypass the disposition gate.
- `metrics.mjs`: independently testable timing P90/coverage and DER. Timing arrays use the same authoritative-text unit indices with null for missing predictions. DER uses a global one-to-one anonymous-label mapping, full recording, zero collar, overlap included, speaker-seconds denominator; silence-only reference is unscorable, and DER can exceed 100%. Hand-calculated tests validate arithmetic, not model quality. Maximum eight speakers per side; no standard-scorer equivalence claim yet.
- `synchronization.mjs`: valid half-open intervals only, gaps clear highlight, original item indices preserved across missing words. Manual cues work without a model. Media replacement invalidates bindings, including recordings of equal length. Rebind after media/text/timing edits. Explicit human split, merge, and anonymous relabel helpers do not infer identities.
- `draft-session.mjs`: explicit opt-in required; real Worker termination on cancel/restart. Call cancel on media/text changes and teardown. Returns `Draft timings — review before export`; the caller must check disposition. No production UI, model loader, automatic export, or paid call is installed.
- `corpus.mjs`: minimum-count, uniqueness, rights/reviewer attestation and stratum checks. Metadata readiness is not verification of the actual recording/license/annotations or permission to release.

The core gate requires >=95% safe coverage, but this implementation additionally rejects ANY unmatched/ambiguous target unit rather than suggest a partial subtitle export. This intentionally sacrifices coverage until real data can justify a less restrictive draft workflow. The numeric-aware edit rate is never raw lexical WER. Neither rate is ASR-vs-human accuracy unless an actual human reference is supplied.

## Private measurement input

`node experiments/a9-18/scripts/evaluate-recording.mjs PRIVATE_CASE.json > PRIVATE_METRICS.json` accepts `{transcript, chunks, duration, referenceWords, referenceTurns, hypothesisTurns}`. Chunks have `text,start,end` (or `timestamp:[start,end]`); reference words have `text,start,end` at the authoritative transcript's unit indices. Turn arrays contain `speaker,start,end` with anonymous IDs. Reference times must be independently human annotated. Word evaluation currently rejects overlapping references; score overlap speaker turns through DER separately. Run a separate lexical ASR-vs-human transcript comparison for transcription accuracy; this CLI's lexicalErrorRate compares the second pass to the authoritative transcript.

The CLI emits metrics only, removes the speaker mapping and redacts errors. Keep private JSON and media outside Git. Do not add sensitive speech to fixtures, logs, screenshots, or Actions artifacts. Ignore rules are an extra guard, not a consent mechanism.

## Historical provenance

`fixtures/a9-17-base-summary.json` is the unchanged retained benchmark summary from QA repository commit `267ac0dd3b5aaa7920e7adedaaf19857d54457bd`, path `audio_to_text/stage_a9_17/local_base_benchmark.json`. Its text/phrase anchors can be rescored; it does NOT contain the original PCM, model revision hashes, or full word chunks. No new inference is claimed.

`scripts/a9-17-safety.mjs` carries the same 11 assertions from that commit's `audio_to_text/stage_a9_17/aligner.unit.mjs`; only the import and output destination changed. It deliberately tests the unchanged A9.17 implementation. A9.18 has its own stricter tests.

See ../../docs/A9_18_VERIFICATION.md and ../../docs/A9_18_REMAINING_WORK.md.
