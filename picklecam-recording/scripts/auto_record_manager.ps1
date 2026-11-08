<#
.SYNOPSIS
    PickleCam Auto Record Manager
    Runs every 1 minute via Windows Task Scheduler

.DESCRIPTION
    Tasks:
    1. Start recordings 5 minutes before scheduled_start
    2. Stop recordings when scheduled_end is passed
    3. Kill stale processes (>8 hours, likely crashed)

    Uses PickleCam REST API instead of direct DB connections
#>

param(
    [string]$ConfigPath = "C:\xampp\htdocs\picklecam\scripts\recording_config.ps1"
)

# Load configuration
if (-not (Test-Path $ConfigPath)) {
    Write-Error "Configuration file not found: $ConfigPath"
    exit 1
}

. $ConfigPath

# Validate configuration
if (-not (Validate-RecordingConfig)) {
    Log-Message -Level "ERROR" -Message "Configuration validation failed"
    exit 1
}

# ===== DATABASE CONNECTION (cPanel PHP Proxy) =====
$dbProxyUrl = "https://asmrcctvtechnologies.com/picklecam/query_db.php"
$dbProxySecret = "CEOSJH4g8Wsl30n7jT1aq52BbuIRP6xp"

# ===== HELPER FUNCTIONS (defined before use) =====

function Start-RecordingSession {
    param(
        [int]$RecordingID,
        [int]$CameraID,
        [int]$CourtID,
        [string]$ScheduledStart,
        [string]$ScheduledEnd
    )

    try {
        Log-Message -Level "INFO" -Message "Starting recording $RecordingID for camera $CameraID"

        # Use live NVR RTSP stream (Hikvision NVR at 192.168.100.41)
        # Stream path: /Streaming/Channels/10X (X = channel number: 1-8 → 101-108)
        # Example: Channel 1 = 101, Channel 2 = 102, etc.
        $rtspUrl = "rtsp://admin:User13919@192.168.100.41:554/Streaming/Channels/101"

        # Create output path
        $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
        $outputFile = "$RECORDING_OUTPUT_PATH\recording_${RecordingID}_${timestamp}.mp4"

        # Ensure directory exists
        $outputDir = Split-Path $outputFile
        if (-not (Test-Path $outputDir)) {
            New-Item -ItemType Directory -Path $outputDir -Force | Out-Null
        }

        # Build ffmpeg command
        $ffmpegArgs = @(
            "-rtsp_transport", "tcp",
            "-i", $rtspUrl,
            "-c:v", "libx264",
            "-b:v", "8000k",
            "-preset", "fast",
            "-c:a", "aac",
            "-b:a", "128k",
            "-y",
            $outputFile
        )

        # Start ffmpeg process
        $process = Start-Process -FilePath "$FFMPEG_PATH\ffmpeg.exe" `
                                 -ArgumentList $ffmpegArgs `
                                 -NoNewWindow `
                                 -PassThru `
                                 -ErrorAction Stop

        if (-not $process) {
            return @{
                success = $false
                error = "Failed to start ffmpeg process"
            }
        }

        Log-Message -Level "INFO" -Message "Recording $RecordingID started with PID $($process.Id), output: $outputFile"

        return @{
            success = $true
            pid = $process.Id
            output_file = $outputFile
            message = "Recording started successfully"
        }
    } catch {
        return @{
            success = $false
            error = $_.Exception.Message
        }
    }
}

function Stop-RecordingSession {
    param(
        [int]$RecordingID,
        [int]$FFmpegPID
    )

    try {
        if (-not $FFmpegPID) {
            Log-Message -Level "WARNING" -Message "No PID for recording $RecordingID, cannot stop"
            return @{ success = $false; error = "No PID provided" }
        }

        Log-Message -Level "INFO" -Message "Stopping recording $RecordingID (PID: $FFmpegPID)"

        # Try graceful shutdown first (send 'q' to stdin)
        $process = Get-Process -Id $FFmpegPID -ErrorAction SilentlyContinue
        if ($process) {
            $process.StandardInput.WriteLine("q")
            $process.WaitForExit(5000)
        }

        # If still running, force kill
        $process = Get-Process -Id $FFmpegPID -ErrorAction SilentlyContinue
        if ($process) {
            Stop-ProcessByID -ProcessID $FFmpegPID -Force $true
        }

        return @{
            success = $true
            duration = 0
            message = "Recording stopped successfully"
        }
    } catch {
        return @{
            success = $false
            error = $_.Exception.Message
        }
    }
}

