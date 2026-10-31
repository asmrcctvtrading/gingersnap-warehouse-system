# OmniRoute — Complete User Guide
**Version**: 3.8.51 | **Last Updated**: 2026-10-10

---

## A. Health Report (Post-Optimization)

| Item | Status |
|------|--------|
| Server running | YES — localhost:20128 (localhost only) |
| Port security | FIXED — now 127.0.0.1 only |
| Backup | `~/.omniroute/backup_20261010_071825` |
| Working providers | Grok Build, OpenAI Codex, Trae, Gemini Web |
| Re-activated | Antigravity CLI (agy/), Kiro AI (kiro/) |
| Disabled (broken) | Auggie CLI |
| Claude OAuth | EXPIRED — re-auth needed (see Section E) |
| Web UI models | FIXED — no more broken kr/ model IDs |

---

## B. Configured Providers and Working Models

### Currently Working Providers

| Provider | ID Prefix | Best Models | Notes |
|----------|-----------|-------------|-------|
| OpenAI Codex | `codex/` | gpt-4o, gpt-4.1, codex models | 45 models, API subscription |
| Trae | `trae/` | claude-like models | 9 models, free quota applies |
| Grok Build | `grok-cli/` | grok-3 | 1 model, xAI account |
| Gemini Web | `gemini-web/` | gemini-2.5-pro | Google web session |
| AI Horde | `aihorde/` | Various open models | 29 free models, variable quality |

### Currently Broken (needs re-auth)
| Provider | Status | Fix Required |
|----------|--------|--------------|
| Claude Code (OAuth) | EXPIRED — invalid_grant | See Section E |

### Re-activated Providers (2026-10-10)
| Provider | Prefix | Models | Notes |
|----------|--------|--------|-------|
| Antigravity CLI | `agy/` | 19 models | Free Starter Quota; includes Claude Sonnet/Opus 4.6, Gemini 3.7/3.8 |
| Kiro AI | `kiro/` | 9 models | Credit-based; includes Qwen3 Coder Next, Claude Sonnet 4.5, DeepSeek v3.2 |

### Disabled (not needed)
- Augment CLI (not installed)

---

## C. Routing Configuration

### Current Routing
- **Strategy**: Priority (free models tried first; falls back down the list on failure)
- **Port**: `127.0.0.1:20128` (localhost only — secured)
- **Session affinity**: ENABLED — 2h TTL (global, applies to all combos)

### Active Combo: `my-combo`

| Priority | Model | Provider | Cost | Notes |
|----------|-------|----------|------|-------|
| 1st | `ollama/qwen2.5-coder:7b` | Ollama (local) | FREE | Local — no internet, no quota |
| 2nd | `oc/north-mini-code-free` | OpenCode | Free | Code-specialized |
| 3rd | `oc/deepseek-v4-flash-free` | OpenCode | Free | DeepSeek V4, strong at code |
| 4th | `agy/claude-sonnet-4-6` | Antigravity CLI | Free quota | Claude Sonnet 4.6 via Google Code Assist |
| 5th | `kiro/qwen3-coder-next` | Kiro AI | Low credit | Qwen3 Coder Next, coding-focused (0.1x) |
| 6th | `gweb/gemini-3.7-flash` | Gemini Web | Free | Google web session |
| 7th | `gc/grok-4.6` | Grok CLI | Free | xAI Grok |
| 8th | `cc/claude-sonnet-5-5` | Claude OAuth | Paid | Fallback (re-auth needed) |

All dead model references removed. Both Antigravity CLI and Kiro AI re-activated 2026-10-10.
Ollama (local) added 2026-10-12 at position 1 — always tried first, falls back to cloud on failure.

---

## D. How to Switch Providers Manually

When Claude's usage limit is reached, use one of these model IDs in Claude Code
or any tool pointed at localhost:20128:

```
# In Claude Code (set ANTHROPIC_MODEL env var or use --model flag):
auto/best            # OmniRoute auto-picks the best available provider
trae/claude-3-7      # Trae provider (free quota, resets every few minutes)
codex/gpt-4o         # OpenAI Codex (API billing applies)
grok-cli/grok-3      # xAI Grok (free quota)
aihorde/...          # AI Horde (free, slower)
```

