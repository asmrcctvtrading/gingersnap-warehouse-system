<#
.SYNOPSIS
    PickleCam Cleanup - Old Recording Remover
    Runs once daily via Windows Task Scheduler (e.g., 2 AM)

.DESCRIPTION
    Tasks:
    1. Find recordings older than N days
    2. Delete video files from disk
    3. Mark recordings as deleted in database (soft delete)
    4. Log cleanup activity

    Uses direct MySQL database connections for data access
#>

param(
    [int]$Days = 7,
    [string]$ConfigPath = "C:\xampp\htdocs\picklecam\scripts\recording_config.ps1"
)

# Load configuration
if (-not (Test-Path $ConfigPath)) {
    Write-Error "Configuration file not found: $ConfigPath"
    exit 1
}

. $ConfigPath

if (-not (Validate-RecordingConfig)) {
    Log-Message -Level "ERROR" -Message "Configuration validation failed"
    exit 1
}

# ===== DATABASE CONNECTION (cPanel PHP Proxy) =====
$dbProxyUrl = "https://asmrcctvtechnologies.com/picklecam/query_db.php"
$dbProxySecret = "CEOSJH4g8Wsl30n7jT1aq52BbuIRP6xp"

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
$cleanupLog = @()

try {
    Log-Message -Level "INFO" -Message "=== Cleanup Process Started (retaining recordings >$Days days) ==="

    # ===== TASK 1: FETCH OLD RECORDINGS FROM DATABASE =====
    Log-Message -Level "DEBUG" -Message "Fetching recordings older than $Days days from database..."

    $cutoffDate = (Get-Date).AddDays(-$Days).ToString("yyyy-MM-dd HH:mm:ss")
    $query = @"
SELECT
    id,
    video_file_path,
    completed_at
FROM recording_scheduled
WHERE completed_at IS NOT NULL
    AND completed_at < @cutoff_date
    AND deleted_at IS NULL
ORDER BY completed_at ASC
"@

    $oldRecordings = Query-Database -Query $query -Parameters @{ cutoff_date = $cutoffDate }

    if (-not $oldRecordings) {
        $oldRecordings = @()
    }

    Log-Message -Level "INFO" -Message "Found $($oldRecordings.Count) recordings to clean up (older than $Days days)"

    $deletedCount = 0
    $failedCount = 0
    $totalSize = 0

    # ===== TASK 2: DELETE EACH OLD RECORDING =====
    foreach ($recording in $oldRecordings) {
        try {
            $videoFile = $recording.video_file_path
            $recordingID = $recording.id

            # Attempt to delete file from disk
            $fileDeleted = $false
            $fileSizeBytes = 0

            if ($videoFile -and (Test-Path $videoFile)) {
                try {
                    $file = Get-Item $videoFile
                    $fileSizeBytes = $file.Length

                    Remove-Item $videoFile -Force -ErrorAction Stop
                    $fileDeleted = $true

                    Log-Message -Level "INFO" -Message "Deleted file: $videoFile ($($fileSizeBytes / 1MB) MB)"
                    $cleanupLog += "[FILE-DELETE] Recording $recordingID - File deleted"

                } catch {
                    Log-Message -Level "WARNING" -Message "Failed to delete file: $videoFile - $_"
                    $cleanupLog += "[FILE-ERROR] Recording $recordingID - Could not delete file"
                }
            } else {
                if ($videoFile) {
                    Log-Message -Level "WARNING" -Message "Video file not found on disk: $videoFile for recording $recordingID"
                }
                $cleanupLog += "[FILE-SKIP] Recording $recordingID - File not found"
            }

            # ===== SOFT DELETE IN DATABASE =====
            # Update database to mark as deleted
            $updateQuery = "UPDATE recording_scheduled SET deleted_at = @deleted_at WHERE id = @id"
            $params = @{
                id = $recordingID
                deleted_at = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
            }

            $deleteResult = Execute-DatabaseQuery -Query $updateQuery -Parameters $params

            if ($deleteResult) {
                Log-Message -Level "INFO" -Message "Marked recording $recordingID as deleted in database"
                $cleanupLog += "[DB-DELETE] Recording $recordingID - Marked deleted"
                $deletedCount++
                $totalSize += $fileSizeBytes
            } else {
                Log-Message -Level "ERROR" -Message "Failed to delete recording $recordingID from database"
                $cleanupLog += "[DB-ERROR] Recording $recordingID - Database delete failed"
                $failedCount++
            }

        } catch {
            $errorMsg = "Exception processing recording $($recording.id): $_"
            Log-Message -Level "ERROR" -Message $errorMsg
            $cleanupLog += "[ERROR] Recording $($recording.id) - Exception: $_"
            $failedCount++
        }
    }

    # ===== CLEANUP THUMBNAIL DIRECTORY =====
    try {
        $thumbnailDir = [System.IO.Path]::Combine($VIDEO_STORAGE_PATH, "thumbnails")

        if (Test-Path $thumbnailDir) {
            Log-Message -Level "DEBUG" -Message "Cleaning up old thumbnail files..."

            $thumbnailCutoffDate = (Get-Date).AddDays(-$Days)
            $oldThumbnails = Get-ChildItem $thumbnailDir -Filter "*.jpg" |
                Where-Object { $_.CreationTime -lt $thumbnailCutoffDate }

            $thumbnailDeletedCount = 0
            foreach ($thumb in $oldThumbnails) {
                try {
                    Remove-Item $thumb.FullName -Force
                    $thumbnailDeletedCount++
                } catch {
                    Log-Message -Level "WARNING" -Message "Failed to delete thumbnail: $($thumb.Name)"
                }
            }

            if ($thumbnailDeletedCount -gt 0) {
                Log-Message -Level "INFO" -Message "Cleaned up $thumbnailDeletedCount old thumbnail files"
                $cleanupLog += "[THUMBNAIL] Deleted $thumbnailDeletedCount old thumbnails"
            }
        }
    } catch {
        Log-Message -Level "WARNING" -Message "Thumbnail cleanup failed: $_"
    }

    # ===== LOG SUMMARY =====
    $duration = (Get-Date) - $startTime
    $totalSizeMB = [Math]::Round($totalSize / 1MB, 2)
    $summary = "Cleanup complete: Deleted=$deletedCount, Failed=$failedCount, SpaceFreed=$($totalSizeMB) MB, Duration=$($duration.TotalSeconds)s"

    Log-Message -Level "INFO" -Message $summary

    if ($cleanupLog.Count -gt 0) {
        Log-Message -Level "INFO" -Message "Cleanup Details: $($cleanupLog -join ' | ')"
    }

    Write-Output "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] $summary"
    if ($cleanupLog.Count -gt 0) {
        Write-Output ($cleanupLog -join "`n")
    }

    # ===== RETENTION POLICY LOG =====
    Log-Message -Level "INFO" -Message "Retention policy: Keep recordings for $Days days, delete older files"

} catch {
    $errorMsg = "Fatal error in cleanup_old_recordings: $_"
    Log-Message -Level "ERROR" -Message $errorMsg
    Write-Error $errorMsg
    exit 1
}