function Query-Database {
    param(
        [string]$Query,
        [hashtable]$Parameters = @{}
    )

    try {
        # Replace parameters in query (simple substitution for @key values)
        $processedQuery = $Query
        foreach ($key in $Parameters.Keys) {
            $value = $Parameters[$key]
            # Handle different types for parameter substitution
            if ($value -is [DateTime]) {
                $value = $value.ToString("yyyy-MM-dd HH:mm:ss")
            }
            $value = $value -replace "'", "''"  # Escape single quotes
            $processedQuery = $processedQuery -replace "@$key", "'$value'"
        }

        $encodedQuery = [System.Uri]::EscapeDataString($processedQuery)
        $url = "$dbProxyUrl`?secret=$dbProxySecret&query=$encodedQuery"

        $response = Invoke-RestMethod -Uri $url -TimeoutSec 30 -ErrorAction Stop

        if ($response.success) {
            return $response.data
        } else {
            Log-Message -Level "ERROR" -Message "Query failed: $($response.error)"
            return @()
        }
    } catch {
        Log-Message -Level "ERROR" -Message "Database query error: $_"
        return @()
    }
}

function Execute-DatabaseQuery {
    param(
        [string]$Query,
        [hashtable]$Parameters = @{}
    )

    try {
        # Replace parameters in query
        $processedQuery = $Query
        foreach ($key in $Parameters.Keys) {
            $value = $Parameters[$key]
            # Handle different types for parameter substitution
            if ($value -is [DateTime]) {
                $value = $value.ToString("yyyy-MM-dd HH:mm:ss")
            }
            $value = $value -replace "'", "''"  # Escape single quotes
            $processedQuery = $processedQuery -replace "@$key", "'$value'"
        }

        $encodedQuery = [System.Uri]::EscapeDataString($processedQuery)
        $url = "$dbProxyUrl`?secret=$dbProxySecret&query=$encodedQuery"

        $response = Invoke-RestMethod -Uri $url -TimeoutSec 30 -ErrorAction Stop

        if ($response.success) {
            return $true
        } else {
            Log-Message -Level "ERROR" -Message "Update failed: $($response.error)"
            return $false
        }
    } catch {
        Log-Message -Level "ERROR" -Message "Database error: $_"
        return $false
    }
}

$startTime = Get-Date
$cycleLog = @()

