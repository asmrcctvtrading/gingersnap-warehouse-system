# Data Verification Policy

"Verified" in this system means **a signed-in staff member with the verifier or admin role recorded evidence** for the record. It is not an official government endorsement, and the app never says it is.

## Record statuses
| Status | Public? | Meaning |
|---|---|---|
| `pending_verification` | Only if *Show records awaiting verification* is on; always labelled "Awaiting verification" | Entered or approved, not yet checked |
| `verified` | Yes | Verifier recorded evidence (who, when, how) |
| `needs_review` | No | Reported wrong or flagged by staff |
| `inactive` | No | Closed or removed; kept for history |

## Separate verification flags
- **Coordinates** (`coords_verified`): needs coordinates present and evidence (e.g. "checked on site with GPS", "matched to satellite imagery + landmark"). Unchecked coordinates are not plotted unless an admin enables it. Changing coordinates resets this flag.
- **Emergency flag** (`facilities.emergency_verified_at`): the public site shows a record as an emergency service only after staff verify it with evidence.
- **Contacts:** only contacts marked public are returned by the API.
- **Boundary:** shown only if an admin supplied GeoJSON *and* a source; `boundary_verified` records barangay confirmation. The app never draws or guesses a boundary.

## Workflow
1. Encoder enters the record → `pending_verification`.
2. Verifier checks it (visit, phone call, document, owner confirmation) and records the evidence text → `verified`. A verification entry and audit-log row are written.
3. If a non-verifier edits a verified record, it returns to `pending_verification`.
4. Public suggestions arrive as requests. Approving a "new" request creates a pending record with unchecked coordinates; approving a correction flags the record `needs_review` for staff to apply.
5. Re-verify periodically (suggested: yearly, and for emergency services every six months). `last_verified_at` is shown to the public.

## Sample data
Everything in `sample_data.sql` is fictional, uses fake phone numbers, and is flagged `is_sample`. Public pages show a banner while sample records exist. Remove with Settings → *Remove sample data* before real use.

## Data collection rules
Never copy coordinates, hours or phone numbers from memory, social media or unverified web pages into verified records. If you do not know a value, leave it blank.
