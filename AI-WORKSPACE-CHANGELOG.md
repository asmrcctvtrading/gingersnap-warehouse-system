# AI Workspace Changelog

## [Phase 3] - 2026-10-12
### Added
- Registered Ollama as a native OmniRoute provider (`ollama-local`, alias `ollama`)
- Connection ID: `ce27819e-ede1-474e-bc3d-1087c397bf88`
- Configured endpoint: `http://localhost:11434/v1` (set via `providerSpecificData.baseUrl`)
- Added `ollama/qwen2.5-coder:7b` to my-combo at position 1 (highest priority, local/free)
- Created `MODEL-ROUTING-GUIDE.md` with full routing reference
- Backup created before modification: `storage_ollama_backup_20261012_111236.sqlite`
### Technical Notes
- `providerSpecificData.baseUrl` must be set in SQLite for local providers (OmniRoute does not
  use the `localDefault` field from the provider catalog at runtime — it is display-only)
- PowerShell 5.1 ScriptBlock parsing prevents `--provider-specific-data '{...}'` from working;
  the baseUrl was set via direct Python sqlite3 update
- OmniRoute v3.8.51 has first-class Ollama support via the `ollama-local` provider ID
### Route Test Result
- `ollama/qwen2.5-coder:7b` via `http://localhost:20128/v1/chat/completions`: PASS (HTTP 200)
- my-combo now has 8 models: ollama first, cc/claude-sonnet-5-5 last (paid fallback)
### Status
- Ollama integration: COMPLETE
- my-combo updated: COMPLETE (8 models, priority order)
- OmniRoute running: YES — port 20128 active

---

## [Phase 5] - 2026-10-12
### Added
- Created 6 specialized Claude Code subagent definition files
- Deployed to both project-level (`.omniroute/.claude/agents/`) and global (`~/.claude/agents/`) locations
- **ai-team-lead** — Coordinates multi-step tasks, delegates to specialist agents, tracks progress, produces final reports
- **system-architect** — Designs database schemas, API integrations, and system structure; produces implementation plans before major changes
- **php-mysql-developer** — Writes and fixes PHP/MySQL code following secure coding practices (PDO, input validation, RBAC) for all active projects
- **qa-security-auditor** — Read-only code review for SQL injection, XSS, auth flaws, exposed secrets; reports findings with severity and remediation steps
- **local-ai-assistant** — Routes simple tasks (code explanation, drafts, naming) to Ollama qwen2.5-coder:7b locally; always discloses which model was used
- **documentation-reporter** — Writes and updates README files, setup guides, changelogs, and milestone reports; reads code before writing docs
### Technical Notes
- Agent files use YAML frontmatter with `name`, `description`, and `tools` fields as required by the Claude Code Agent SDK
- Global placement (`~/.claude/agents/`) makes all 6 agents available across every project on this machine
- Project placement (`.omniroute/.claude/agents/`) scopes them to the OmniRoute workspace as well
- local-ai-assistant references Ollama at `qwen2.5-coder:7b` consistent with Phase 2/3 setup
### Status
- All 6 agent files: CREATED in both locations
- Project-level path: `C:\Users\Odyssey Jan Pascual\.omniroute\.claude\agents\`
- Global path: `C:\Users\Odyssey Jan Pascual\.claude\agents\`

---

## [Phase 2] - 2026-10-12
### Added
- Pulled local model: qwen2.5-coder:7b (4.7 GB, ID: dae161e27b0e)
- Validated Ollama local API on port 11434
- Ran basic PHP coding inference test
### Status
- Local model inference: PASS
- GPU VRAM confirmed: 4,852 MiB / 8,188 MiB used after inference (RTX 4060 Laptop, CUDA 13.0)

---

## [Phase 1] - 2026-10-12
### Added
- Phase 1 audit completed (2026-10-12)
- `AI-WORKSPACE-AUDIT.md` — full environment snapshot
- `AI-WORKSPACE-PLAN.md` — Phase 2 setup plan pending user approval

## Notes

All Phase 1 changes were read-only.
No installations, modifications, or deletions were made during the Phase 1 audit.
