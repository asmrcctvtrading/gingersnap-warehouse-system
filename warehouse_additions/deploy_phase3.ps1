# Phase 3 Deployment Script for Gingersnaps Warehouse
# Generated: 2026-10-24T11:12Z
# Target: asmrcctvtechnologies.com/warehouse

param(
    [ValidateSet('zip', 'ftp', 'list')]
    [string]$Mode = 'list',

    [string]$FtpHost = '',
    [string]$FtpUser = '',
    [string]$FtpPass = '',
    [string]$RemotePath = '/public_html/warehouse'
)

$ErrorActionPreference = 'Stop'
$basePath = Split-Path -Parent $MyInvocation.MyCommand.Path

# File manifest
$files = @(
    @{Local='modules\dbased\list.php';        Remote='modules/dbased/list.php';        Size=22971},
    @{Local='modules\dbased\view.php';        Remote='modules/dbased/view.php';        Size=23444},
    @{Local='modules\dbased\form.php';        Remote='modules/dbased/form.php';        Size=19147},
    @{Local='modules\dbased\action.php';      Remote='modules/dbased/action.php';      Size=6654},
    @{Local='modules\purchase_orders\form.php';   Remote='modules/purchase_orders/form.php';   Size=10639},
    @{Local='modules\purchase_orders\action.php'; Remote='modules/purchase_orders/action.php'; Size=12662},
    @{Local='modules\purchase_orders\view.php';   Remote='modules/purchase_orders/view.php';   Size=15810},
    @{Local='modules\tickets\form.php';       Remote='modules/tickets/form.php';       Size=5764},
    @{Local='modules\tickets\action.php';     Remote='modules/tickets/action.php';     Size=3687},
    @{Local='modules\tickets\print.php';      Remote='modules/tickets/print.php';      Size=15695},
    @{Local='modules\gatepass\action.php';    Remote='modules/gatepass/action.php';    Size=24215}
)

function Write-Header {
    Write-Host "`n╔═══════════════════════════════════════════════════════╗" -ForegroundColor Cyan
    Write-Host "║   GINGERSNAPS WMS - PHASE 3 DEPLOYMENT SCRIPT       ║" -ForegroundColor Cyan
    Write-Host "╚═══════════════════════════════════════════════════════╝" -ForegroundColor Cyan
    Write-Host "Mode: $Mode" -ForegroundColor Yellow
    Write-Host "Files: 11 total`n" -ForegroundColor Yellow
}

function Test-Files {
    Write-Host "→ Verifying local files..." -ForegroundColor Cyan
    $allExist = $true
    $totalSize = 0

    foreach ($file in $files) {
        $localPath = Join-Path $basePath $file.Local
        if (Test-Path $localPath) {
            $actualSize = (Get-Item $localPath).Length
            $match = if ($actualSize -eq $file.Size) { '✓' } else { '⚠' }
            Write-Host "  $match $($file.Local)" -ForegroundColor $(if ($actualSize -eq $file.Size) { 'Green' } else { 'Yellow' })
            if ($actualSize -ne $file.Size) {
                Write-Host "    Expected: $($file.Size) bytes | Actual: $actualSize bytes" -ForegroundColor Gray
            }
            $totalSize += $actualSize
        } else {
            Write-Host "  ✗ $($file.Local) - NOT FOUND" -ForegroundColor Red
            $allExist = $false
        }
    }

    Write-Host "`nTotal size: $([math]::Round($totalSize/1KB, 2)) KB" -ForegroundColor Cyan

    if (-not $allExist) {
        throw "Some files are missing. Deployment aborted."
    }

    Write-Host "✓ All files verified`n" -ForegroundColor Green
}

