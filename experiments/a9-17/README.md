# A9.17 Automatic Timings & Speaker Identification — Isolated Research

**Status:** Experimental; do not merge to the live `release/audio-to-text-v1` branch.

This branch retains only a conservative, number-aware transcript-to-word-timestamp matcher under `experiments/a9-17/`. It does **not** load a model, call OpenAI, update the PHP API, or enable automatic SRT/VTT in public.

## Verified so far

- The current `gpt-transcribe` remains the authoritative user-facing transcript. Its recommended API does not expose production-ready word timestamps/speaker assignments.
- Whisper Base EN q8 running locally in GitHub Actions produced valid word timestamps with a 0.182 s maximum onset error across three known phrase boundaries on one 8.28 s synthetic fixture; this is not a natural-language/mobile benchmark.
- Its raw lexical WER was 6.67%, caused by `seventeen` vs `17`. The protected matcher treated these as exact numeric equivalents without treating different values as equal: 15/15 synthetic words aligned; 11/11 safety assertions passed.
- A naive amplitude-only detector incorrectly classified three instrumental music bursts as speech, so it was rejected.
- Browser diarization candidates have unresolved long-meeting accuracy and/or restricted model access. No production integration.

**Full source-backed report:** https://github.com/oswaldcby-afk/motricialy-ai-humanizer-tests/blob/main/audio_to_text/stage_a9_17/A9_17_TECHNICAL_VERIFICATION_2026-11-21.md

**Next gate (A9.18):** diverse human-annotated English/Mandarin/bilingual speech, overlaps, music, meeting audio, model transfer size, first and cached run on physical iPhone and Android, independently computed DER. Until passed, keep manual SRT/VTT and manual speaker labels live, and reject unsupported/low-confidence auto timestamps.