try {
    Log-Message -Level "INFO" -Message "=== Auto Record Manager cycle started ==="

    # ===== TASK 1: FETCH PENDING RECORDINGS =====
    Log-Message -Level "DEBUG" -Message "Fetching pending and active recordings from database..."
    $query = "SELECT id, reservation_id, court_id, camera_id, scheduled_start, scheduled_end, status, activated_at, ffmpeg_pid FROM recording_scheduled WHERE (status = 'pending' OR status = 'active') AND deleted_at IS NULL ORDER BY scheduled_start ASC"
    $allRecordings = Query-Database -Query $query

    if (-not $allRecordings) {
        $allRecordings = @()
    }

    $pendingRecordings = @($allRecordings | Where-Object { $_.status -eq 'pending' })
    $activeRecordings = @($allRecordings | Where-Object { $_.status -eq 'active' })

    Log-Message -Level "INFO" -Message "Found $($pendingRecordings.Count) pending recordings and $($activeRecordings.Count) active recordings"

    # ===== TASK 1A: START RECORDINGS =====
    foreach ($recording in $pendingRecordings) {
        try {
            if ($recording.status -ne "pending") {
                continue
            }

            $scheduledStart = [DateTime]::Parse($recording.scheduled_start)
            $scheduledEnd = [DateTime]::Parse($recording.scheduled_end)
            $now = Get-Date
            $minutesUntilStart = ($scheduledStart - $now).TotalMinutes

            # Start if within buffer window and start time not in past
            if ($minutesUntilStart -le $RECORD_START_BUFFER_MINUTES -and $minutesUntilStart -gt -1) {
                $result = Start-RecordingSession -RecordingID $recording.id `
                                                -CameraID $recording.camera_id `
                                                -CourtID $recording.court_id `
                                                -ScheduledStart $recording.scheduled_start `
                                                -ScheduledEnd $recording.scheduled_end

                if ($result.success) {
                    # Update database status to active
                    $updateQuery = "UPDATE recording_scheduled SET status = 'active', ffmpeg_pid = @ffmpeg_pid, activated_at = @activated_at WHERE id = @id"
                    $params = @{
                        id = $recording.id
                        ffmpeg_pid = $result.pid
                        activated_at = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
                    }
                    Execute-DatabaseQuery -Query $updateQuery -Parameters $params
                    $cycleLog += "[START] Recording $($recording.id) started (PID: $($result.pid))"
                } else {
                    $cycleLog += "[FAIL] Recording $($recording.id) failed to start: $($result.error)"

                    # Update database with failure status
                    $updateQuery = "UPDATE recording_scheduled SET status = 'failed', error_message = @error_message WHERE id = @id"
                    $params = @{
                        id = $recording.id
                        error_message = $result.error
                    }
                    Execute-DatabaseQuery -Query $updateQuery -Parameters $params
                }
            }
        } catch {
            $cycleLog += "[ERROR] Exception processing recording $($recording.id): $_"
            Log-Message -Level "ERROR" -Message "Exception processing recording $($recording.id): $_"
        }
    }

    # ===== TASK 2: STOP ACTIVE RECORDINGS =====
    foreach ($recording in $activeRecordings) {
        try {
            $scheduledEnd = [DateTime]::Parse($recording.scheduled_end)
            $now = Get-Date

            # Stop if end time has passed
            if ($now -ge $scheduledEnd) {
                $result = Stop-RecordingSession -RecordingID $recording.id `
                                               -FFmpegPID $recording.ffmpeg_pid

                if ($result.success) {
                    # Update database status to completed
                    $updateQuery = "UPDATE recording_scheduled SET status = 'completed', completed_at = @completed_at WHERE id = @id"
                    $params = @{
                        id = $recording.id
                        completed_at = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
                    }
                    Execute-DatabaseQuery -Query $updateQuery -Parameters $params
                    $cycleLog += "[STOP] Recording $($recording.id) stopped (duration: $($result.duration)s)"
                } else {
                    $cycleLog += "[FAIL] Recording $($recording.id) failed to stop: $($result.error)"
                }
            }
        } catch {
            $cycleLog += "[ERROR] Exception stopping recording $($recording.id): $_"
            Log-Message -Level "ERROR" -Message "Exception stopping recording $($recording.id): $_"
        }
    }

    # ===== TASK 3: KILL STALE PROCESSES =====
    Log-Message -Level "DEBUG" -Message "Checking for stale processes (>$STALE_THRESHOLD_HOURS hours)..."

    foreach ($recording in $activeRecordings) {
        try {
            if ($null -eq $recording.activated_at -or $recording.activated_at -eq [DBNull]::Value) {
                continue
            }

            $activatedAt = [DateTime]::Parse($recording.activated_at)
            $now = Get-Date
            $hoursActive = ($now - $activatedAt).TotalHours

            if ($hoursActive -gt $STALE_THRESHOLD_HOURS) {
                Log-Message -Level "WARNING" -Message "Killing stale process $($recording.ffmpeg_pid) (recording $($recording.id))"
                Stop-ProcessByID -ProcessID $recording.ffmpeg_pid -Force $true

                # Update database with stale kill status
                $updateQuery = "UPDATE recording_scheduled SET status = 'failed', error_message = @error_message, completed_at = @completed_at WHERE id = @id"
                $params = @{
                    id = $recording.id
                    error_message = "Stale process killed (>$STALE_THRESHOLD_HOURS hours)"
                    completed_at = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
                }
                Execute-DatabaseQuery -Query $updateQuery -Parameters $params

                $cycleLog += "[STALE] Process $($recording.ffmpeg_pid) killed for recording $($recording.id)"
            }
        } catch {
            $cycleLog += "[ERROR] Exception checking stale process for recording $($recording.id): $_"
            Log-Message -Level "ERROR" -Message "Exception checking stale process: $_"
        }
    }

    # ===== LOG CYCLE SUMMARY =====
    $duration = (Get-Date) - $startTime
    $summary = "Cycle complete: Started=$(@($pendingRecordings | Where {$_.status -eq 'pending'}).Count), Active=$($activeRecordings.Count), Duration=$($duration.TotalSeconds)s"

    Log-Message -Level "INFO" -Message $summary

    if ($cycleLog.Count -gt 0) {
        Log-Message -Level "INFO" -Message "Cycle Details: $($cycleLog -join ' | ')"
    }

    Write-Output "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] $summary"
    if ($cycleLog.Count -gt 0) {
        Write-Output ($cycleLog -join "`n")
    }

} catch {
    $errorMsg = "Fatal error in auto_record_manager: $_"
    Log-Message -Level "ERROR" -Message $errorMsg
    Write-Error $errorMsg
    exit 1
}
