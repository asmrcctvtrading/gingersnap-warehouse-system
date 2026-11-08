<#
.SYNOPSIS
    PickleCam Post-Recording Processor
    Runs every 5 minutes via Windows Task Scheduler

.DESCRIPTION
    Tasks:
    1. Fetch completed recordings pending post-processing
    2. Encode video (re-encode if needed for optimization)
    3. Generate thumbnail
    4. Upload to cloud (optional)
    5. Update processing status

    Uses direct MySQL database connections for data access
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

# ===== HELPER FUNCTIONS (defined before use) =====

function Process-VideoEncoding {
    param(
        [string]$InputFile,
        [string]$OutputCodec,
        [string]$AudioCodec
    )

    try {
        if (-not (Test-Path $FFMPEG_PATH)) {
            throw "ffmpeg not found at: $FFMPEG_PATH"
        }

        $outputFile = [System.IO.Path]::ChangeExtension($InputFile, ".mp4")

        # Build ffmpeg command for re-encoding
        $ffmpegArgs = @(
            "-i", $InputFile,
            "-c:v", $OutputCodec,
            "-c:a", $AudioCodec,
            "-b:a", $FFMPEG_AUDIO_BITRATE,
            "-y",
            $outputFile
        )

        Log-Message -Level "DEBUG" -Message "Starting video encoding with ffmpeg..."

        $process = Start-Process -FilePath "$FFMPEG_PATH\ffmpeg.exe" `
                                 -ArgumentList $ffmpegArgs `
                                 -NoNewWindow `
                                 -PassThru `
                                 -RedirectStandardOutput "$env:TEMP\ffmpeg_out.txt" `
                                 -RedirectStandardError "$env:TEMP\ffmpeg_err.txt"

        $process.WaitForExit()

        if ($process.ExitCode -ne 0) {
            $error = Get-Content "$env:TEMP\ffmpeg_err.txt" -Raw
            throw "ffmpeg exited with code $($process.ExitCode): $error"
        }

        if (Test-Path $outputFile) {
            Log-Message -Level "INFO" -Message "Video encoding completed: $outputFile"
            return $outputFile
        } else {
            throw "Output file not created after encoding"
        }

    } catch {
        Log-Message -Level "ERROR" -Message "Video encoding failed: $_"
        return $null
    }
}

function Generate-Thumbnail {
    param(
        [string]$InputFile,
        [int]$RecordingID
    )

    try {
        if (-not (Test-Path $FFMPEG_PATH)) {
            throw "ffmpeg not found at: $FFMPEG_PATH"
        }

        $thumbnailDir = [System.IO.Path]::Combine($VIDEO_STORAGE_PATH, "thumbnails")
        if (-not (Test-Path $thumbnailDir)) {
            New-Item -Path $thumbnailDir -ItemType Directory -Force | Out-Null
        }

        $thumbnailFile = [System.IO.Path]::Combine($thumbnailDir, "recording_$($RecordingID)_thumb.jpg")

        # Generate thumbnail at 5 seconds into video
        $ffmpegArgs = @(
            "-i", $InputFile,
            "-ss", "00:00:05",
            "-vframes", "1",
            "-vf", "scale=320:-1",
            "-y",
            $thumbnailFile
        )

        $process = Start-Process -FilePath "$FFMPEG_PATH\ffmpeg.exe" `
                                 -ArgumentList $ffmpegArgs `
                                 -NoNewWindow `
                                 -PassThru `
                                 -Wait

        if ($process.ExitCode -eq 0 -and (Test-Path $thumbnailFile)) {
            Log-Message -Level "DEBUG" -Message "Thumbnail generated: $thumbnailFile"
            return $thumbnailFile
        } else {
            throw "Failed to generate thumbnail"
        }

    } catch {
        Log-Message -Level "WARNING" -Message "Thumbnail generation failed: $_"
        return $null
    }
}

function Upload-VideoToCPanel {
    param(
        [string]$LocalFilePath,
        [int]$RecordingID
    )

    try {
        # FTP Configuration
        $ftpHost = "ftp.asmrcctvtechnologies.com"
        $ftpUser = "yitrmcft"
        $ftpPass = "p@`$`$w0rd@123"
        $ftpRemotePath = "/public_html/picklecam/videos"

        if (-not (Test-Path $LocalFilePath)) {
            throw "Local file not found: $LocalFilePath"
        }

        $fileName = [System.IO.Path]::GetFileName($LocalFilePath)
        $fileSize = (Get-Item $LocalFilePath).Length / 1MB

        Log-Message -Level "INFO" -Message "Uploading $fileName ($([math]::Round($fileSize, 2)) MB) to cPanel FTP..."

        # Create FTP credentials and request
        $credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $ftpUri = "ftp://$ftpHost$ftpRemotePath/$fileName"

        $ftpRequest = [System.Net.FtpWebRequest]::Create($ftpUri)
        $ftpRequest.Credentials = $credentials
        $ftpRequest.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $ftpRequest.UseBinary = $true
        $ftpRequest.Timeout = 30000

        # Read and upload file
        $fileBytes = [System.IO.File]::ReadAllBytes($LocalFilePath)
        $ftpRequest.ContentLength = $fileBytes.Length

        $uploadStream = $ftpRequest.GetRequestStream()
        $uploadStream.Write($fileBytes, 0, $fileBytes.Length)
        $uploadStream.Close()

        # Get response
        $ftpResponse = $ftpRequest.GetResponse()
        $ftpResponse.Close()

        # Return remote path for database storage (web URL format for download feature)
        $remotePath = "https://asmrcctvtechnologies.com/picklecam/videos/$fileName"
        Log-Message -Level "INFO" -Message "Upload successful: $remotePath"

        return $remotePath

    } catch {
        Log-Message -Level "ERROR" -Message "FTP upload failed for recording $RecordingID`: $_"
        return $null
    }
}

