# AI Workspace Plan — Phase 2 Setup
**Based on**: Phase 1 Audit (2026-10-12)  
**Status**: Pending user approval — nothing has been installed or modified

---

## Summary of Current State

OmniRoute v3.8.51 is running and routing correctly. Claude Code is installed as a desktop
app with 30 provider profiles. Ollama v0.40.3 is installed and running but has no local
model weights. The agents/ infrastructure for multi-agent workflows does not yet exist.
Disk space (37.6 GB free) is the primary constraint for local model setup.

---

## Phase 2 Objectives

1. Fix Claude OAuth (blocked models)
2. Add a local coding model via Ollama with GPU acceleration
3. Integrate Ollama into OmniRoute routing
4. Initialize the agents/ directory for multi-agent workflows
5. Fix the Claude Code CLI PATH issue
6. Secure the OmniRoute dashboard

---

## Step-by-Step Plan

### Step A — Fix Claude OAuth (REQUIRES USER ACTION)

**Why**: The `cc/` prefix (8+ models including claude-sonnet-5-5 and claude-opus-5-5) is
currently broken. These are the most capable models in the combo.

**Action required by user** (cannot be automated):
1. Open http://localhost:20128/dashboard
2. Navigate to Providers → Claude Code
3. Click Re-authenticate and sign in with your Google/Anthropic account
4. Confirm cc/ models show "Active" status

---

### Step B — Free Up or Confirm Disk Space

**Why**: ~37.6 GB free. A local 7B coding model needs 4–5 GB compressed. Running the
model (inference state) needs additional RAM swap. Tight but workable for one model.

**Prerequisite**: Before pulling any Ollama model, confirm at least 8 GB free disk.
Run `Get-PSDrive C` to verify current state.

**Option B1 (recommended)**: Proceed with the audit showing 37.6 GB free — one small
model (7B, q4) will fit.

**Option B2**: Delete temporary or old backup directories if space becomes an issue:
- `backup_20261010_071825` — 2-day-old backup, no longer critical
- `backup_20261010_081809` — same session backup

> Requires user approval before deleting any backups.

---

### Step C — Pull a Local Coding Model for Ollama

**Hardware assessment**:
- VRAM: RTX 4060 Laptop GPU, likely 8 GB (WMI under-reported as 4 GB — verify with
  `nvidia-smi`)
- RAM: 16 GB (13B models feasible with CPU offload)

**Recommended models** (ranked by fit for your use case):

| Rank | Model | Size (q4) | VRAM fit | Strengths |
|------|-------|-----------|----------|-----------|
| 1st | `qwen2.5-coder:7b` | ~4.5 GB | Yes (8 GB) | Best 7B coder, PHP/JS/SQL strong |
| 2nd | `deepseek-coder-v2:16b` | ~9 GB | With offload | Excellent coder, needs 16 GB RAM |
| 3rd | `codellama:7b` | ~3.8 GB | Yes | Stable, widely tested, older |
| 4th | `phi4:14b` | ~8 GB | With offload | General purpose, good reasoning |

**Recommendation**: Start with `qwen2.5-coder:7b`. It fits in 8 GB VRAM fully, leaves
RAM free for other work, and is strong on PHP, MySQL, and JavaScript — your primary stack.

**Pull command** (run when approved):
```powershell
ollama pull qwen2.5-coder:7b
```

> Requires user approval. Download size ~4.5 GB. Disk must have 8+ GB free before running.

---

### Step D — Integrate Ollama into OmniRoute

**Current state**: OmniRoute v3.8.51 is running. Ollama is running on localhost:11434.
OmniRoute supports adding providers via the dashboard.

**Integration path**:

1. Open http://localhost:20128/dashboard
2. Navigate to Providers → Add Provider
3. Look for "Ollama" or "Custom / Local OpenAI-compatible" option
4. If Ollama is a native type, select it and enter: `http://localhost:11434`
5. If only "OpenAI-compatible" is available, use: base URL = `http://localhost:11434/v1`,
   API key = `ollama` (any string — Ollama does not verify)
6. After adding, the prefix will be something like `ollama/` or a custom name you set
7. Add `ollama/qwen2.5-coder:7b` as position 1 in `my-combo` (cheapest, fastest, local)

