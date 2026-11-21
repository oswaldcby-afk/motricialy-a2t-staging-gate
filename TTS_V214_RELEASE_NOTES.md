# TTS v2.14 — once-per-day failed-generation allowance (2026-11-21)

This is a limited **visitor quota credit**, not an OpenAI API cost refund.

Scope:
- Only when the server settles a generation as **estimated** because provider usage was unknown, or recovers a stale pending request after 600 seconds.
- The full reserved amount remains charged to daily/cycle/total budget tables.
- A single anonymous visitor identity may receive one daily quota-and-character credit per UTC day, shared across full and partial generations.
- Subsequent failures do not receive another allowance credit that day.
- No automatic retry, no change to API model, request rate limits, concurrent limit, $3/day, $60 operating cycle or $75 total.
- Known-cost overruns remain accounted; genuinely unpriced requests still fail closed.

Live files changed: `/tools/text-to-speech/api/budget.php` only.
- Before SHA-256: `62959341a9a0222be6db38b863da009dcd4b367d001de52c2ba790c2896142a5`
- After SHA-256: `9a8f5675da21053ef7a886693458375774c9adeb1e773ca52dd4472fafc18161`
- Original backup outside web root: `/home/u342033576/domains/motricialy.com/tts-budget-before-v214-20261121.php`

Release: Hostinger returned `TTS_V214_DEPLOY_OK ONE_DAILY_FAILED_REQUEST_GRACE=ON NO_FINANCIAL_REFUND=YES CAPS_UNCHANGED=YES BACKUP=YES`.
Isolated SQLite regression: 13 checks passed, including one-grace rule, second failure counted, cross-action rule, separate visitors, stale pending recovery, full-charge preservation and daily budget cap.
Post-deploy public API config: mode `openai-verified-api`, `unresolved=0`, `heldUsd=0`, original budgets unchanged.

Monitoring: ChatGPT conditional hourly automation watches public TTS API configuration, unresolved count, uptime and budget thresholds without paid generation. It cannot catch every transient outage between runs or prove actual speech quality.

Installer and isolated QA test were used only for release; source history remains in the branch's Git commit history.
