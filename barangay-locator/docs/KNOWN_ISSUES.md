# Known Issues and Limitations

Status as of the build date: **no code has been executed.** Items marked ⚠ may be real bugs waiting to be found.

## Verification gaps
1. ⚠ **No PHP, MySQL or shell was available in the build environment** (sub-agent and shell tools failed with "model temporarily unavailable"). Syntax, SQL and runtime behaviour are unchecked. Expect a few typos on first run; run `php -l` on every file and the tests first.
2. The multi-role plan was carried out sequentially by one author, not by parallel sub-agents (see AGENT_TASKS.md). There was no independent review.
3. ⚠ **Leaflet SRI hashes** in the CDN fallback were written from memory and are unverified. If the browser console shows an integrity error, download Leaflet 1.9.4 and put it in `assets/vendor/leaflet/` (the app prefers that copy), or correct the hashes from leafletjs.com.

## Functional limits
4. **Routing:** only hand-off to an external map app is implemented. `routing_provider = osrm` is *not* implemented. "Nearby" distances are straight-line and labelled so.
5. **Tiles:** the default is the public OpenStreetMap tile server, suitable for development only. A busy public site or kiosk needs a provider whose terms allow it (Settings → Map tiles).
6. **Correction requests** from the public only flag the record `needs_review`; staff apply the change manually.
7. **Photos:** one image per establishment; no resizing (limit 2 MB, jpg/png/webp).
8. **Operating hours** are stored but the UI does not compute "open now" across holidays or special schedules.
9. **No password-reset e-mail.** An admin resets passwords in Users.
10. **Rate limiting** relies on the database (per-IP hash per day for submissions; per-IP failed-login counts). Behind a shared proxy/CDN, `client_ip()` may see one IP for everyone.
11. **Boundaries:** none supplied. Boundary display works only when an admin pastes GeoJSON with a named source.

## Technical risks
12. ⚠ Some SQL uses double-quoted string literals; this breaks if MySQL runs with `ANSI_QUOTES`. Default cPanel MySQL does not. Replace with single quotes if it does.
13. **The installer is open until it finishes.** Run it immediately after upload, then delete `install/`.
14. CSP allows `https://unpkg.com` for the Leaflet fallback and `style-src 'unsafe-inline'`; tighten both after vendoring Leaflet.
15. Sessions use PHP's file handler; some shared hosts purge session files aggressively, causing unexpected logouts.
16. No automated browser/accessibility testing was possible; accessibility is by design (labels, focus, contrast, large touch targets) but unmeasured.
