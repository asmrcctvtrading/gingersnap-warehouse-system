# Kiosk and Operations

## Kiosk mode
Open `https://your-domain/barangay/?kiosk=1`. The choice is remembered for the browser session. Kiosk mode shows a "touch to start" screen, larger touch targets, hides staff links, and resets to the start page after the idle time (Settings → Kiosk idle reset, default 90 s). The kiosk never asks for location unless the user taps "Use my location".

### Windows kiosk startup (Chrome/Edge)
Create a shortcut or startup task:
```
"C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk --incognito --noerrdialogs --disable-pinch --overscroll-history-navigation=0 "https://your-domain/barangay/?kiosk=1"
```
Put the shortcut in `shell:startup`. Use a standard (non-admin) Windows account, disable sleep, and use Windows "Assigned access" if you need to lock the device to the browser. Use a production-suitable tile provider (see MAPPING_AND_GIS) because a kiosk generates steady tile traffic.

## Backups
- Weekly (daily if data changes often): cPanel → Backup → download the MySQL database backup, plus a copy of `uploads/`.
- Before upgrades or bulk edits: phpMyAdmin → Export → SQL.
- Keep at least one copy off the server. Test a restore occasionally on a staging database.

## Updates
Upload changed files over the existing ones; never overwrite `app/config.local.php` or `storage/`. Apply new SQL files from `database/migrations/` in order.

## Monitoring
Check cPanel error logs after deploying. Review the audit log for repeated failed logins.