**Fallback**: If OmniRoute v3.8.51 does not support direct Ollama integration via the
dashboard, a direct local HTTP call to Ollama (port 11434) can be used from Claude Code
without OmniRoute routing, using `ANTHROPIC_BASE_URL=http://localhost:11434/v1`.

> Requires user approval and manual steps in OmniRoute dashboard.

---

### Step E — Create agents/ Directory for Multi-Agent Setup

**Current state**: No `agents/` directory exists in either `~/.claude/` or
`~/.omniroute/.claude/`.

**What this enables**: Defines specialized sub-agents (coding assistant, research agent,
PHP debugger, SEO analyst, etc.) that Claude Code can dispatch automatically.

**Action plan** (when approved):
1. Create `C:\Users\Odyssey Jan Pascual\.claude\agents\` directory
2. Write individual agent `.md` files per role (e.g., `php-coder.md`, `seo-analyst.md`,
   `sql-designer.md`)
3. Each agent file specifies: model to use, tools available, system instructions, and scope

**Suggested agents for your projects**:

| Agent File | Purpose | Model Suggestion |
|------------|---------|-----------------|
| `php-coder.md` | PHP/MySQL development (PickleCam, Gingersnaps, ASMR site) | my-combo or ollama/qwen2.5-coder:7b |
| `seo-analyst.md` | Local SEO audit tasks for ASMR CCTV | agy/claude-sonnet-4-6 |
| `sql-designer.md` | Database schema and query design | cc/claude-sonnet-5-5 (after re-auth) |
| `local-coder.md` | Offline/low-cost coding tasks | ollama/qwen2.5-coder:7b |

> Requires user approval. Files will be created one at a time for review.

---

### Step F — Fix Claude Code CLI in PATH

**Current state**: `claude` command not found in PowerShell. Claude Code v2.1.286 is
installed as a Desktop App and launches via GUI only.

**Options**:

| Option | Method | Notes |
|--------|--------|-------|
| A | Add Desktop App's CLI bin to PATH | Locate `claude.exe` in `%LOCALAPPDATA%\AnthropicClaude` and add to user PATH |
| B | Install Claude Code via npm | `npm install -g @anthropic-ai/claude-code` — clean CLI, no GUI overlay |
| C | Accept current state | Desktop app works; CLI not strictly needed if all work done in GUI |

**Recommendation**: Option A or B. Having `claude` in PATH enables scripting,
hooks, and scheduled automation from terminal.

> Requires user approval for Option B (npm install). Option A requires reading the
> actual install path first.

---

### Step G — Secure OmniRoute Dashboard Password

**Current state**: Admin password is still on factory default (flagged in OMNIROUTE_GUIDE.md
Section J as STILL TODO).

**Action required by user** (must be done manually — do not share password with Claude):
1. Open http://localhost:20128/dashboard/settings/security
2. Update password to a strong unique password
3. Store in your password manager

> This is a local-only server (127.0.0.1 only) so the risk is confined to your machine,
> but it should still be changed before any network changes.

---

## Prerequisites Summary

| Step | Requires User Action | Can Claude Do It |
|------|---------------------|-----------------|
| A — Fix Claude OAuth | YES (manual browser sign-in) | NO |
| B — Confirm disk space | YES (verify before model pull) | Read-only check only |
| C — Pull Ollama model | YES (approval + download) | Can run command after approval |
| D — Add Ollama to OmniRoute | YES (dashboard UI interaction) | Can assist; user clicks |
| E — Create agents/ dir | YES (approval per agent file) | YES, after approval |
| F — Fix Claude Code PATH | YES (approval for install/PATH change) | YES, after approval |
| G — Change dashboard password | YES (must be done by user) | NO |

---

## Risks and Prerequisites

1. **Disk space**: Pull no Ollama model until disk free is confirmed >= 8 GB
2. **VRAM verification**: Run `nvidia-smi` before model selection to confirm 8 GB GPU
3. **OAuth re-auth first**: Do Step A first — cc/ models are the best fallback in combos
4. **Backup before changes**: `backup_20261010_081809` exists from last session; consider
   creating a fresh backup before any OmniRoute config changes
5. **Ollama model quality**: deepseek-v4.1-flash:cloud (current) is a cloud alias —
   do not delete it before confirming a local model works end-to-end
