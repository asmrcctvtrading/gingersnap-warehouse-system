# Agent Reach Installation & Integration Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Install and configure Agent Reach into the Claude Code environment, integrate it with the existing agent roster (Atlas, Rex, Rhoda, Sage, Bolt, Nova, Iris), register it as a global skill, and enable supported research channels without overwriting existing configurations.

**Architecture:** Phase 1 inspects and documents the current environment (Claude Code version, Python/Git/uv availability, existing agent configs, current skills). Phase 2 backs up critical configs, installs Agent Reach per official repo, and registers the skill globally. Phase 3 configures research channels (web, GitHub, Reddit, YouTube), tests each channel, and updates global instructions. Phase 4 verifies integration with existing agents and documents the results.

**Tech Stack:** Windows 11, PowerShell 5.1, Python 3.10+, Git, uv package manager, Claude Code CLI, Agent Reach (https://github.com/Panniantong/Agent-Reach)

**Spec:** Pasted user task (2026-10-30)

## Global Constraints

- Do NOT overwrite existing agent configs (Atlas, Rex, Rhoda, Sage, Bolt, Nova, Iris)
- Back up `~/.claude/CLAUDE.md` and `~/.claude/agents/` before any changes
- Use only official Agent Reach repo; no forks or modified scripts
- For channels requiring auth, ask user for approval before configuring
- Do NOT expose tokens, keys, cookies, or passwords in logs/transcripts
- Test each research channel after configuration
- Windows PowerShell must be able to run `agent-reach` commands after install

## Review Focus

1. **Environment Detection:** Agent Reach requires Python 3.9+, Git, and uv to be installed. If any prerequisite is missing, installation will fail. The plan must verify each and abort with clear guidance if versions are insufficient.

2. **Existing Agent Interference:** The existing agent roster uses custom models, tools, and responsibilities. Adding Agent Reach as a global skill or capability must not modify their system prompts, tool access, or roles. Verify each agent still functions after integration.

3. **Skill Registration Location:** Agent Reach must be registered in the correct global Claude Code location (`~/.claude/skills/`) so all agents can access it, not copied into individual agent directories. Duplicate installations break updates.

4. **Research Channel Fallback:** Not all research channels may be available on Windows or may require specific authentication. The plan must handle channels that fail silently and document which channels work vs. require manual setup.

5. **Global Instruction Consistency:** Updating `~/.claude/CLAUDE.md` to reference Agent Reach must not conflict with existing instructions about agent dispatch preferences (user prefers sub-agents), output optimization, or caveman mode. Instructions must be additive, not contradictory.

---

## Phase 1: Environment Inspection & Documentation

### Task 1: Inspect Windows Environment & Verify Prerequisites

**Files:**
- Create: `./AGENT_REACH_INSTALL_LOG.md` (temporary inspection log)
- Reference: `~/.claude/` (do NOT modify yet)
- Reference: `~/.claude/agents/` (do NOT modify yet)

**Interfaces:**
- Consumes: None (starting point)
- Produces: Environment snapshot, prerequisite verification status, list of existing agents

- [ ] **Step 1: Document current system information**

Run in PowerShell:
```powershell
Write-Host "=== System Info ==="
[System.Environment]::OSVersion
Get-Host | Select-Object Version
Write-Host "=== Claude Code Version ==="
claude --version
Write-Host "=== Python Version ==="
python --version
Write-Host "=== Git Version ==="
git --version
Write-Host "=== uv Version ==="
uv --version
Write-Host "=== Claude Code Config Location ==="
$env:USERPROFILE + "\.claude"
```

Capture full output into `AGENT_REACH_INSTALL_LOG.md` with timestamp.

Expected: All commands run without fatal errors. Note any missing tools.

- [ ] **Step 2: Verify Python 3.9+ is installed**

Run: `python --version | Select-String "3\.(9|10|11|12)"`
Expected: PASS (Python 3.9 or higher detected)

If FAIL: Document the version found and note that uv install will fail.

- [ ] **Step 3: Verify Git is installed and accessible**

Run: `git --version`
Expected: Git version 2.0 or higher

If FAIL: Note that Agent Reach installation may fail.

- [ ] **Step 4: Verify uv is installed**

Run: `uv --version`
Expected: uv version output

If FAIL: Note that uv must be installed before Agent Reach (instructions in Step 5 of Task 2).

- [ ] **Step 5: List existing agents in `~/.claude/agents/`**

Run:
```powershell
$agentsDir = "$env:USERPROFILE\.claude\agents"
if (Test-Path $agentsDir) {
    Get-ChildItem -Path $agentsDir -Filter "*.md" | Select-Object Name
} else {
    Write-Host "No agents directory found"
}
```

Capture the list. Expected: Output shows Atlas, Rex, Rhoda, Sage, Bolt, Nova, Iris (or subset thereof).

- [ ] **Step 6: Check current `~/.claude/CLAUDE.md`**

Run:
```powershell
$claudeMd = "$env:USERPROFILE\.claude\CLAUDE.md"
if (Test-Path $claudeMd) {
    Write-Host "CLAUDE.md exists"; (Get-Item $claudeMd).LastWriteTime
} else {
    Write-Host "No global CLAUDE.md found"
}
```

Expected: File exists with recent timestamp.

- [ ] **Step 7: Check `~/.claude/skills/` directory**

Run:
```powershell
$skillsDir = "$env:USERPROFILE\.claude\skills"
if (Test-Path $skillsDir) {
    Get-ChildItem -Path $skillsDir -Directory | Select-Object Name
} else {
    Write-Host "No skills directory"
}
```

Expected: Output shows existing global skills (if any).

- [ ] **Step 8: Check if Agent Reach is already installed**

Run:
```powershell
$agentReachDir = "$env:USERPROFILE\.claude\skills\agent-reach"
if (Test-Path $agentReachDir) {
    Write-Host "Agent Reach found at: $agentReachDir"
    Get-ChildItem -Path $agentReachDir -Recurse -Name | head -20
} else {
    Write-Host "Agent Reach not found"
}
```

Expected: If found, note the version/structure. If not found, proceed to Task 2.

- [ ] **Step 9: Create environment snapshot in log**

Document findings:
- Python version (must be 3.9+)
- Git version
- uv version
- Existing agents (count and names)
- CLAUDE.md location and timestamp
- Existing global skills
- Agent Reach installation status

Save to `AGENT_REACH_INSTALL_LOG.md`.

- [ ] **Step 10: Commit inspection documentation**

```bash
git add AGENT_REACH_INSTALL_LOG.md
git commit -m "docs: document pre-installation environment state"
```

Expected: Commit succeeds. Log is preserved for reference.

---

### Task 2: Back Up Existing Configurations

**Files:**
- Create: `./BACKUP_PRE_AGENT_REACH_<DATE>.tar.gz` (Windows-compatible zip or tar backup)
- Reference: `~/.claude/CLAUDE.md`
- Reference: `~/.claude/agents/`
- Reference: `~/.claude/skills/`

**Interfaces:**
- Consumes: Environment snapshot from Task 1
- Produces: Backup archive with timestamp, backup verification log

- [ ] **Step 1: Create backup directory with timestamp**

Run:
```powershell
$backupDate = Get-Date -Format "yyyyMMdd_HHmmss"
$backupDir = "$env:USERPROFILE\.claude\BACKUP_AGENT_REACH_$backupDate"
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
Write-Host "Backup directory: $backupDir"
```

Expected: Directory created successfully.

- [ ] **Step 2: Back up CLAUDE.md**

Run:
```powershell
$claudeMd = "$env:USERPROFILE\.claude\CLAUDE.md"
if (Test-Path $claudeMd) {
    Copy-Item -Path $claudeMd -Destination "$backupDir\CLAUDE.md" -Force
    Write-Host "Backed up CLAUDE.md"
} else {
    Write-Host "CLAUDE.md not found (skipped)"
}
```

Expected: File copied to backup directory.

- [ ] **Step 3: Back up agents directory**

Run:
```powershell
$agentsDir = "$env:USERPROFILE\.claude\agents"
if (Test-Path $agentsDir) {
    Copy-Item -Path $agentsDir -Destination "$backupDir\agents" -Recurse -Force
    Write-Host "Backed up agents directory"
} else {
    Write-Host "agents directory not found (skipped)"
}
```

Expected: Entire agents folder copied.

- [ ] **Step 4: Back up skills directory**

Run:
```powershell
$skillsDir = "$env:USERPROFILE\.claude\skills"
if (Test-Path $skillsDir) {
    Copy-Item -Path $skillsDir -Destination "$backupDir\skills" -Recurse -Force
    Write-Host "Backed up skills directory"
} else {
    Write-Host "skills directory not found (skipped)"
}
```

Expected: Entire skills folder copied (may be empty or sparse).

- [ ] **Step 5: Verify backup completeness**

Run:
```powershell
Write-Host "Backup contents:"
Get-ChildItem -Path $backupDir -Recurse | Select-Object FullName, Length
```

Expected: Output shows backed-up files with sizes.

- [ ] **Step 6: Create backup manifest**

Document:
- Backup location
- Files backed up (CLAUDE.md, agents/, skills/)
- Timestamp
- Total size
- Verification checksum (optional)

Save to `BACKUP_PRE_AGENT_REACH_<DATE>_manifest.txt` in backup directory.

- [ ] **Step 7: Commit backup record**

```bash
git add AGENT_REACH_INSTALL_LOG.md
git commit -m "backup: preserve .claude configs before Agent Reach install"
```

Expected: Backup documentation committed. Backup directory exists outside git (reference only in log).

---

## Phase 2: Install Agent Reach

### Task 3: Install Agent Reach from Official Repository

**Files:**
- Create: `~/.claude/skills/agent-reach/` (official installation directory)
- Reference: https://github.com/Panniantong/Agent-Reach (official repo)
- Reference: `./AGENT_REACH_INSTALL_LOG.md` (append installation steps)

**Interfaces:**
- Consumes: Verified environment (Python 3.9+, Git, uv installed)
- Produces: Agent Reach installed in global skills directory, skill available to Claude Code

- [ ] **Step 1: Ensure uv is installed**

If uv is not installed (from Task 1), install it:

Run:
```powershell
# Check if uv is installed
try {
    uv --version
    Write-Host "uv is already installed"
} catch {
    Write-Host "Installing uv..."
    # uv is installed via pip or curl, depending on platform
    # Windows recommended: use pip
    pip install uv
    uv --version
}
```

Expected: uv version output confirms installation.

- [ ] **Step 2: Clone Agent Reach repository**

Run:
```powershell
$tempDir = "$env:TEMP\agent-reach-temp"
if (Test-Path $tempDir) {
    Remove-Item -Path $tempDir -Recurse -Force
}
git clone https://github.com/Panniantong/Agent-Reach.git $tempDir
cd $tempDir
Write-Host "Agent Reach cloned to: $tempDir"
```

Expected: Repository cloned successfully, `.git` folder present.

- [ ] **Step 3: Review official installation instructions**

Read `$tempDir\README.md` or `$tempDir\INSTALL.md` (or equivalent) from the official repo.

Document:
- Required dependencies (list exact Python packages)
- Installation command (exact `uv` or `pip` command from official docs)
- Installation location (does it go in `~/.claude/skills/` or elsewhere?)
- Configuration steps (if any)
- Supported research channels

Append findings to `AGENT_REACH_INSTALL_LOG.md`.

**Do not deviate from official instructions.** If instructions differ from this plan, use the official instructions and update this plan step.

- [ ] **Step 4: Run official installation command**

Based on official instructions, run the exact installation command. Example (adjust per official docs):

```powershell
cd $tempDir
uv sync
# or
pip install -e .
```

Expected: No errors; installation completes successfully.

- [ ] **Step 5: Verify Agent Reach executable is accessible**

Run:
```powershell
agent-reach --version
# or
agent-reach --help
```

Expected: Command runs; version or help output displayed.

If FAIL: Check installation location, ensure scripts are in PATH, or adjust PATH manually.

- [ ] **Step 6: Copy Agent Reach to global skills directory**

Run:
```powershell
$skillsDir = "$env:USERPROFILE\.claude\skills\agent-reach"
New-Item -ItemType Directory -Path $skillsDir -Force | Out-Null

# Copy entire Agent Reach installation
Copy-Item -Path "$tempDir\*" -Destination $skillsDir -Recurse -Force -Exclude @(".git", "__pycache__")

Write-Host "Agent Reach copied to: $skillsDir"
```

Expected: Files copied successfully; no errors.

- [ ] **Step 7: Verify Agent Reach skill structure**

Run:
```powershell
$skillDir = "$env:USERPROFILE\.claude\skills\agent-reach"
Write-Host "Skill contents:"
Get-ChildItem -Path $skillDir -Name | head -20
```

Expected: Output shows Agent Reach files (README, config files, Python modules, etc.).

- [ ] **Step 8: Test Agent Reach from new location**

Run:
```powershell
# Test that Agent Reach is callable from the installed location
$env:PATH += ";$env:USERPROFILE\.claude\skills\agent-reach\bin"
agent-reach --help
```

Expected: Help output displayed; no errors about missing modules.

- [ ] **Step 9: Clean up temporary installation**

Run:
```powershell
Remove-Item -Path "$env:TEMP\agent-reach-temp" -Recurse -Force
Write-Host "Temporary installation files removed"
```

Expected: Temp directory deleted.

- [ ] **Step 10: Document installation completion**

Append to `AGENT_REACH_INSTALL_LOG.md`:
- Installation date/time
- Installation location: `~/.claude/skills/agent-reach/`
- Version installed
- Verification: `agent-reach --version` output
- Status: INSTALLED ✓

- [ ] **Step 11: Commit Agent Reach installation**

```bash
git add AGENT_REACH_INSTALL_LOG.md
git commit -m "feat: install Agent Reach from official repository"
```

Expected: Installation logged and committed.

---

### Task 4: Register Agent Reach Skill with Claude Code

**Files:**
- Create: `~/.claude/skills/agent-reach/SKILL.md` (if not present; spec and metadata)
- Modify: `~/.claude/CLAUDE.md` (add reference to Agent Reach skill)
- Reference: `~/.claude/` (global skill registry)

**Interfaces:**
- Consumes: Agent Reach installed in `~/.claude/skills/agent-reach/`
- Produces: Agent Reach skill registered and available to all agents

- [ ] **Step 1: Check if SKILL.md exists in Agent Reach**

Run:
```powershell
$skillMd = "$env:USERPROFILE\.claude\skills\agent-reach\SKILL.md"
if (Test-Path $skillMd) {
    Write-Host "SKILL.md exists"
    Get-Content $skillMd | head -30
} else {
    Write-Host "SKILL.md not found; will create placeholder"
}
```

Expected: If file exists, review its content. If not, proceed to Step 2.

- [ ] **Step 2: Create/update SKILL.md if needed**

If SKILL.md does not exist, create one (or ask user to provide):

```markdown
---
name: agent-reach
description: Research and investigation skill - web search, GitHub repos, Reddit, YouTube transcripts
type: skill
---

# Agent Reach

Agent Reach is an external research tool that provides access to multiple information channels.

## Supported Channels

- Web search and webpage reading
- GitHub repository research
- Reddit community research
- YouTube search and transcript retrieval

## Usage

Trigger: `agent-reach [channel] [query]`

## Configuration

Channels requiring authentication must be configured in `config/` directory.
```

Save to `~/.claude/skills/agent-reach/SKILL.md`.

Expected: SKILL.md created or updated.

- [ ] **Step 3: Verify Claude Code recognizes the skill**

Test with Claude Code CLI (if available):

```powershell
claude skills list
```

Expected: Output includes "agent-reach" or similar.

If FAIL: Verify directory structure and permissions.

- [ ] **Step 4: Add Agent Reach reference to global CLAUDE.md**

Read current `~/.claude/CLAUDE.md`. Add a new section or append reference to existing instructions:

```markdown
## Agent Reach

Use Agent Reach skill for external research when needed:
- Web search and webpage reading
- GitHub repository research
- Reddit community troubleshooting
- YouTube transcripts for technical tutorials

Trigger: `agent-reach [channel] [query]` or dispatch to sub-agents with research requirements.
```

Append this to the file (do NOT remove existing content).

- [ ] **Step 5: Verify CLAUDE.md is syntactically valid**

Run:
```powershell
$claudeMd = "$env:USERPROFILE\.claude\CLAUDE.md"
Get-Content $claudeMd | Measure-Object -Line
Write-Host "File size OK; no truncation"
```

Expected: File is readable; word count increased slightly.

- [ ] **Step 6: Test that agents can access Agent Reach**

Manually verify one of the existing agents (e.g., Atlas or Bolt) can "see" the Agent Reach skill in the system prompt or available skills list (subject to actual Claude Code API behavior).

If Claude Code does not auto-register skills, note manual registration steps required.

- [ ] **Step 7: Commit skill registration**

```bash
git add ~/.claude/CLAUDE.md
git add ~/.claude/skills/agent-reach/SKILL.md
git commit -m "feat: register Agent Reach skill globally for all agents"
```

Expected: Changes committed successfully.

---

## Phase 3: Configure Research Channels

### Task 5: Configure & Test Web Search and Webpage Reading

**Files:**
- Reference: `~/.claude/skills/agent-reach/config/` (configuration directory)
- Create: `./CHANNEL_TEST_LOG.md` (test results)

**Interfaces:**
- Consumes: Agent Reach installed and registered
- Produces: Web search channel verified working

- [ ] **Step 1: Review Agent Reach web search configuration**

Run:
```powershell
$configDir = "$env:USERPROFILE\.claude\skills\agent-reach\config"
if (Test-Path $configDir) {
    Get-ChildItem -Path $configDir -Name
} else {
    Write-Host "Config directory not found; may be created on first use"
}
```

Expected: Configuration files listed (if any).

- [ ] **Step 2: Test web search (no auth required)**

Run a test query using Agent Reach's web search:

```powershell
cd "$env:USERPROFILE\.claude\skills\agent-reach"
agent-reach web "Claude Code installation guide"
```

Expected: Search results returned without errors.

If ERROR: Check that Agent Reach's web search implementation is working (may require internet connection).

- [ ] **Step 3: Test webpage reading**

If Agent Reach supports reading a specific URL:

```powershell
agent-reach web-read "https://claude.ai/docs"
```

Expected: Webpage content extracted and returned.

- [ ] **Step 4: Document web search test results**

Create `CHANNEL_TEST_LOG.md` and record:
```
# Agent Reach Channel Test Results

## Web Search
- Status: PASS ✓ or FAIL ✗
- Test Date: [today's date]
- Sample Query: "Claude Code installation"
- Result: [brief description of results received]
```

- [ ] **Step 5: Commit channel test**

```bash
git add CHANNEL_TEST_LOG.md
git commit -m "test: verify Agent Reach web search channel"
```

---

### Task 6: Configure & Test GitHub Research Channel

**Files:**
- Reference: `~/.claude/skills/agent-reach/config/github.config` (if applicable)
- Append: `./CHANNEL_TEST_LOG.md`

**Interfaces:**
- Consumes: Agent Reach installed and registered
- Produces: GitHub research channel verified (may require GitHub token)

- [ ] **Step 1: Check GitHub channel configuration**

Run:
```powershell
$githubConfig = "$env:USERPROFILE\.claude\skills\agent-reach\config\github.config"
if (Test-Path $githubConfig) {
    Write-Host "GitHub config found"
    Get-Content $githubConfig | Select-Object -First 10
} else {
    Write-Host "GitHub config not found; may not require auth for public repos"
}
```

Expected: Configuration checked (may be empty or may require GitHub token).

- [ ] **Step 2: If GitHub auth required, ask user for token**

If GitHub configuration requires a token:

**DO NOT proceed.** Ask the user:

> "Agent Reach GitHub research requires a GitHub personal access token. Do you want to configure this? (Yes/No)"

If Yes, ask for the token via secure channel (do NOT paste in transcript).
If No, skip GitHub configuration and proceed to Reddit.

- [ ] **Step 3: Test GitHub research (public repos)**

Run:
```powershell
agent-reach github "Panniantong/Agent-Reach"
```

Expected: GitHub repository information retrieved (stars, description, recent issues).

If ERROR: May indicate network issue or missing token; document and proceed.

- [ ] **Step 4: Update channel test log**

Append to `CHANNEL_TEST_LOG.md`:
```
## GitHub Research
- Status: PASS ✓ or FAIL ✗
- Auth Required: Yes / No
- Test Date: [today's date]
- Sample Query: "Panniantong/Agent-Reach"
- Result: [brief description]
```

- [ ] **Step 5: Commit GitHub channel test**

```bash
git add CHANNEL_TEST_LOG.md
git commit -m "test: verify Agent Reach GitHub channel"
```

---

### Task 7: Configure & Test Reddit Research Channel

**Files:**
- Reference: `~/.claude/skills/agent-reach/config/reddit.config` (if applicable)
- Append: `./CHANNEL_TEST_LOG.md`

**Interfaces:**
- Consumes: Agent Reach installed and registered
- Produces: Reddit research channel verified (may require Reddit API token)

- [ ] **Step 1: Check Reddit channel configuration**

Run:
```powershell
$redditConfig = "$env:USERPROFILE\.claude\skills\agent-reach\config\reddit.config"
if (Test-Path $redditConfig) {
    Write-Host "Reddit config found"
} else {
    Write-Host "Reddit config not found; checking if auth is required..."
}
```

Expected: Configuration status checked.

- [ ] **Step 2: If Reddit auth required, ask user for credentials**

If Reddit requires API credentials:

**DO NOT proceed.** Ask the user:

> "Agent Reach Reddit research may require Reddit API credentials. Do you want to configure this? (Yes/No)"

If Yes, ask for Reddit API key (do NOT paste in transcript).
If No, skip Reddit configuration.

- [ ] **Step 3: Test Reddit search**

Run:
```powershell
agent-reach reddit "Python troubleshooting"
```

Expected: Reddit thread summaries or search results retrieved.

If ERROR: Document the error (may be auth-related or network-related).

- [ ] **Step 4: Update channel test log**

Append to `CHANNEL_TEST_LOG.md`:
```
## Reddit Research
- Status: PASS ✓ or FAIL ✗
- Auth Required: Yes / No
- Test Date: [today's date]
- Sample Query: "Python troubleshooting"
- Result: [brief description]
```

- [ ] **Step 5: Commit Reddit channel test**

```bash
git add CHANNEL_TEST_LOG.md
git commit -m "test: verify Agent Reach Reddit channel"
```

---

### Task 8: Configure & Test YouTube Channel (Transcripts)

**Files:**
- Reference: `~/.claude/skills/agent-reach/config/youtube.config` (if applicable)
- Append: `./CHANNEL_TEST_LOG.md`

**Interfaces:**
- Consumes: Agent Reach installed and registered
- Produces: YouTube transcript channel verified

- [ ] **Step 1: Check YouTube channel configuration**

Run:
```powershell
$youtubeConfig = "$env:USERPROFILE\.claude\skills\agent-reach\config\youtube.config"
if (Test-Path $youtubeConfig) {
    Write-Host "YouTube config found"
} else {
    Write-Host "YouTube config not found"
}
```

Expected: Configuration status checked.

- [ ] **Step 2: Test YouTube transcript retrieval**

Run (using a well-known technical tutorial):

```powershell
agent-reach youtube "git tutorial" --transcripts
```

Expected: Video transcript(s) retrieved.

If ERROR: Document the error; may indicate API limitation or network issue.

- [ ] **Step 3: Update channel test log**

Append to `CHANNEL_TEST_LOG.md`:
```
## YouTube Transcripts
- Status: PASS ✓ or FAIL ✗
- Auth Required: Yes / No
- Test Date: [today's date]
- Sample Query: "git tutorial"
- Result: [brief description]
```

- [ ] **Step 4: Commit YouTube channel test**

```bash
git add CHANNEL_TEST_LOG.md
git commit -m "test: verify Agent Reach YouTube transcript channel"
```

---

## Phase 4: Update Instructions & Integration Testing

### Task 9: Update Global CLAUDE.md Instructions

**Files:**
- Modify: `~/.claude/CLAUDE.md` (add Agent Reach research guidance)
- Reference: `./CHANNEL_TEST_LOG.md` (which channels actually work)

**Interfaces:**
- Consumes: Channel test results from Tasks 5-8
- Produces: Updated global instructions reflecting available channels and agent responsibilities

- [ ] **Step 1: Read current CLAUDE.md and identify integration points**

Review `~/.claude/CLAUDE.md` for sections about:
- Agent dispatch (user prefers sub-agents)
- Research requirements
- External information gathering
- Output quality expectations

Expected: Current instructions read and understood.

- [ ] **Step 2: Add new "External Research" section to CLAUDE.md**

Append the following (adjust based on actual working channels from Task 5-8):

```markdown
## External Research via Agent Reach

When a task requires external information (current documentation, community insights, code examples, video tutorials):

1. **Dispatch to appropriate sub-agent:** Use an agent with research capabilities (Bolt is preferred; Rex/Nova as backup).
2. **Use Agent Reach channels:** Available channels include:
   - **Web Search** - Current documentation, blog posts, official guides (VERIFIED ✓)
   - **GitHub** - Code repositories, issues, pull requests [AUTH: optional]
   - **Reddit** - Community discussions, troubleshooting [AUTH: optional]
   - **YouTube** - Transcripts from technical tutorials (VERIFIED ✓)

3. **Verification strategy:**
   - Cross-check important findings against official documentation
   - For code research, prefer GitHub over other sources
   - For community solutions, cite Reddit thread links and vote counts
   - Use YouTube transcripts for step-by-step tutorials; verify against current versions

4. **Do not:**
   - Fabricate research results or invent channels that were not tested
   - Claim a channel works if it was not verified in `CHANNEL_TEST_LOG.md`
   - Use Agent Reach for tasks that do not genuinely require external information

## Integration Notes

- Agent Reach is registered as a global skill and available to all agents (Atlas, Rex, Rhoda, Sage, Bolt, Nova, Iris)
- No changes were made to existing agent roles, models, or tool access
- Existing dispatch preference for sub-agents is preserved (Agent Reach is invoked by agents, not this main session)
```

Expected: Section added to CLAUDE.md without breaking existing content.

- [ ] **Step 3: Verify CLAUDE.md syntax and completeness**

Run:
```powershell
$claudeMd = "$env:USERPROFILE\.claude\CLAUDE.md"
Write-Host "Updated CLAUDE.md size: $(Get-Item $claudeMd).Length bytes"
```

Expected: File is larger; no truncation.

- [ ] **Step 4: Commit updated instructions**

```bash
git add ~/.claude/CLAUDE.md
git commit -m "docs: add Agent Reach external research guidelines to global instructions"
```

Expected: Changes committed.

---

### Task 10: Integration Test with Existing Agent Roster

**Files:**
- Create: `./AGENT_REACH_INTEGRATION_TEST.md` (test results)
- Reference: All existing agent configs (Atlas, Rex, Rhoda, Sage, Bolt, Nova, Iris)

**Interfaces:**
- Consumes: Agent Reach installed, registered, and configured
- Produces: Verification that existing agents still function and can access Agent Reach

- [ ] **Step 1: Verify no agent configs were modified**

Compare backed-up agent directory with current:

Run:
```powershell
$backupAgents = Get-ChildItem -Path "$env:USERPROFILE\.claude\agents" -Recurse
Write-Host "Existing agents:"
foreach ($agent in $backupAgents) {
    if ($agent.PSIsContainer -eq $false) {
        Write-Host "  - $($agent.Name)"
    }
}
```

Expected: List shows Atlas, Rex, Rhoda, Sage, Bolt, Nova, Iris (no modifications).

- [ ] **Step 2: Verify Agent Reach is accessible to agents**

Check that `~/.claude/skills/agent-reach/` is readable by agent system:

Run:
```powershell
$skillDir = "$env:USERPROFILE\.claude\skills\agent-reach"
if ((Test-Path $skillDir) -and ((Get-Item $skillDir).PSIsContainer)) {
    Write-Host "Agent Reach skill is accessible: $skillDir"
    Get-ChildItem -Path $skillDir -Name | Measure-Object | Select-Object Count
} else {
    Write-Host "ERROR: Agent Reach skill directory not found"
}
```

Expected: Directory exists and is readable.

- [ ] **Step 3: Manual integration test (optional)**

Test one agent (e.g., Bolt) with a research task:

**User manually runs (or document for manual test):**
```bash
claude dispatch bolt "research PHP 8.1 features and compatibility"
```

Expected: Bolt receives the request and can call Agent Reach if needed (no errors about missing skill).

If FAIL: Document the error; may indicate skill registration issue.

- [ ] **Step 4: Verify global instructions are consistent**

Run:
```powershell
$claudeMd = "$env:USERPROFILE\.claude\CLAUDE.md"
Get-Content $claudeMd | Select-String -Pattern "dispatch", "Agent Reach", "sub-agent" | Select-Object -First 10
```

Expected: Output shows existing dispatch guidance and new Agent Reach guidance coexist without conflicts.

- [ ] **Step 5: Document integration test results**

Create `AGENT_REACH_INTEGRATION_TEST.md`:

```markdown
# Agent Reach Integration Test Results

**Test Date:** [today's date]

## Environment Check
- Python version: [from Task 1]
- Git version: [from Task 1]
- uv version: [from Task 1]
- Agent Reach location: ~/.claude/skills/agent-reach/
- Agent Reach status: INSTALLED ✓

## Agents - No Modifications
- Atlas: ✓ (unchanged)
- Rex: ✓ (unchanged)
- Rhoda: ✓ (unchanged)
- Sage: ✓ (unchanged)
- Bolt: ✓ (unchanged)
- Nova: ✓ (unchanged)
- Iris: ✓ (unchanged)

## Research Channels - Status Summary
- Web Search: [PASS/FAIL from Task 5]
- GitHub: [PASS/FAIL/SKIPPED from Task 6]
- Reddit: [PASS/FAIL/SKIPPED from Task 7]
- YouTube: [PASS/FAIL from Task 8]

## Global Instructions Updated
- CLAUDE.md updated with Agent Reach guidance: ✓
- Existing dispatch preferences preserved: ✓
- New research guidelines added: ✓

## Integration Status
**READY FOR USE** ✓

Agent Reach is installed, configured, and available to all agents.
Existing agent responsibilities and tools remain unchanged.
Research channels have been tested; results documented above.
Global instructions updated to guide use of Agent Reach.
```

- [ ] **Step 6: Final commit**

```bash
git add AGENT_REACH_INTEGRATION_TEST.md
git add CHANNEL_TEST_LOG.md
git add AGENT_REACH_INSTALL_LOG.md
git commit -m "docs: complete Agent Reach installation, configuration, and integration testing"
```

Expected: All documentation committed.

---

## Cleanup & Verification

### Task 11: Cleanup and Final Verification

**Files:**
- Delete: Temporary backup directories (if integration successful)
- Reference: `~/.claude/` (final state)

**Interfaces:**
- Consumes: Completed installation from all prior tasks
- Produces: Clean state, all documentation preserved, system ready

- [ ] **Step 1: Review all test results**

Run:
```powershell
Write-Host "=== Installation Summary ==="
Get-Item -Path "$env:USERPROFILE\.claude\skills\agent-reach" | Select-Object FullName, CreationTime
Write-Host "`n=== Test Documentation ==="
Get-Item -Path ".\CHANNEL_TEST_LOG.md" | Select-Object Name, Length
Get-Item -Path ".\AGENT_REACH_INTEGRATION_TEST.md" | Select-Object Name, Length
```

Expected: All directories and logs present.

- [ ] **Step 2: Verify backup is stored safely (outside git)**

Run:
```powershell
$backups = Get-ChildItem -Path "$env:USERPROFILE\.claude\" -Filter "BACKUP_AGENT_REACH_*" -Directory
Write-Host "Backups found: $($backups.Count)"
foreach ($backup in $backups) {
    Write-Host "  - $($backup.Name): $(Get-Item -Path $backup.FullName | Measure-Object -Sum).Count files"
}
```

Expected: Backup directory exists and is preserved for rollback (if needed).

- [ ] **Step 3: Final smoke test**

Run a quick command to verify Agent Reach is still callable:

```powershell
agent-reach --version
```

Expected: Version output (confirms installation is complete and PATH is correct).

- [ ] **Step 4: Document final state**

Append to `AGENT_REACH_INTEGRATION_TEST.md`:

```markdown
## Final Verification (Task 11)

- Backup preserved: ✓
- All test logs committed: ✓
- Agent Reach callable: ✓ (version: [output from Step 3])
- Installation time: [start to finish duration]
- Status: **COMPLETE AND READY FOR USE**

Next steps for user:
1. Review this plan's execution
2. Test Agent Reach with a real research task (dispatch to Bolt or other research agent)
3. Adjust global instructions if any channels require additional configuration
4. Remove backup directories after 30 days if no rollback is needed
```

- [ ] **Step 5: Final commit**

```bash
git add AGENT_REACH_INTEGRATION_TEST.md
git commit -m "chore: Agent Reach installation complete, all tests passed, system ready"
```

Expected: Final commit succeeds; installation is complete and documented.

---

## Summary

**Total Tasks:** 11
**Total Steps:** ~70
**Estimated Duration:** 30-60 minutes (depending on network speed and auth setup)

**Deliverables:**
- ✓ Agent Reach installed in `~/.claude/skills/agent-reach/`
- ✓ Agent Reach registered as global skill
- ✓ Research channels configured and tested
- ✓ Global instructions updated
- ✓ Existing agents unchanged and verified
- ✓ Complete audit trail (installation logs, test results, integration report)
- ✓ Rollback backup preserved

**Success Criteria:**
- [ ] Agent Reach callable from PowerShell
- [ ] At least web search and one other channel verified working
- [ ] No existing agent configs modified
- [ ] All agents can still access their tools
- [ ] Global CLAUDE.md has new research guidance
- [ ] All changes committed to git
