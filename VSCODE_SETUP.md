# VSCode + OmniRoute Auto-Init Setup
**Created**: 2026-10-24 | **Status**: Ready to use

---

## Overview
This setup automatically initializes Claude with OmniRoute whenever you open a terminal in VSCode from the `.omniroute` directory. The integration includes:

1. **VSCode Workspace Settings** (`.vscode/settings.json`)
   - Auto-sets environment variables for OmniRoute
   - Configures terminal profile to check/start OmniRoute on terminal open

2. **VSCode Tasks** (`.vscode/tasks.json`)
   - Task: "Start OmniRoute Server" — runs the startup script
   - Task: "Initialize Claude with OmniRoute" — sets env vars and confirms setup
   - Task: "Check OmniRoute Status" — quick status check

3. **PowerShell Profile** (`~/Documents/PowerShell/profile.ps1`)
   - Auto-runs on every PowerShell terminal open (VSCode or other)
   - Checks if OmniRoute is running
   - Auto-starts OmniRoute if not running
   - Initializes Claude environment variables
   - Shows available commands and dashboard URLs

---

## Quick Start

### 1. Open VSCode from .omniroute directory
```powershell
# From PowerShell or cmd:
code C:\Users\Odyssey\ Jan Pascual\.omniroute
```

### 2. Open a new terminal in VSCode
- Press `Ctrl+`` (backtick) or go to **Terminal → New Terminal**
- The profile will automatically:
  - Check if OmniRoute is running
  - Start it if not running (takes ~3-4 seconds)
  - Set environment variables
  - Display status and available commands

### 3. Use Claude immediately
```powershell
claude
claude --model auto/best
claude --model cc/claude-opus-5-5
```

---

## Files Created/Modified

```
C:\Users\Odyssey Jan Pascual\
├── .omniroute/
│   ├── .vscode/
│   │   ├── settings.json          ← Workspace settings (auto-init terminal)
│   │   └── tasks.json             ← VSCode tasks for OmniRoute
│   ├── VSCODE_SETUP.md            ← This file
│   ├── OMNIROUTE_GUIDE.md         ← Existing guide (no changes)
│   └── ...
└── Documents/PowerShell/
    └── profile.ps1                 ← PowerShell terminal profile (auto-init)
```

---

## Features

### Terminal Auto-Init (Workspace Settings)
When you open a new terminal in VSCode:
```
OmniRoute auto-init starting...
OmniRoute is already running.
Ready for Claude.
```

**Environment variables set automatically:**
- `ANTHROPIC_BASE_URL=http://localhost:20128`
- `ANTHROPIC_AUTH_TOKEN=local`
- `ANTHROPIC_MODEL=my-combo`
- `CLAUDE_CODE_ENABLE_GATEWAY_MODEL_DISCOVERY=1`

### PowerShell Profile (Global)
Every time you open a PowerShell terminal (anywhere, not just VSCode):

```
OmniRoute Auto-Init Profile

✓ OmniRoute is running (PID 12345, Memory: 45.32 MB)
  Dashboard: http://localhost:20128
  Web UI: http://localhost:3000

OmniRoute environment initialized
  ANTHROPIC_BASE_URL: http://localhost:20128
  ANTHROPIC_MODEL: my-combo

OmniRoute Quick Commands:
  omniroute status      - Check OmniRoute server status
  omniroute restart     - Restart the server
  omniroute logs        - Show server logs
  claude                - Start Claude with omniroute
  claude --model <id>   - Start with specific model
  omniroute-help        - Show this message
```

### Available VSCode Tasks
Open Task Palette: `Ctrl+Shift+P` → `Tasks: Run Task`

1. **Start OmniRoute Server**
   - Runs the startup batch file
   - Shows server status in integrated terminal
   - Use if OmniRoute crashes

2. **Initialize Claude with OmniRoute**
   - Sets environment variables in a fresh terminal
   - Confirms all settings are correct
   - Shows ready message for `claude` command

3. **Check OmniRoute Status**
   - Quick health check of running server
   - Shows PID and memory usage
   - Shows dashboard URLs

---

## Usage Examples

### Example 1: Open VSCode and start working
```bash
# Terminal auto-initializes with OmniRoute check
# Just type:
claude --model cc/claude-sonnet-5-5

# Or use auto-detection:
claude
```

### Example 2: OmniRoute crashed, restart it
```bash
# Ctrl+Shift+P → Tasks: Run Task → "Start OmniRoute Server"
# Or manual:
omniroute restart
```