function New-DeploymentZip {
    $timestamp = Get-Date -Format 'yyyyMMdd_HHmmss'
    $zipName = "phase3_deployment_$timestamp.zip"
    $zipPath = Join-Path $basePath $zipName

    Write-Host "→ Creating deployment package..." -ForegroundColor Cyan

    # Create temporary staging directory
    $stagingDir = Join-Path $basePath "temp_deploy_$timestamp"
    New-Item -ItemType Directory -Path $stagingDir -Force | Out-Null

    try {
        # Copy files preserving directory structure
        foreach ($file in $files) {
            $srcPath = Join-Path $basePath $file.Local
            $destPath = Join-Path $stagingDir $file.Local
            $destDir = Split-Path $destPath -Parent

            if (-not (Test-Path $destDir)) {
                New-Item -ItemType Directory -Path $destDir -Force | Out-Null
            }

            Copy-Item $srcPath $destPath
            Write-Host "  + $($file.Local)" -ForegroundColor Gray
        }

        # Copy deployment checklist
        $checklistSrc = Join-Path $basePath "PHASE3_DEPLOYMENT.md"
        if (Test-Path $checklistSrc) {
            Copy-Item $checklistSrc (Join-Path $stagingDir "PHASE3_DEPLOYMENT.md")
            Write-Host "  + PHASE3_DEPLOYMENT.md" -ForegroundColor Gray
        }

        # Create README
        $readme = @"
# Phase 3 Deployment Package
Generated: $timestamp

## Contents
11 PHP files for Gingersnaps Warehouse Management System Phase 3

## Upload Instructions
1. Extract this zip to a local directory
2. Upload via cPanel File Manager or FTP to: /public_html/warehouse/
3. Preserve the directory structure (modules/*)
4. Overwrite existing files when prompted
5. Follow PHASE3_DEPLOYMENT.md for testing checklist

## Files Included
$(($files | ForEach-Object { "- $($_.Remote)" }) -join "`n")

## Target Server
- Host: asmrcctvtechnologies.com
- Path: /public_html/warehouse/
- Database: yitrmcft_gingersnaps

## Support
Refer to PHASE3_DEPLOYMENT.md for detailed deployment and testing procedures.
"@

        Set-Content -Path (Join-Path $stagingDir "README.txt") -Value $readme -Encoding UTF8

        # Create zip
        Compress-Archive -Path "$stagingDir\*" -DestinationPath $zipPath -Force

        $zipSize = [math]::Round((Get-Item $zipPath).Length/1KB, 2)
        Write-Host "`n✓ Deployment package created:" -ForegroundColor Green
        Write-Host "  $zipPath" -ForegroundColor White
        Write-Host "  Size: $zipSize KB`n" -ForegroundColor Gray

        # Show next steps
        Write-Host "→ Next Steps:" -ForegroundColor Cyan
        Write-Host "  1. Extract the zip file" -ForegroundColor White
        Write-Host "  2. Login to cPanel File Manager" -ForegroundColor White
        Write-Host "  3. Navigate to /public_html/warehouse/" -ForegroundColor White
        Write-Host "  4. Upload the modules/* directories" -ForegroundColor White
        Write-Host "  5. Overwrite existing files when prompted" -ForegroundColor White
        Write-Host "  6. Run post-deployment tests (see PHASE3_DEPLOYMENT.md)`n" -ForegroundColor White

    } finally {
        # Cleanup staging directory
        Remove-Item $stagingDir -Recurse -Force -ErrorAction SilentlyContinue
    }
}

function Invoke-FtpDeploy {
    if ([string]::IsNullOrWhiteSpace($FtpHost) -or
        [string]::IsNullOrWhiteSpace($FtpUser) -or
        [string]::IsNullOrWhiteSpace($FtpPass)) {
        Write-Host "✗ FTP credentials required. Use parameters:" -ForegroundColor Red
        Write-Host "  -FtpHost 'ftp.example.com'" -ForegroundColor Yellow
        Write-Host "  -FtpUser 'username'" -ForegroundColor Yellow
        Write-Host "  -FtpPass 'password'" -ForegroundColor Yellow
        Write-Host "  -RemotePath '/public_html/warehouse'`n" -ForegroundColor Yellow
        return
    }

    Write-Host "→ Deploying via FTP..." -ForegroundColor Cyan
    Write-Host "  Host: $FtpHost" -ForegroundColor Gray
    Write-Host "  Remote path: $RemotePath`n" -ForegroundColor Gray

    $uploaded = 0
    $failed = 0

    foreach ($file in $files) {
        $localPath = Join-Path $basePath $file.Local
        $remotePath = "$RemotePath/$($file.Remote -replace '\\', '/')"

        try {
            $ftpUri = "ftp://$FtpHost$remotePath"
            $ftpRequest = [System.Net.FtpWebRequest]::Create($ftpUri)
            $ftpRequest.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
            $ftpRequest.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
            $ftpRequest.UseBinary = $true
            $ftpRequest.KeepAlive = $false

            $fileContent = [System.IO.File]::ReadAllBytes($localPath)
            $ftpRequest.ContentLength = $fileContent.Length

            $requestStream = $ftpRequest.GetRequestStream()
            $requestStream.Write($fileContent, 0, $fileContent.Length)
            $requestStream.Close()

            $response = $ftpRequest.GetResponse()
            $response.Close()

            Write-Host "  ✓ $($file.Remote)" -ForegroundColor Green
            $uploaded++

        } catch {
            Write-Host "  ✗ $($file.Remote) - $($_.Exception.Message)" -ForegroundColor Red
            $failed++
        }
    }

    Write-Host "`n→ Upload Summary:" -ForegroundColor Cyan
    Write-Host "  Uploaded: $uploaded / $($files.Count)" -ForegroundColor $(if ($uploaded -eq $files.Count) { 'Green' } else { 'Yellow' })
    if ($failed -gt 0) {
        Write-Host "  Failed: $failed" -ForegroundColor Red
    }
    Write-Host ""
}

function Show-FileList {
    Write-Host "→ Phase 3 File Manifest:" -ForegroundColor Cyan
    Write-Host ""

    $grouped = $files | Group-Object { ($_.Local -split '\\')[1] }

    foreach ($group in $grouped) {
        Write-Host "  $($group.Name)/ ($($group.Count) files)" -ForegroundColor Yellow
        foreach ($file in $group.Group) {
            $localPath = Join-Path $basePath $file.Local
            $fileName = $file.Local.Split('\')[-1]
            if (Test-Path $localPath) {
                $size = (Get-Item $localPath).Length
                $sizeKB = [math]::Round($size/1KB, 1)
                Write-Host "    + $fileName" -ForegroundColor Green -NoNewline
                Write-Host " ($sizeKB KB)" -ForegroundColor Gray
            } else {
                Write-Host "    x $fileName" -ForegroundColor Red
            }
        }
        Write-Host ""
    }

    Write-Host "→ Upload Target:" -ForegroundColor Cyan
    Write-Host "  Server: asmrcctvtechnologies.com" -ForegroundColor White
    Write-Host "  Path: /public_html/warehouse/" -ForegroundColor White
    Write-Host "  Database: yitrmcft_gingersnaps`n" -ForegroundColor White

    Write-Host "→ Available Modes:" -ForegroundColor Cyan
    Write-Host "  .\deploy_phase3.ps1 -Mode list  # Show this list" -ForegroundColor White
    Write-Host "  .\deploy_phase3.ps1 -Mode zip   # Create deployment zip" -ForegroundColor White
    Write-Host "  .\deploy_phase3.ps1 -Mode ftp -FtpHost '...' -FtpUser '...' -FtpPass '...'" -ForegroundColor White
    Write-Host ""
}

# Main execution
try {
    Write-Header
    Test-Files

    switch ($Mode) {
        'zip' { New-DeploymentZip }
        'ftp' { Invoke-FtpDeploy }
        'list' { Show-FileList }
    }

    Write-Host "✓ Script completed successfully`n" -ForegroundColor Green

} catch {
    Write-Host "`n✗ Deployment failed: $($_.Exception.Message)`n" -ForegroundColor Red
    exit 1
}
