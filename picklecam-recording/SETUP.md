# PickleCam Video Recording System - Setup Guide

## Status: RTSP Stream Connected ✓
**Date:** November 8, 2026  
**NVR:** Hikvision DS-7608NXI-K2/8P(E) at 192.168.100.41:554  
**Test Result:** Successfully captured 1.54 MB test recording from Channel 1

---

## Correct RTSP URL Format

```
rtsp://admin:User13919@192.168.100.41:554/Streaming/Channels/10X
```

Where `X` = channel number (1-8):
- Court 1 → Channel 101
- Court 2 → Channel 102
- Court 3 → Channel 103
- Court 4 → Channel 104

---

## Database Updates Required

Run this SQL in cPanel phpMyAdmin:

```sql
UPDATE cameras SET stream_url = 'rtsp://admin:User13919@192.168.100.41:554/Streaming/Channels/101' WHERE court_id = 1;
UPDATE cameras SET stream_url = 'rtsp://admin:User13919@192.168.100.41:554/Streaming/Channels/102' WHERE court_id = 2;
UPDATE cameras SET stream_url = 'rtsp://admin:User13919@192.168.100.41:554/Streaming/Channels/103' WHERE court_id = 3;
UPDATE cameras SET stream_url = 'rtsp://admin:User13919@192.168.100.41:554/Streaming/Channels/104' WHERE court_id = 4;
```

Or use: `update_camera_urls.sql`

---

## PowerShell Automation Setup

**Location:** `C:\xampp\htdocs\picklecam\scripts\`

### Files:
1. **recording_config.ps1** - Central config (paths, ffmpeg, API credentials)
2. **auto_record_manager.ps1** - Runs every 1 min (starts/stops recordings)
3. **post_record_process.ps1** - Runs every 5 min (encode to 2000k, thumbnails)
4. **cleanup_old_recordings.ps1** - Runs daily 2 AM (delete videos older than 7 days)

### Windows Task Scheduler Jobs:
```powershell
# 1-minute cycle
schtasks /create /tn "PickleCam\AutoRecordManager" /tr "powershell -ExecutionPolicy Bypass -File C:\xampp\htdocs\picklecam\scripts\auto_record_manager.ps1" /sc minute /mo 1

# 5-minute cycle
schtasks /create /tn "PickleCam\PostRecordProcess" /tr "powershell -ExecutionPolicy Bypass -File C:\xampp\htdocs\picklecam\scripts\post_record_process.ps1" /sc minute /mo 5

# Daily 2 AM cleanup
schtasks /create /tn "PickleCam\CleanupOldRecordings" /tr "powershell -ExecutionPolicy Bypass -File C:\xampp\htdocs\picklecam\scripts\cleanup_old_recordings.ps1" /sc daily /st 02:00
```

---

## Next Steps

1. **Update Database** (via cPanel phpMyAdmin):
   - Run SQL statements from `update_camera_urls.sql`
   - Verify: SELECT * FROM cameras WHERE court_id BETWEEN 1 AND 4;

2. **Verify Recording Path**:
   - Check: `C:\xampp\htdocs\picklecam\videos\` exists and writable

3. **Create Test Booking**:
   - Go to PickleCam booking system
   - Create test reservation for Court 1 (any time)
   - Recording should auto-start 5 minutes before scheduled time

4. **Monitor Logs**:
   - Check: `C:\xampp\htdocs\picklecam\logs\recording.log`
   - Watch for "Recording started" messages

5. **Verify Post-Processing**:
   - After recording ends, check for:
     - Re-encoded video at lower bitrate
     - Thumbnail image
     - Download token in database

---

## Testing Checklist

- [x] RTSP stream accessible (test completed)
- [ ] Camera URLs updated in database
- [ ] Task Scheduler jobs created
- [ ] Test booking created and recording started
- [ ] Video file created in output directory
- [ ] Post-processing completes (encode + thumbnail)
- [ ] Download token generated
- [ ] Email notification sent (if configured)
- [ ] Video auto-deleted after 7 days

---

## Troubleshooting

**404 Not Found on RTSP:**
- Verify format: `/Streaming/Channels/10X` (not `/h264/chX` or other formats)
- Confirm credentials: admin / User13919
- Test with: `ffmpeg -rtsp_transport tcp -i rtsp://admin:User13919@192.168.100.41:554/Streaming/Channels/101 -t 1 -f null -`

**No videos being created:**
- Check output path exists: `C:\xampp\htdocs\picklecam\videos\`
- Check logs: `C:\xampp\htdocs\picklecam\logs\recording.log`
- Verify database has pending recordings: `SELECT * FROM recording_scheduled WHERE status = 'pending';`

**ffmpeg error:**
- Confirm installed at: `C:\ffmpeg\bin\ffmpeg.exe`
- Test: `ffmpeg -version`

---

## Configuration Files

### recording_config.ps1
- FFMPEG_PATH = C:\ffmpeg\bin
- VIDEO_STORAGE_PATH = C:\xampp\htdocs\picklecam\videos
- RECORD_START_BUFFER_MINUTES = 5
- FFMPEG_VIDEO_CODEC = libx265
- API_SECRET = CEOSJH4g8Wsl30n7jT1aq52BbuIRP6xp

### Database
- Host: cPanel (yitrmcft_picklecam)
- Proxy: https://asmrcctvtechnologies.com/picklecam/query_db.php
- Tables: recording_scheduled, video_download_logs, cameras

---

Generated: 2026-11-08 10:37 UTC
