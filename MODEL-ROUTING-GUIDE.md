# OmniRoute Model Routing Guide
**Version**: 1.0 | **Created**: 2026-10-12 | **Phase**: 3 (Ollama Integration)

---

## Overview

OmniRoute runs on `http://127.0.0.1:20128` and acts as a drop-in OpenAI-compatible proxy.
All AI tools pointing at port 20128 automatically route requests through the configured combo.

Set this in your tool:
```
Base URL : http://localhost:20128/v1
API Key  : (your OmniRoute API key from the dashboard API Manager)
```

---

## Active Combo: my-combo

Strategy: **priority** — tries each model in order; falls back on failure.

| Priority | Model ID | Provider | Type | Cost |
|----------|----------|----------|------|------|
| 1 (HIGHEST) | `ollama/qwen2.5-coder:7b` | Ollama (local) | Local LLM | FREE — no internet |
| 2 | `oc/north-mini-code-free` | OpenCode | Cloud | Free quota |
| 3 | `oc/deepseek-v4-flash-free` | OpenCode | Cloud | Free quota |
| 4 | `agy/claude-sonnet-4-6` | Antigravity CLI | Cloud | Free quota |
| 5 | `kiro/qwen3-coder-next` | Kiro AI | Cloud | Low credit (0.1x) |
| 6 | `gweb/gemini-3.7-flash` | Gemini Web | Cloud | Free (Google session) |
| 7 | `gc/grok-4.6` | Grok CLI | Cloud | Free (xAI account) |
| 8 | `cc/claude-sonnet-5-5` | Claude OAuth | Cloud | PAID (re-auth needed) |

**Routing rule**: OmniRoute tries ollama first. If Ollama is down or returns an error,
it automatically falls through to cloud providers. No manual intervention required.

---

## Targeting a Specific Model

To bypass my-combo and use one specific provider:

```
# Local Ollama (no internet, fully private)
ollama/qwen2.5-coder:7b

# Free cloud models
oc/north-mini-code-free
oc/deepseek-v4-flash-free
agy/claude-sonnet-4-6
gweb/gemini-3.7-flash
gc/grok-4.6

# Low-cost credit models
kiro/qwen3-coder-next

# Paid (requires Claude re-auth)
cc/claude-sonnet-5-5
cc/claude-opus-5-5
cc/claude-haiku-5-5
```

---

## Ollama Local Provider Details

| Item | Value |
|------|-------|
| Provider ID | `ollama-local` |
| Alias | `ollama` |
| Connection ID | `ce27819e-ede1-474e-bc3d-1087c397bf88` |
| Endpoint | `http://localhost:11434/v1` |
| Model installed | `qwen2.5-coder:7b` (4.7 GB, Qwen2.5 Coder) |
| GPU | RTX 4060 Laptop (8 GB VRAM) |
| Auth required | None (no API key) |
| Internet required | No — runs fully offline |

**How to check if Ollama is running:**
```powershell
Test-NetConnection -ComputerName localhost -Port 11434 -InformationLevel Quiet
# Should return True
```

**How to list installed models:**
```powershell
ollama list
```

**To pull more models:**
```powershell
ollama pull <model-name>
# Then add to my-combo via OmniRoute dashboard or database
```

---

## Provider Prefix Reference

| Prefix | Provider | Notes |
|--------|----------|-------|
| `ollama/` | Ollama (local) | Requires Ollama running on port 11434 |
| `oc/` | OpenCode | Free quota; resets periodically |
| `agy/` | Antigravity CLI | Free Starter quota; ~19 models |
| `kiro/` | Kiro AI | Credit-based; ~9 models |
| `gweb/` | Gemini Web | Google web session token |
| `gc/` | Grok CLI | xAI account token |
| `cc/` | Claude OAuth | Paid Claude subscription (re-auth needed) |
| `codex/` | OpenAI Codex | Pay-per-token API key |
| `trae/` | Trae | Free quota with per-session limit |
| `aihorde/` | AI Horde | Community compute, free but slower |

---

## How to Use with Claude Code

OmniRoute profiles are in `~/.claude/profiles/`. Use with:

```powershell
# Launch using a profile (auto-injects OmniRoute key)
omniroute launch --profile cc-claude-sonnet-5-5

# Or manually set env vars
$env:ANTHROPIC_BASE_URL = "http://localhost:20128"
$env:ANTHROPIC_AUTH_TOKEN = "<your-omniroute-api-key>"
claude
```

To regenerate profiles after adding new providers/models:
```powershell
omniroute setup-claude --api-key <your-omniroute-api-key>
```

---

## Troubleshooting

### Ollama not responding
```
Symptom: my-combo skips to cloud models
Check  : Test-NetConnection localhost 11434
Fix    : Start Ollama — it should auto-start with Windows; if not, run: ollama serve
```

### OmniRoute not running
```
Symptom: Port 20128 not accessible
Fix    : Double-click "Start OmniRoute.bat" on Desktop
```

### Cloud provider rate-limited
```
Symptom: Specific cloud model returns 429 or quota error
Fix    : OmniRoute auto-falls back. If all cloud providers exhausted, ollama is the
         offline backup — it is always tried first anyway.
```

### my-combo returns wrong model
```
Check  : omniroute models --combo my-combo
Fix    : Restart OmniRoute after any database changes
```

---

## Backup Information

| Backup | Path | Created |
|--------|------|---------|
| Ollama integration backup | `~/.omniroute/storage_ollama_backup_20261012_111236.sqlite` | 2026-10-12 |
| Phase 2 backup | `~/.omniroute/backup_20261010_071825/` | 2026-10-10 |

To rollback:
```powershell
Copy-Item "~\.omniroute\storage_ollama_backup_20261012_111236.sqlite" "~\.omniroute\storage.sqlite" -Force
omniroute serve  # restart
```
