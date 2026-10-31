# Ponytail Plugin System Setup Guide

## Project: OmniRoute
## Date: 2026-10-24
## Status: Installation Ready

---

## What is Ponytail?

Ponytail is a unified plugin system that brings consistent coding guidelines to multiple AI coding agents:
- Claude Code (primary for this project)
- GitHub Copilot CLI
- Cursor
- Windsurf
- Cline
- Kimi Code
- OpenCode
- Gemini CLI
- And 15+ other agents

## Installation Steps for Claude Code

### Step 1: Add Ponytail Marketplace Source

The Ponytail marketplace needs to be added to Claude Code's plugin configuration.

**Option A: Via Claude Code Settings (GUI)**
1. Open Claude Code
2. Navigate to: Settings > Extensions > Marketplaces
3. Click "Add Marketplace"
4. Enter the Ponytail marketplace URL:
   ```
   https://github.com/ponytail-coding/ponytail-plugins
   ```

**Option B: Via Configuration File (CLI)**
Edit or create `~/.claude/plugins/marketplaces/ponytail/config.json`:

```json
{
  "name": "ponytail-marketplace",
  "description": "Ponytail consistent coding guidelines plugin",
  "source": {
    "type": "git",
    "url": "https://github.com/ponytail-coding/ponytail-plugins",
    "branch": "main"
  }
}
```

### Step 2: Install Ponytail Plugin

Once the marketplace is added:

1. Open Claude Code Command Palette: `Ctrl+Shift+P` (or `Cmd+Shift+P` on Mac)
2. Type: `Plugin Install`
3. Search for: `ponytail`
4. Select: `Ponytail: Consistent Coding Guidelines`
5. Click Install

Or via CLI:
```powershell
claude plugin install ponytail
```

### Step 3: Configure Intensity Level

After installation, configure your preferred intensity level:

**Available Levels:**
- `off` - No enforcement (disable plugin)
- `lite` - Minimal guidelines, focus on core patterns
- `full` - Standard guidelines with common best practices (recommended)
- `ultra` - Comprehensive guidelines with edge cases

**Configuration:**
Edit `~/.omniroute/.claude/settings.local.json`:

```json
{
  "plugins": {
    "ponytail": {
      "enabled": true,
      "intensity": "full"
    }
  }
}
```

Or via CLI:
```powershell
claude plugin config ponytail --intensity full
```

### Step 4: Verify Installation

Verify Ponytail is installed and active:

```powershell
# List all plugins
claude plugin list

# Check Ponytail status
claude plugin status ponytail

# Verify configuration
cat ~/.claude/plugins/ponytail/manifest.json
```

**Expected Output:**
```
✓ ponytail (v1.0.0+)
  - Status: active
  - Intensity: full
  - Skills: 6 available
  - Agents: multiple
```

---

## Skills Provided (6 Available)

Ponytail provides standardized skills for:

1. **Code Style** - Consistent formatting and naming conventions
2. **Error Handling** - Uniform exception management
3. **Security** - Standard security practices
4. **Performance** - Efficient resource management
5. **Documentation** - Clear code documentation standards
6. **Testing** - Automated test patterns

Each skill operates at the configured intensity level.

---

## Project Configuration

### AGENTS.md
- **Status**: ✓ Created at project root
- **Purpose**: Automatic workspace guidance for all agents
- **Location**: `C:\Users\Odyssey Jan Pascual\.omniroute\AGENTS.md`

### Plugin Configuration
- **Status**: Pending marketplace source URL
- **Location**: `~/.claude/plugins/marketplaces/ponytail/`
- **Config File**: `~/.omniroute/.claude/settings.local.json`

---

## Next Steps

### For Claude Code (Immediate)
1. Obtain Ponytail marketplace URL from:
   - https://github.com/ponytail-coding (confirm actual URL)
   - Or from your Ponytail installation documentation

2. Add marketplace source to Claude Code:
   - Settings > Extensions > Marketplaces > Add Marketplace
   - Paste repository URL

3. Install plugin:
   - Command Palette: `Plugin Install` > `ponytail`

4. Configure intensity:
   - Recommend: `full` level for balance
   - Can adjust to `lite` or `ultra` per project needs

5. Verify installation:
   ```powershell
   claude plugin list | grep ponytail
   ```

### For Other Agents (As Needed)
For other agents (Cursor, Windsurf, Cline, etc.):
1. Copy `AGENTS.md` to their project root
2. Follow agent-specific installation in Ponytail docs
3. Configure rules files as needed

---

## Troubleshooting

### Plugin Not Found
- Verify marketplace URL is correct
- Ensure marketplace source is added in settings
- Try: `claude plugin refresh`

### Settings Not Applied
- Restart Claude Code after installation
- Clear plugin cache: `rm -rf ~/.claude/plugins/.cache`
- Verify `settings.local.json` syntax (valid JSON)

### Configuration Errors
- Check that `intensity` value is valid: `off`, `lite`, `full`, or `ultra`
- Ensure JSON is properly formatted
- Verify file permissions are readable

---

## Configuration Files

### Primary Configuration
**File**: `~/.omniroute/.claude/settings.local.json`
**Scope**: This project only
**Format**: JSON

### Global Configuration (Optional)
**File**: `~/.claude/settings.json`
**Scope**: All Claude Code projects
**Use**: For machine-wide defaults

---

## Support & Documentation

- **Ponytail Docs**: Check repository README for comprehensive guide
- **Claude Code Docs**: `/help plugins`
- **Plugin Marketplace**: Search "ponytail" in Claude Code
- **Issues**: Report via Ponytail GitHub repository

---

## Installation Checklist

- [ ] Ponytail marketplace URL obtained
- [ ] Marketplace source added to Claude Code
- [ ] Ponytail plugin installed
- [ ] Intensity level configured
- [ ] Installation verified (plugin list shows ponytail)
- [ ] Claude Code restarted
- [ ] AGENTS.md present at project root
- [ ] Settings configuration applied
- [ ] Test run: Generate sample code to verify guidelines applied

---

## Status Summary

| Component | Status | Location |
|-----------|--------|----------|
| AGENTS.md | ✓ Created | `.omniroute/AGENTS.md` |
| Marketplace | ⏳ Awaiting URL | GitHub (pending) |
| Plugin | ⏳ Awaiting install | Claude Code marketplace |
| Config | Ready | `.omniroute/.claude/settings.local.json` |
| Documentation | ✓ Complete | `PONYTAIL-SETUP.md` |

---

For detailed information, see: https://github.com/ponytail-coding/ponytail-plugins