$startTime = Get-Date
$processLog = @()

try {
    Log-Message -Level "INFO" -Message "=== Post-Record Processor started ==="

    # ===== TASK 1: FETCH COMPLETED RECORDINGS PENDING PROCESSING =====
    Log-Message -Level "DEBUG" -Message "Fetching completed recordings pending post-processing from database..."
    $query = @"
SELECT
    id,
    court_id,
    camera_id,
    video_file_path,
    processing_status
FROM recording_scheduled
WHERE status = 'completed'
    AND (processing_status IS NULL OR processing_status = 'pending')
    AND deleted_at IS NULL
    AND video_file_path IS NOT NULL
ORDER BY completed_at ASC
"@

    $completedRecordings = Query-Database -Query $query

    if (-not $completedRecordings) {
        $completedRecordings = @()
    }

    Log-Message -Level "INFO" -Message "Found $($completedRecordings.Count) recordings to process"

    # ===== TASK 2: PROCESS EACH RECORDING =====
    foreach ($recording in $completedRecordings) {
        try {
            Log-Message -Level "INFO" -Message "Processing recording $($recording.id) from file: $($recording.video_file_path)"

            $videoFile = $recording.video_file_path

            # Validate file exists
            if (-not (Test-Path $videoFile)) {
                Log-Message -Level "WARNING" -Message "Video file not found: $videoFile for recording $($recording.id)"

                # Update status to failed
                $updateQuery = "UPDATE recording_scheduled SET processing_status = 'failed', error_message = @error_message WHERE id = @id"
                $params = @{
                    id = $recording.id
                    error_message = "Video file not found: $videoFile"
                }
                Execute-DatabaseQuery -Query $updateQuery -Parameters $params

                $processLog += "[SKIP] Recording $($recording.id) - file not found"
                continue
            }

            # ===== SUB-TASK 2A: RE-ENCODE VIDEO (Optional Optimization) =====
            $processedFile = Process-VideoEncoding -InputFile $videoFile `
                                                   -OutputCodec $FFMPEG_VIDEO_CODEC `
                                                   -AudioCodec $FFMPEG_AUDIO_CODEC

            if (-not $processedFile) {
                Log-Message -Level "ERROR" -Message "Failed to encode video for recording $($recording.id)"

                $updateQuery = "UPDATE recording_scheduled SET processing_status = 'failed', error_message = @error_message WHERE id = @id"
                $params = @{
                    id = $recording.id
                    error_message = "Video encoding failed"
                }
                Execute-DatabaseQuery -Query $updateQuery -Parameters $params

                $processLog += "[FAIL] Recording $($recording.id) - encoding failed"
                continue
            }

            # ===== SUB-TASK 2B: GENERATE THUMBNAIL =====
            $thumbnailFile = Generate-Thumbnail -InputFile $processedFile -RecordingID $recording.id

            if (-not $thumbnailFile) {
                Log-Message -Level "WARNING" -Message "Failed to generate thumbnail for recording $($recording.id)"
            }

            # ===== SUB-TASK 2C: UPLOAD TO CPANEL VIA FTP =====
            $remoteVideoPath = Upload-VideoToCPanel -LocalFilePath $processedFile -RecordingID $recording.id

            if ($remoteVideoPath) {
                Log-Message -Level "INFO" -Message "Video uploaded to cPanel: $remoteVideoPath"

                # Update recording with remote path
                $updateQuery = "UPDATE recording_scheduled SET encoded_file_path = @remote_path WHERE id = @id"
                $params = @{
                    id = $recording.id
                    remote_path = $remoteVideoPath
                }
                Execute-DatabaseQuery -Query $updateQuery -Parameters $params
            } else {
                Log-Message -Level "WARNING" -Message "Failed to upload video to cPanel for recording $($recording.id)"
            }

            # ===== SUB-TASK 2D: UPDATE PROCESSING STATUS =====
            $updateQuery = "UPDATE recording_scheduled SET processing_status = 'completed', error_message = NULL WHERE id = @id"
            $params = @{
                id = $recording.id
            }

            $updateResult = Execute-DatabaseQuery -Query $updateQuery -Parameters $params

            if ($updateResult) {
                Log-Message -Level "INFO" -Message "Successfully processed recording $($recording.id)"
                $processLog += "[SUCCESS] Recording $($recording.id) processed"
            } else {
                Log-Message -Level "ERROR" -Message "Failed to update processing status for recording $($recording.id)"
                $processLog += "[ERROR] Recording $($recording.id) - status update failed"
            }

        } catch {
            $errorMsg = "Exception processing recording $($recording.id): $_"
            Log-Message -Level "ERROR" -Message $errorMsg

            # Update status to failed
            $updateQuery = "UPDATE recording_scheduled SET processing_status = 'failed', error_message = @error_message WHERE id = @id"
            $params = @{
                id = $recording.id
                error_message = $errorMsg
            }
            Execute-DatabaseQuery -Query $updateQuery -Parameters $params

            $processLog += "[ERROR] Recording $($recording.id) - exception: $_"
        }
    }

    # ===== LOG SUMMARY =====
    $duration = (Get-Date) - $startTime
    $summary = "Processing complete: Processed=$($completedRecordings.Count), Duration=$($duration.TotalSeconds)s"

    Log-Message -Level "INFO" -Message $summary

    if ($processLog.Count -gt 0) {
        Log-Message -Level "INFO" -Message "Process Details: $($processLog -join ' | ')"
    }

    Write-Output "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] $summary"
    if ($processLog.Count -gt 0) {
        Write-Output ($processLog -join "`n")
    }

} catch {
    $errorMsg = "Fatal error in post_record_process: $_"
    Log-Message -Level "ERROR" -Message $errorMsg
    Write-Error $errorMsg
    exit 1
}