### Example 3: Check which providers are available
```bash
# Open http://localhost:20128 in browser
# Or check command line:
omniroute status
```

### Example 4: Switch models mid-session
```powershell
# Set model temporarily:
$env:ANTHROPIC_MODEL = "auto/best"
claude

# Or switch back to combo:
$env:ANTHROPIC_MODEL = "my-combo"
```

---

## Model Reference (Quick)

For your common tasks:

| Task | Command | Why |
|------|---------|-----|
| Quick question | `claude --model cc/claude-haiku-5-5` | Fast, cheap |
| Debugging code | `claude --model cc/claude-sonnet-5-5` | Strong reasoning |
| When Claude limited | `claude --model auto/best` | OmniRoute picks best available |
| Database design | `claude --model cc/claude-opus-5-5` | Most thorough |

Full reference: See `OMNIROUTE_GUIDE.md` → Section K (Model Reference Card)

---

## Troubleshooting

### Problem: "Connection refused" when starting Claude
**Solution:**
1. Check OmniRoute is running: `omniroute status`
2. If not: Run task "Start OmniRoute Server" or `omniroute restart`
3. Wait 3-5 seconds for server to start
4. Try again: `claude`

### Problem: PowerShell profile not running
**Cause:** Execution policy may be restricted

**Solution:**
```powershell
# Check current policy:
Get-ExecutionPolicy

# If "Restricted", allow user scripts:
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Problem: Terminal doesn't show OmniRoute status
**Solution:**
- If using `cmd.exe`: Switch to PowerShell (VSCode setting: `terminal.integrated.defaultProfile.windows`)
- Verify `profile.ps1` exists at: `C:\Users\Odyssey Jan Pascual\Documents\PowerShell\profile.ps1`
- Manually source it: `. $PROFILE`

### Problem: Environment variables not set
**Solution 1 (VSCode):** Run task "Initialize Claude with OmniRoute"

**Solution 2 (Manual):**
```powershell
$env:ANTHROPIC_BASE_URL = "http://localhost:20128"
$env:ANTHROPIC_MODEL = "my-combo"
claude
```

---

## Advanced: Customize Settings

### Change default model in VSCode
Edit `.vscode/settings.json`:
```json
"terminal.integrated.env.windows": {
  "ANTHROPIC_MODEL": "auto/best"  ← Change this
}
```

### Auto-start specific task on workspace open
Edit `.vscode/settings.json` and add:
```json
{
  "tasks.runTasks": ["Start OmniRoute Server"]
}
```
(Note: VSCode doesn't natively support this, but the terminal profile accomplishes it)

### Disable auto-init on terminal open
Edit `.vscode/settings.json` and remove the custom `PowerShell` profile — revert to default:
```json
"terminal.integrated.profiles.windows": {
  // Remove the custom PowerShell entry
}
```

---

## Integration with Claude Code (Existing)

Your `.claude/settings.json` already has OmniRoute configured:
```json
{
  "env": {
    "ANTHROPIC_BASE_URL": "http://localhost:20128",
    "ANTHROPIC_AUTH_TOKEN": "local",
    "ANTHROPIC_MODEL": "my-combo"
  }
}
```

**This setup complements it** by ensuring OmniRoute is always running when you open VSCode.

---

## When This Setup Activates

### VSCode Terminal Profile (`.vscode/settings.json`)
- ✅ Opens a terminal in VSCode from `.omniroute` directory
- ✅ Uses PowerShell profile
- ✅ Auto-checks/starts OmniRoute

### PowerShell Profile (`~/Documents/PowerShell/profile.ps1`)
- ✅ Opens any PowerShell terminal (VSCode, Windows Terminal, cmd → powershell)
- ✅ Check/starts OmniRoute (global, every session)
- ✅ Sets environment variables for Claude

### NOT activated:
- ❌ CMD (cmd.exe) unless you type `powershell` first
- ❌ Other shells (bash, zsh, etc.) unless explicitly configured

---

## Summary

| Component | File | Scope | Action |
|-----------|------|-------|--------|
| Workspace settings | `.vscode/settings.json` | VSCode terminal from `.omniroute` | Auto-init PowerShell with OmniRoute |
| VSCode tasks | `.vscode/tasks.json` | VSCode tasks palette | Manual start/check OmniRoute |
| PowerShell profile | `~/Documents/PowerShell/profile.ps1` | All PowerShell terminals | Auto-check/start OmniRoute + env vars |

**Result:** Whenever you open a PowerShell terminal in VSCode (or anywhere), Claude + OmniRoute are ready to use in seconds.
