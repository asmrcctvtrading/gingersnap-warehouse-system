# PickleCam - Recording Configuration
# Central config for all recording automation scripts

# ===== API CONFIGURATION =====
# Base URL for PickleCam API endpoints on cPanel
$API_BASE_URL = "https://asmrcctvtechnologies.com/picklecam/api"

# Secret token for API authentication (must match BOOKING_HOOK_SECRET from PHP config)
$API_SECRET = "CEOSJH4g8Wsl30n7jT1aq52BbuIRP6xp"

# ===== RECORDING PARAMETERS =====
# Time (in minutes) before scheduled_start to begin recording
$RECORD_START_BUFFER_MINUTES = 5

# Time (in hours) to consider a recording stale (crashed process)
$STALE_THRESHOLD_HOURS = 8

# Days to keep recordings before cleanup
$CLEANUP_DAYS = 7

# ===== PATHS =====
# Local storage for video files
$VIDEO_STORAGE_PATH = "C:\xampp\htdocs\picklecam\videos"
$RECORDING_OUTPUT_PATH = $VIDEO_STORAGE_PATH

# ffmpeg executable directory
$FFMPEG_PATH = "C:\ffmpeg\bin"

# Logs directory
$LOG_PATH = "C:\xampp\htdocs\picklecam\logs"

# ===== FFMPEG CODEC SETTINGS =====
$FFMPEG_VIDEO_CODEC = "libx265"    # h265 - good compression
$FFMPEG_AUDIO_CODEC = "aac"        # AAC audio
$FFMPEG_AUDIO_BITRATE = "128k"

# ===== PROCESS MANAGEMENT =====
# Maximum concurrent recordings
$MAX_CONCURRENT_RECORDINGS = 4

# Process check interval (seconds)
$PROCESS_CHECK_INTERVAL = 30

# ===== LOGGING =====
# Log level: "DEBUG", "INFO", "WARNING", "ERROR"
$LOG_LEVEL = "INFO"

# Maximum log file size before rotation (MB)
$MAX_LOG_SIZE_MB = 50

# ===== ERROR HANDLING =====
# Retry attempts for failed operations
$MAX_RETRIES = 3

# Delay between retries (seconds)
$RETRY_DELAY_SECONDS = 5

# Email for error notifications (optional)
$ERROR_EMAIL = ""

# ===== SSL VERIFICATION =====
# For testing/development, set to $false. For production, use $true
$VERIFY_SSL = $true

# TLS version for API calls
$SECURITY_PROTOCOL = [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12

# ===== HELPER FUNCTION: Validate Config =====
function Validate-RecordingConfig {
    $errors = @()

    if (-not (Test-Path $FFMPEG_PATH)) {
        $errors += "ffmpeg not found at: $FFMPEG_PATH"
    }

    if (-not (Test-Path $VIDEO_STORAGE_PATH)) {
        New-Item -Path $VIDEO_STORAGE_PATH -ItemType Directory -Force | Out-Null
    }

    if (-not (Test-Path $LOG_PATH)) {
        New-Item -Path $LOG_PATH -ItemType Directory -Force | Out-Null
    }

    if ($API_SECRET -eq "CEOSJH4g8Wsl30n7jT1aq52BbuIRP6xp") {
        Write-Host "WARNING: API_SECRET is using default value. Update it with your actual secret." -ForegroundColor Yellow
    }

    if ($errors.Count -gt 0) {
        Write-Host "Configuration Errors:" -ForegroundColor Red
        $errors | ForEach-Object { Write-Host "  - $_" -ForegroundColor Red }
        return $false
    }

    return $true
}

# ===== HELPER FUNCTION: Invoke API with Error Handling =====
function Invoke-PickleCamAPI {
    param(
        [string]$Endpoint,
        [string]$Method = "GET",
        [hashtable]$Body = $null,
        [int]$Timeout = 30
    )

    $url = "$API_BASE_URL/$Endpoint"

    # Add secret to URL for GET, or header for POST
    if ($Method -eq "GET") {
        if ($url -notmatch "\?") {
            $url += "?secret=$API_SECRET"
        } else {
            $url += "&secret=$API_SECRET"
        }
    }

    $headers = @{
        "Authorization" = "Bearer $API_SECRET"
        "Content-Type" = "application/json"
    }

    try {
        if (!$VERIFY_SSL) {
            [System.Net.ServicePointManager]::ServerCertificateValidationCallback = { $true }
        }

        if ($Method -eq "POST" -and $Body) {
            $bodyJson = $Body | ConvertTo-Json -Depth 10
            $response = Invoke-RestMethod -Uri $url -Method $Method -Headers $headers -Body $bodyJson -TimeoutSec $Timeout
        } else {
            $response = Invoke-RestMethod -Uri $url -Method $Method -Headers $headers -TimeoutSec $Timeout
        }

        return $response
    } catch {
        $errorMsg = "API Error calling $Endpoint`: $_"
        Write-Error $errorMsg
        Log-Message -Level "ERROR" -Message $errorMsg
        return $null
    }
}

# ===== HELPER FUNCTION: Logging =====
function Log-Message {
    param(
        [string]$Level = "INFO",
        [string]$Message,
        [string]$Script = "recording"
    )

    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logFile = "$LOG_PATH\recording.log"
    $logEntry = "[$timestamp] [$Level] [$Script] $Message"

    Add-Content -Path $logFile -Value $logEntry

    if ($LOG_LEVEL -eq "DEBUG" -or $Level -in @("ERROR", "WARNING")) {
        $color = switch ($Level) {
            "ERROR" { "Red" }
            "WARNING" { "Yellow" }
            default { "Green" }
        }
        Write-Host $logEntry -ForegroundColor $color
    }
}

# ===== HELPER FUNCTION: Get FFmpeg PID =====
function Get-FFmpegProcessId {
    param([string]$FilePath)

    try {
        $process = Get-Process ffmpeg -ErrorAction SilentlyContinue |
            Where-Object { $_.CommandLine -like "*$FilePath*" } |
            Select-Object -First 1

        return $process.Id
    } catch {
        return $null
    }
}

# ===== HELPER FUNCTION: Kill Process =====
function Stop-ProcessByID {
    param(
        [int]$ProcessID,
        [bool]$Force = $false
    )

    try {
        if ($Force) {
            Stop-Process -Id $ProcessID -Force
        } else {
            Stop-Process -Id $ProcessID
        }
        return $true
    } catch {
        Log-Message -Level "WARNING" -Message "Failed to stop process $ProcessID`: $_"
        return $false
    }
}

# Functions are available to calling scripts
