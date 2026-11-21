# Text to Speech v2.12 — budget reconciliation hotfix (2026-11-21)

Production issue: one failed 530-character generation entered `ambiguous` while the full reservation of $0.2306 was already charged conservatively. A global guard blocked every subsequent generation, despite available daily budget.

Corrective action: after inspecting read-only aggregates, backed up the SQLite ledger using a consistent VACUUM INTO snapshot and the original budget.php outside public_html. Reclassified exactly one stale ambiguous reservation as `estimated` without changing `reserved`, `charged`, `daily_charges`, or visitor quota. Changed budget.php so future requests with unknown provider usage are conservatively charged their full reserved amount as `estimated`, included in budget status totals and cap enforcement, instead of globally freezing TTS. Stale pending requests receive the same treatment. Genuine `overrun` still blocks new requests pending manual audit.

Production budget.php original SHA-256: `5fd37ac79a95fc73ba650b28ac3baf6631879645fb6d9c9e24cd22f73f877c81`

Production budget.php v2.12 SHA-256: `d79b15e15c3211b303c2ca68b8d3d6d577f6dc9cb20b4eb425840d87e16670ee`

Hostinger application: `TTS_V212_BUDGET_RESTORED CONSERVATIVE_ESTIMATE_USD=0.2306 BACKUPS=YES DAILY_SPEND_UNCHANGED=YES`.

Postfix smoke: normal public Text-to-Speech API, Ash, full generation, HTTP 200, actual reported cost $0.0016688. After test `unresolved=0`, `heldUsd=0` and no deployment cron tasks. OpenAI provider invoice for the failed request has **not** been verified; $0.2306 remains a conservative estimate rather than confirmed actual spend.

The one-shot diagnostic, installer, and smoke scripts were removed from the current release tree after use; they remain available in Git commit history for audit. No API keys or personal data were included.