**In your web UI** (http://localhost:3000): just switch the model dropdown to
any working model before sending your message.

---

## E. Re-authenticate Claude Subscription (REQUIRED)

Your Claude OAuth refresh token has permanently expired (invalid_grant).
This means Claude models via OmniRoute will not work until you re-authenticate.

**Steps to fix:**

1. Open the OmniRoute dashboard: http://localhost:20128
2. Log in with your password
3. Go to Dashboard → Providers → Claude Code
4. Click **"Apply auth"** or **"Re-authenticate"** button next to your account
5. A browser window will open — sign in with asmrcctvandpc@gmail.com
6. After sign-in, OmniRoute will show the account as "Active" (green)
7. Click "Test" to confirm it works

After re-auth, the `cc/claude-sonnet-5-5` and other `cc/` model IDs will work
again through OmniRoute.

**Note**: This is separate from your Claude Code app subscription. OmniRoute
needs its own OAuth token to act on behalf of your account.

---

## F. Connect Claude Code to OmniRoute (setup-claude) ✅ DONE

Claude Code profiles were generated on 2026-10-10 for all 28 OmniRoute models.
They are in `~/.claude/profiles/<name>/settings.json`.

**Available profiles:**
```
cc-claude-sonnet-5-5    →  cc/claude-sonnet-5-5   (primary workhorse)
cc-claude-opus-5-5      →  cc/claude-opus-5-5     (most powerful)
cc-claude-haiku-5-5     →  cc/claude-haiku-5-5    (fast/cheap)
cc-claude-fable-5-1     →  cc/claude-fable-5-1    (balanced)
gweb-gemini-3-7-flash   →  gweb/gemini-3.7-flash  (free Gemini)
gc-grok-4-6             →  gc/grok-4.6            (free Grok)
oc-deepseek-v4-flash-free → oc/deepseek-v4-flash-free (free)
tr-gemini-3-1-pro       →  tr/gemini-3.1-pro      (Trae)
... and 20 more
```

**To use a profile (preferred — auto-injects your OmniRoute key):**
```powershell
omniroute launch --profile cc-claude-sonnet-5-5
omniroute launch --profile cc-claude-opus-5-5
```

**To use manually (you must set your OmniRoute key from API Manager):**
```powershell
$env:CLAUDE_CONFIG_DIR="C:\Users\Odyssey Jan Pascual\.claude\profiles\cc-claude-sonnet-5-5"
$env:ANTHROPIC_AUTH_TOKEN="sk-e0834****"   # your omnirouter key
claude
```

**To regenerate profiles** (after adding new providers):
```powershell
omniroute setup-claude --api-key YOUR_OMNIROUTE_KEY_HERE
```

---

## G. Cost Comparison

| Access Method | Cost Structure | When to Use |
|---------------|----------------|-------------|
| Claude subscription (OAuth) | Flat monthly — no per-token cost | Default for all work |
| OpenAI Codex (API key) | Pay per token (~$0.01–0.06 per 1K tokens) | When Claude is rate-limited |
| Trae | Free (with quota) | Light fallback use |
| Grok Build | Free (with limits) | Simple tasks when Claude is limited |
| AI Horde | Free (community compute) | Last resort, slower responses |
| Gemini Web | Free (Google account quota) | Google-related tasks |

**Key rule**: Never use Codex (sk- key) for long conversations without checking
costs first. Use Claude subscription for everything when available.

---

## H. Troubleshooting Guide

### "Authentication required" when using Claude models
**Cause**: Claude OAuth token expired
**Fix**: Section E — Re-authenticate in the dashboard

### "trae 4008: Your requests have exceeded the quota"
**Cause**: Trae has a per-session or per-minute quota
**Fix**: Wait 30–60 seconds and try again, or switch to another provider

### Web UI shows error when using My Combo or Debug Assistant
**Cause**: Was using kr/ (Kiro) model IDs — now fixed in config.json
**Fix**: Already fixed. If still happening, clear browser cache and reload

### OmniRoute server not responding
**Fix**: Double-click "Start OmniRoute.bat" on your Desktop

### Port 20128 shows 0.0.0.0 after reboot (security regression)
**Fix**: Make sure OMNIROUTE_SERVER_HOST=127.0.0.1 is in ~/.omniroute/.env
then run: `omniroute restart`

### Claude Code 502 error: "qwen2.5-coder:7b does not support thinking"
**Cause**: Claude Code always sends `thinking: {"type":"adaptive"}` in every API request.
OmniRoute forwards this to Ollama (qwen2.5-coder:7b) after translating from Anthropic format
to OpenAI format. Ollama rejects it with HTTP 400 because the model has no thinking support.
**Fix applied (2026-10-12)**: Changed the first entry in my-combo from `ollama/qwen2.5-coder:7b`
to `no-think/ollama/qwen2.5-coder:7b` directly in storage.sqlite. The `no-think/` prefix is
OmniRoute's built-in reasoning-suppression alias that strips the thinking parameter before
forwarding. Also added model_capabilities (reasoning=0) and model_capability_overrides
(reasoning=false) rows for ollama-local/qwen2.5-coder:7b as belt-and-suspenders.
**If the error persists** after restarting OmniRoute (meaning `no-think/` doesn't work for Ollama):
Remove the ollama entry from my-combo entirely via the OmniRoute dashboard, or update it via
SQLite: `UPDATE combos SET data = (replace combo JSON to remove the ollama entry) WHERE name='my-combo'`
**Note**: settings.json and settings.local.json do NOT have any thinking-disable option;
Claude Code sends the thinking parameter unconditionally and there is no client-side override.

### "invalid_grant" error in logs
**Cause**: Refresh token expired permanently — cannot be fixed by retrying
**Fix**: Section E — Re-authenticate Claude in dashboard

### Server crashes on startup
**Check logs**: `~/.omniroute/logs/application/app.log`
**Common cause**: Token refresh timeout on slow network at startup (harmless,
server recovers within 2 minutes)

---

## I. Backup and Rollback Instructions

### Your backup is at:
```
C:\Users\Odyssey Jan Pascual\.omniroute\backup_20261010_071825\
```

### Files backed up:
- `.env.bak` — environment variables
- `webui_config.json.bak` — web UI model configuration
- `storage.sqlite.bak` — all provider credentials and settings

### To rollback the .env change:
```powershell
Copy-Item "~\.omniroute\backup_20261010_071825\.env.bak" "~\.omniroute\.env" -Force
omniroute restart
```

### To rollback the web UI config:
```powershell
Copy-Item "~\.omniroute\backup_20261010_071825\webui_config.json.bak" "~\.omniroute\web-ui\config.json" -Force
```

---

## J. Remaining Manual Steps

These items require your direct action:

1. ~~**Re-authenticate Claude**~~ ✅ DONE — Claude OAuth is connected (auto-refreshed)

2. ~~**Set up Claude Code profiles**~~ ✅ DONE — 28 profiles written to `~/.claude/profiles/`
   See Section F for profile names and how to use them.

3. ~~**Enable session affinity**~~ ✅ DONE — `sessionAffinityTtlMs` set to 7,200,000 ms (2 hours)
   Long conversations will now stay locked to one provider for up to 2 hours.

4. ~~**Update my-combo models**~~ ✅ DONE — rebuilt with free-first priority order (2026-10-10)
   Strategy changed from round-robin → priority. All dead model references removed.
   Order: oc/north-mini-code-free → oc/deepseek-v4-flash-free → gweb/gemini-3.7-flash → gc/grok-4.6 → cc/claude-sonnet-5-5

5. **Change dashboard password** ⚠ STILL TODO — you must do this yourself:
   1. Open http://localhost:20128/dashboard/settings/security
   2. Fill in **Current Password**: (the current default)
   3. Fill in **New Password** and **Confirm New Password**
   4. Click **Update Password**

---

## Model Reference Card

### For your PHP/MySQL development work:

| Task | Model to use | Why |
|------|-------------|-----|
| Quick PHP question | `cc/claude-haiku-5-5` | Fast, cheap |
| Debugging PHP/MySQL | `cc/claude-sonnet-5-5` | Strong reasoning |
| Database schema design | `cc/claude-sonnet-5-5` | Good at SQL |
| Security audit of code | `cc/claude-opus-5-5` | Most thorough |
| Writing documentation | `cc/claude-fable-5-1` | Clear writer |
| When Claude is limited | `auto/best` | OmniRoute picks best available |

(All `cc/` models require Claude re-auth first — see Section E)
