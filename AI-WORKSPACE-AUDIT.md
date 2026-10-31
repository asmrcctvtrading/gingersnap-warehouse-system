# AI Workspace Audit — Phase 1
**Audit Date**: 2026-10-12  
**Audit Time**: ~09:30 PHT (UTC+8)  
**Audited by**: Claude Code automated read-only audit  
**Working Directory**: `C:\Users\Odyssey Jan Pascual\.omniroute`

---

## 1. System Specifications

| Component | Detail |
|-----------|--------|
| **OS** | Windows 11 Home Single Language (Build 22621 — Windows 11 22H2) |
| **RAM** | 15.70 GB physical (16 GB nominal) |
| **CPU** | Intel Core i5-13500HX — 14 cores, 2.5 GHz base clock |
| **GPU (Primary)** | NVIDIA GeForce RTX 4060 Laptop GPU — AdapterRAM reports ~4 GB via WMI* |
| **GPU (Integrated)** | Intel UHD Graphics — ~2 GB shared system memory |
| **Disk C: Used** | ~438 GB |
| **Disk C: Free** | ~37.6 GB (~8% free — **low**) |

> *WMI AdapterRAM is a known under-reporter for NVIDIA cards. The RTX 4060 Laptop GPU is
> shipped with 8 GB GDDR6 VRAM as its standard configuration. Actual usable VRAM for
> local inference is likely 8 GB. Verify with `nvidia-smi` for a precise reading.

---

## 2. Installed Tool Versions

| Tool | Version | Status |
|------|---------|--------|
| **Node.js** | v24.21.0 | Installed, in PATH |
| **npm** | v11.19.0 | Installed (npm v12.2.0 available — optional upgrade) |
| **Claude Code** | v2.1.286 | Installed as Desktop App — `claude` CLI NOT in PATH |
| **OmniRoute** | v3.8.51 | Installed as npm global, in PATH |
| **Ollama** | v0.40.3 | Installed, in PATH |

---

## 3. OmniRoute State

### Installation
- **Method**: npm global package (`npm install -g omniroute`)
- **Install path**: Detected via `npm list -g`
- **Config directory**: `C:\Users\Odyssey Jan Pascual\.omniroute\`
- **Guide file**: `C:\Users\Odyssey Jan Pascual\.omniroute\OMNIROUTE_GUIDE.md` (last updated 2026-10-10)

### Runtime Status
- **Running**: YES — confirmed via port check (TCP 20128 responds on IPv4)
- **PID files present**: `server/.pid` and `supervisor/.pid` both dated 2026-10-12 08:21
- **Port**: 127.0.0.1:20128 (localhost only — security-hardened; OMNIROUTE_SERVER_HOST set in .env)
- **Session affinity**: ENABLED — 2-hour TTL

### Provider Inventory

| Provider | Prefix | Model Count | Status |
|----------|--------|-------------|--------|
| OpenAI Codex | `codex/` | ~45 models | Working (API subscription) |
| Trae | `trae/` | ~9 models | Working (free quota, rate-limited) |
| Grok Build | `grok-cli/` | ~1 model | Working (free xAI account) |
| Gemini Web | `gemini-web/` | ~1 model | Working (Google web session) |
| AI Horde | `aihorde/` | ~29 models | Working (free, variable quality) |
| Antigravity CLI | `agy/` | ~19 models | Working (re-activated 2026-10-10) |
| Kiro AI | `kiro/` | ~9 models | Working (credit-based) |
| Claude Code OAuth | `cc/` | ~8+ models | **BROKEN — OAuth token expired** |

> Note: Provider prefixes differ from memory notes. Grok is `grok-cli/` not `gc/`;
> OpenCode is not listed — replaced by `codex/` and `trae/`.

### Active Combo: `my-combo`

| Priority | Model ID | Provider | Cost |
|----------|----------|----------|------|
| 1st | `oc/north-mini-code-free` | OpenCode | Free |
| 2nd | `oc/deepseek-v4-flash-free` | OpenCode | Free |
| 3rd | `agy/claude-sonnet-4-6` | Antigravity CLI | Free quota |
| 4th | `kiro/qwen3-coder-next` | Kiro AI | Low credit (0.1x) |
| 5th | `gweb/gemini-3.7-flash` | Gemini Web | Free |
| 6th | `gc/grok-4.6` | Grok CLI | Free |
| 7th | `cc/claude-sonnet-5-5` | Claude OAuth | Paid (broken) |

**Strategy**: Priority (free models first, cascades on failure)

### Claude Code Profiles
- **Total profiles**: 30 profiles in `C:\Users\Odyssey Jan Pascual\.claude\profiles\`
- **Includes**: cc-*, gweb-*, gc-*, oc-*, tr-*, aihorde-*, auto-best, my-combo
- **Global settings.json**: Configured — `ANTHROPIC_BASE_URL=http://localhost:20128`, `ANTHROPIC_AUTH_TOKEN=local`, `model=my-combo`

---

## 4. Claude Code Configuration

