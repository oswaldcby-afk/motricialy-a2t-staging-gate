# Motricialy Text to Speech — HTML / React Release Gate

Audit date: 2026-11-22. Production site: https://motricialy.com/tools/text-to-speech/

## Why this exists
A prior commercial-use FAQ update changed production `index.html` without changing the matching FAQ array inside the compiled React JavaScript. React hydration then showed the old questions. That regression was fixed in v2.21.1.

**Rule:** Do not declare an update complete solely because `index.html` or a separate CSS/JS enhancement was uploaded. Verify the compiled React app, raw HTML, and actual rendered DOM.

## Reusable read-only audit
File: `audit-tts-html-react.php`

It compares the *live* TTS `index.html` with the JS bundle actually referenced by the page. It checks:
- Exactly six FAQ questions and identical answers, with legal links
- Three How to Use steps, six Features and six Related Tools
- H1 uniqueness, SoftwareApplication / BreadcrumbList, analytics and Site Chrome
- Front-end 2,000 / 400 character limits and result-loading condition
- Voice-picker gender labels, active Generate bridge, result status, seekable players
- Versioned CSS files for editor height and header Generate button
- Replaced player/status scripts are not loaded

On a mismatch it prints a JSON report and exits nonzero. It is **read-only** and must not be placed at a publicly accessible endpoint.

Run on Hostinger, from this gated staging directory, with `php audit-tts-html-react.php`. If the release introduces a deliberate redesign, update the relevant audit expectations together with both HTML and React code.

## Browser checks (mocked API, zero OpenAI costs)
Run against the built React bundle in Chromium on desktop and mobile:
- After hydration, FAQ titles and legal links match raw HTML.
- Voice Picker shows 10 voices, Female (4), Male (6), no Neutral; switching voices works.
- Desktop Generate is aligned with Your text; mobile retains footer Generate.
- Generate click forwards to the actual React action; loading state and bar visible.
- Successful mock response displays non-clickable green Ready to download.
- Full voiceover and Section audio allow clicking/dragging seek bar, and playback head advances.

The 2026-11-22 offline simulated test passed at 1440px and 390px. It does not replace a live browser end-to-end test against real generated audio.

## Important source-code drift risk
The locally supplied **v2.4 React source** still contains old FAQ labels (Can I download MP3 and WAV? / Are the voice previews free?) and a 180-character limit. Production uses a newer patched compiled bundle (v2.21.1 with 400-character sections) plus separate UI enhancement scripts. A full rebuild from the older v2.4 source **can silently roll back the current fixes**.

Before the next full Vite/React rebuild, obtain/reconcile the latest canonical React source and commit all accepted v2.5–v2.21.1 UI/logic/content changes. Keep an up-to-date release source tree, not only a collection of production patch installers.

## Scope
This gate checks content and wiring. It does **not** certify commercial rights legally, guarantee OpenAI fidelity, or replace live tests of billing, generated audio, browser cache and mobile playback.
