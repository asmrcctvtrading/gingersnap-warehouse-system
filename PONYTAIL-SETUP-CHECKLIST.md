# Ponytail Setup Checklist

**Project:** OmniRoute
**Date Initiated:** 2026-10-24
**Status:** Ready for Installation

---

## Overview

Ponytail is a unified coding guidelines plugin system for multiple AI coding agents. This checklist guides you through the installation for Claude Code (your primary IDE).

---

## Installation Checklist

### Phase 1: Prerequisites

- [ ] Claude Code v2.0+ installed
- [ ] Git configured for cloning repositories
- [ ] Terminal/PowerShell access
- [ ] Internet connectivity

### Phase 2: Add Ponytail Marketplace

**Method 1: GUI (Recommended)**

1. [ ] Open Claude Code
2. [ ] Open Settings (Ctrl+,)
3. [ ] Navigate to: Extensions > Marketplaces
4. [ ] Click "Add Marketplace"
5. [ ] Enter repository URL: (pending - to be confirmed)
6. [ ] Wait for marketplace to sync

**Method 2: CLI**

```powershell
# Run in terminal
claude marketplace add ponytail <REPOSITORY_URL>
```

- [ ] Marketplace added successfully

### Phase 3: Install Ponytail Plugin

**Via GUI:**
1. [ ] Open Claude Code Command Palette (Ctrl+Shift+P)
2. [ ] Search: "Plugin Install"
3. [ ] Type: "ponytail"
4. [ ] Select: "Ponytail: Consistent Coding Guidelines"
5. [ ] Click "Install"

**Via CLI:**
```powershell
claude plugin install ponytail
```

- [ ] Plugin installed successfully

### Phase 4: Verify Installation

```powershell
# Check installed plugins
claude plugin list

# Verify Ponytail is active
claude plugin status ponytail
```

Expected output:
```
Ponytail (v1.0.0+)
  Status: active
  Intensity: full
  6 skills available
```

- [ ] Installation verified

### Phase 5: Configure Settings

**Edit:** `~/.omniroute/.claude/settings.json` (or create if needed)

Add or update:
```json
{
  "enabledPlugins": {
    "ponytail@ponytail-marketplace": true
  }
}
```

- [ ] Settings updated
- [ ] Claude Code restarted

### Phase 6: Activate Project Configuration

Project files already prepared:
- [x] AGENTS.md created at project root
- [x] PONYTAIL-SETUP.md created for detailed guide
- [ ] AGENTS.md verified at: `C:\Users\Odyssey Jan Pascual\.omniroute\AGENTS.md`

---

## Intensity Level Configuration

### Available Levels

| Level | Use Case | Coverage |
|-------|----------|----------|
| **off** | Disable plugin | No enforcement |
| **lite** | Minimal enforcement | Core patterns only |
| **full** | Recommended | Best balance (selected) |
| **ultra** | Maximum coverage | All edge cases |

### Configure Intensity

**Via settings.json:**
```json
{
  "enabledPlugins": {
    "ponytail@ponytail-marketplace": {
      "enabled": true,
      "intensity": "full"
    }
  }
}
```

**Via CLI:**
```powershell
claude plugin config ponytail --intensity full
```

- [ ] Intensity level set to: **full**

---

## Provided Skills

Ponytail provides 6 standardized skills:

1. **Code Style** - Consistent formatting and naming
2. **Error Handling** - Uniform exception management
3. **Security** - Standard security practices
4. **Performance** - Efficient resource management
5. **Documentation** - Clear code documentation
6. **Testing** - Automated test patterns

Each skill operates at your configured intensity level.

---

## Files Created

| File | Purpose | Location |
|------|---------|----------|
| AGENTS.md | Workspace guidance | Project root |
| PONYTAIL-SETUP.md | Detailed installation | Project root |
| PONYTAIL-SETUP-CHECKLIST.md | This file | Project root |

**Verification:**
```powershell
Get-ChildItem "C:\Users\Odyssey Jan Pascual\.omniroute" -Filter "*AGENTS*"
Get-ChildItem "C:\Users\Odyssey Jan Pascual\.omniroute" -Filter "*PONYTAIL*"
```

---

## Next Steps

### Immediate (Today)

1. **Obtain Marketplace URL**
   - Confirm Ponytail repository location
   - Verify it's accessible from your network
   - Note: If behind a proxy, add marketplace headers

2. **Add Marketplace** (Method 1 or 2)
   - GUI: Settings > Extensions > Marketplaces > Add
   - CLI: `claude marketplace add ponytail <URL>`

3. **Install Plugin**
   - GUI: Command Palette > Plugin Install > ponytail
   - CLI: `claude plugin install ponytail`

4. **Verify Installation**
   - Run: `claude plugin list | grep ponytail`
   - Check status output

### Short-term (This Week)

1. [ ] Configure intensity level (recommend: "full")
2. [ ] Test with a simple code generation task
3. [ ] Verify guidelines are being applied
4. [ ] Adjust intensity if needed

### Medium-term (This Month)

1. [ ] Review Ponytail skill output on active projects
2. [ ] Provide feedback on guideline effectiveness
3. [ ] Consider using "ultra" for critical code paths
4. [ ] Document any project-specific overrides

---

## Troubleshooting

### Marketplace Not Found
**Symptoms:** "Repository not found" when adding marketplace
**Solutions:**
- [ ] Verify repository URL is correct
- [ ] Check network connectivity
- [ ] If behind proxy: add marketplace headers for authentication
- [ ] Try: `claude marketplace refresh`

### Plugin Won't Install
**Symptoms:** Plugin appears in list but won't install or activate
**Solutions:**
- [ ] Clear plugin cache: `rm -rf ~/.claude/plugins/.cache`
- [ ] Restart Claude Code completely
- [ ] Verify marketplace was added: `claude marketplace list`
- [ ] Check plugin compatibility with your Claude Code version

### Settings Not Applied
**Symptoms:** Plugin installed but guidelines not being followed
**Solutions:**
- [ ] Verify settings.json syntax (valid JSON)
- [ ] Check plugin is enabled: `claude plugin status ponytail`
- [ ] Restart Claude Code after settings changes
- [ ] Verify intensity level: `claude plugin config ponytail`

### Plugin Conflicts
**Symptoms:** Error messages about plugin conflicts
**Solutions:**
- [ ] Check for duplicate plugin installations
- [ ] Verify no conflicting plugins installed
- [ ] Review plugin load order in settings
- [ ] Check bundled plugin restrictions

---

## Support & Documentation

- **Official Docs:** https://github.com/ponytail-coding/ponytail
- **Skills Guide:** Available in Ponytail repository
- **Claude Code Docs:** `/help plugins`
- **Marketplace Search:** Built into Claude Code

---

## Contact & Feedback

For issues or questions about:
- **Ponytail:** Contact Ponytail team (via repository)
- **This Setup:** Refer to PONYTAIL-SETUP.md
- **Claude Code:** `/help` or official documentation

---

## Completion Summary

When all items are checked:
- Ponytail marketplace added and synced
- Plugin installed and verified active
- Settings configured and applied
- Project files in place
- Ready to use guidelines in code generation

**Estimated Time:** 15-30 minutes from start to finish

---

**Installation Log:**
- Created: 2026-10-24
- Files: 3 (AGENTS.md, PONYTAIL-SETUP.md, this checklist)
- Status: Ready for marketplace URL
- Next: User confirms Ponytail repository location
