# API Specification

Base: `{base_url}/api/…` (rewritten to `api/index.php?r=…`; the `?r=` form always works). All responses are JSON `{ "ok": true|false, … }`. Errors: `{ "ok": false, "error": "…" }` with an HTTP status. Public endpoints never need login and never need location permission.

Public visibility rules are applied inside the repository layer, not per endpoint — see DATA_VERIFICATION.md.

## GET endpoints

| Endpoint | Parameters | Returns |
|---|---|---|
| `/establishments` | `q`, `barangay_id`, `category_id`, `zone`, `classification` (public/private), `essential`, `emergency`, `limit` (1–200, default 50), `offset` | `total`, `items[]` |
| `/establishments/{id}` | — | `item` incl. `contacts` (public only) and `hours`; 404 if not public |
| `/establishments/nearby` | `lat`, `lng` (required), `radius` m (50–20000, default 1000), `limit` (1–100), plus filters | `distance_kind: "straight_line"`, `items[]` with distance in metres |
| `/map/markers` | filters | `items[]` — only plottable points (coords present and checked, unless admin allows unchecked) |
| `/categories` | — | active categories (localised name, icon, colour, essential flag) |
| `/barangays` | — | active barangays; boundary GeoJSON only if admin supplied it with a source |
| `/zones` | `barangay_id` | distinct zones/puroks in use |
| `/facilities` | filters | public facilities/services |
| `/config` | — | site title, tile URL + attribution, `tile_is_production_ok`, routing mode, external-nav template, kiosk idle seconds, sample-banner flag. No secrets |

### Establishment item (public shape)
`id, name, description, barangay_id, barangay, category{id,slug,name,icon,color,essential}, address, landmark, zone, classification, status, verified, last_verified_at, hours_note, photo_url, is_sample, has_coordinates, plottable, coords_verified, lat, lng, emergency, facility_type, services`

- `lat`/`lng` are `null` unless `plottable`.
- `emergency` is true only if staff verified the emergency flag.
- `status` is `verified` or `pending_verification`; clients must show the "awaiting verification" label for the latter.

### Nearby
Distances are great-circle (haversine) straight-line metres, **not** walking/driving distance or time. The response carries `distance_kind: "straight_line"` so clients can label it.

## POST `/reports`
Public suggestion/correction. **Never publishes anything**; creates a pending request for staff.

Headers: `X-CSRF-Token: <token from the page>` (or `csrf` in the body). Body JSON (or form):
`request_type` = `new` | `correction` | `report_incorrect`; `establishment_id` (not for `new`); `barangay_id` (for `new`); optional `name, category_id, address, landmark, zone, phone, message, lat, lng, submitter_name, submitter_contact`; honeypot field `website` must be empty.

Responses: `201 ok`, `403` bad CSRF, `404` unknown establishment/barangay, `413` payload > 8000 bytes, `422` validation, `429` over the per-visitor daily limit (setting `reports_per_ip_per_day`).
A filled honeypot returns a normal-looking success without storing anything.

## Not provided
No write endpoints for staff: staff use server-rendered pages with session login and CSRF tokens. No routing endpoint (`osrm` not implemented).
