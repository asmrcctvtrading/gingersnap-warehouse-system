# Staff Guide

Sign in at `/admin/login.php`. Menu items depend on your role.

| Role | Can do |
|---|---|
| Encoder | View, add, edit establishments (edits to verified records send them back to "awaiting verification") |
| Verifier | Everything an encoder can, plus verify / flag / deactivate / reactivate, review public requests, view audit log, export CSV |
| Admin | Everything, plus categories, barangays, users, settings |

A user can be limited to one barangay (Users → Barangay scope). Scoped users only see and edit that barangay.

## Daily tasks
- **Add an establishment:** Establishments → Add. Required: name, barangay, category. Leave latitude/longitude blank if unknown. If the system warns about a similar name, check the existing record first and tick the acknowledgement only if it is a different place.
- **Coordinates:** paste decimal degrees (e.g. 14.5995, 120.9842 format). Never guess. After checking on site/imagery, a verifier uses *Verify coordinates* with evidence.
- **Verify:** open the record → Verification → *Verify* and write the evidence ("visited 12 Oct, owner confirmed hours"). Evidence is mandatory.
- **Emergency services:** tick "emergency" on the facility, then a verifier uses *Verify emergency* with evidence.
- **Requests:** Requests lists public submissions. Approve, reject, or leave. Nothing public changes until you approve and then verify.
- **Photos:** jpg/png/webp up to 2 MB.

## Admin tasks
- **Barangays:** add the real barangay; centre and boundary optional. Paste boundary GeoJSON only if you hold an official file, and fill in its source.
- **Categories:** the 20 defaults can be renamed, reordered, recoloured (hex colour) or hidden; add your own.
- **Users:** create accounts (password 10+ characters, letters and numbers). Reset a password by typing a new one in the row. You cannot disable or demote yourself.
- **Settings:** site title, language, kiosk idle time, visibility toggles, tile provider, directions template, remove sample data.
- **Export:** CSV of all records you can access. Contains non-public records; store it carefully.
- **Audit log:** who did what and when (create/update/verify/export/login failures).

## Good practice
Use individual accounts, not shared ones. Log out on shared computers. Back up the database before bulk changes (cPanel → Backup or phpMyAdmin export).