| Item | Detail |
|------|--------|
| **Version** | v2.1.286 (Desktop App) |
| **CLI in PATH** | NO — `claude` command not recognized in PowerShell |
| **Config directory** | `C:\Users\Odyssey Jan Pascual\.claude\` |
| **settings.json** | Present — points to OmniRoute at localhost:20128 |
| **settings.local.json** | Present |
| **Profiles directory** | Present — 30 profiles |
| **agents/ directory** | NOT FOUND (neither in `~/.claude/` nor `~/.omniroute/.claude/`) |
| **plugins/ directory** | Present in `~/.claude/plugins/` |
| **skills/ directory** | Present in `~/.claude/skills/` |
| **CLAUDE.md** | Present in `~/.claude/` |

---

## 5. Ollama State

| Item | Detail |
|------|--------|
| **Installed** | YES — v0.40.3 |
| **Running** | YES — port 11434 responds on IPv4 |
| **Local models** | 1 entry: `deepseek-v4.1-flash:cloud` (SIZE: `-`) |
| **True local weights** | NONE — the `:cloud` suffix and zero size indicate this is a cloud-backed alias, not locally stored weights |

---

## 6. Gaps and Risks Identified

| # | Severity | Item | Detail |
|---|----------|------|--------|
| 1 | HIGH | Claude OAuth expired | `cc/` models non-functional; cc-* profiles exist but fail |
| 2 | HIGH | Disk space low | ~37.6 GB free (~8%) — insufficient for multiple large local models |
| 3 | MEDIUM | Claude Code CLI not in PATH | `claude` command unavailable in PowerShell; launched only via desktop app |
| 4 | MEDIUM | No local Ollama models | Only cloud-backed alias present; no GPU-accelerated local inference active |
| 5 | MEDIUM | No agents/ directory | Multi-agent subagent infrastructure not initialized |
| 6 | LOW | Dashboard default password | Security risk — admin console still on factory credentials (Section J in guide) |
| 7 | LOW | WMI GPU VRAM mis-report | WMI shows ~4 GB for RTX 4060; actual is likely 8 GB — verify with `nvidia-smi` |
| 8 | INFO | npm major upgrade available | npm v12.2.0 available; current v11.19.0 is functional but not latest |

---

## Phase 2 Results
**Date**: 2026-10-12  
**Executed by**: Claude Code automated Phase 2 agent

### Step 1 — Ollama API Validation

- Endpoint `http://localhost:11434/` response: `Ollama is running`
- Endpoint `http://localhost:11434/api/tags` response: Valid JSON; 1 model listed (`deepseek-v4.1-flash:cloud`, SIZE `-`)
- **Result**: Ollama API reachable and responding correctly before pull

### Step 2 — Model Pull: qwen2.5-coder:7b

| Item | Detail |
|------|--------|
| **Model** | qwen2.5-coder:7b |
| **Model ID** | dae161e27b0e |
| **Total size** | 4.7 GB |
| **SHA256 digest** | Verified (Ollama confirmed "success") |
| **Download speed** | ~1.5–1.9 MB/s average (variable; brief stalls at ~10 KB/s then recovered) |
| **Total download time** | ~75 minutes (includes two task-limit interruptions; Ollama resumed from checkpoint each time) |
| **Result** | SUCCESS — model verified and listed in `ollama list` |

### Step 3 — Model Verification

`ollama list` output after pull:
```
NAME                         ID              SIZE      MODIFIED
qwen2.5-coder:7b             dae161e27b0e    4.7 GB    Less than a second ago
deepseek-v4.1-flash:cloud    e04da138d31e    -         2 hours ago
```

### Step 4 — Inference Test

**Prompt**: "Write a PHP function that safely inserts a user record into MySQL using PDO prepared statements. Return only the function, no explanation."

**Model output (first 10 lines)**:
```php
function insertUser($username, $email, $password) {
    $pdo = new PDO('mysql:host=localhost;dbname=yourdatabase', 'username', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':password', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
```

**Assessment**: PASS — The model produced valid, syntactically correct PHP using PDO prepared statements with named parameters, proper error mode, correct `bindParam` types, and even included `password_hash()` for secure password storage.

### Step 5 — GPU VRAM Confirmation

`nvidia-smi` output (post-inference):
```
NVIDIA GeForce RTX 4060 Laptop GPU  |  4852MiB / 8188MiB  |  0%
```

| Item | Detail |
|------|--------|
| **VRAM Total** | 8,188 MiB (8 GB — confirms WMI mis-report from Phase 1 audit) |
| **VRAM Used** | 4,852 MiB (~59%) |
| **GPU Util** | 0% (post-inference idle; model kept loaded in VRAM) |
| **CUDA Version** | 13.0 |
| **Driver Version** | 581.29 |
| **Result**: | Model loaded into GPU VRAM — inference ran on GPU, not CPU |

### Phase 2 Summary

| Check | Status |
|-------|--------|
| Ollama API reachable | PASS |
| Model qwen2.5-coder:7b pulled | PASS |
| Model listed in `ollama list` | PASS |
| PHP coding inference test | PASS |
| GPU VRAM utilization confirmed | PASS |
| Disk space sufficient | PASS (4.7 GB used; ~33 GB remaining on C:) |

**Phase 2 overall**: COMPLETE — local GPU-accelerated inference is operational.
