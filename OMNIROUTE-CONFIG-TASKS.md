# OmniRoute Configuration Tasks
**Created**: 2026-10-19  
**Status**: OmniRoute server confirmed running on port 20128

---

## Current Status ✓

- ✅ **OmniRoute v3.8.51** - Running on http://localhost:20128
- ✅ **Ollama Integration** - qwen2.5-coder:7b active at position 1 in my-combo
- ✅ **6 Specialized Agents** - Deployed globally and project-level
- ✅ **my-combo routing** - 8 models configured with priority fallback

---

## Pending Configuration Tasks

### Task 1: Fix Claude OAuth (HIGH PRIORITY)
**Status**: ⏳ Ready to execute  
**Impact**: Unlocks 8+ premium Claude models (cc/ prefix)

**Current Issue**:
- Claude Code OAuth token has expired (invalid_grant error)
- Models affected: `cc/claude-sonnet-5-5`, `cc/claude-opus-5-5`, `cc/claude-haiku-5-5`, `cc/claude-fable-5-1`
- These are currently position 8 in my-combo but unavailable

**Steps Required** (Manual - requires browser interaction):

1. **Open Dashboard**
   ```
   http://localhost:20128
   ```

2. **Login to Dashboard**
   - Use your current OmniRoute dashboard password
   - (Note: Dashboard password is still on factory default - see Task 2)

3. **Navigate to Providers**
   - Dashboard → Providers → Claude Code

4. **Re-authenticate**
   - Click "Apply auth" or "Re-authenticate" button
   - Browser window will open automatically
   - Sign in with: `asmrcctvandpc@gmail.com`

5. **Verify Success**
   - Provider should show "Active" status (green indicator)
   - Click "Test" button to confirm connectivity
   - Check that cc/ models now respond

**After Re-auth**:
- `cc/claude-sonnet-5-5` will work as the paid fallback (position 8)
- All Claude Code profiles will function correctly
- OAuth token will be valid for ~90 days

**Why This Matters**:
- Claude Sonnet 5.5 is the most capable model in your combo
- Provides reliable fallback when free tiers are exhausted
- Required for production-quality code generation

---

### Task 2: Update Dashboard Password (SECURITY)
**Status**: ⏳ Ready to execute  
**Impact**: Secures OmniRoute dashboard access

**Current Issue**:
- Dashboard password is still factory default "CHANGEME"
- Flagged in OMNIROUTE_GUIDE.md Section J as "STILL TODO"
- Anyone with local access can manage your API routing

**Steps Required** (Manual - must be done by user):

1. **Open Dashboard Settings**
   ```
   http://localhost:20128/dashboard/settings/security
   ```

2. **Change Password**
   - Enter current password (factory default)
   - Set a strong unique password
   - Confirm new password

3. **Store Password Securely**
   - Save in your password manager
   - Do NOT share with anyone (including AI assistants)

**Risk Level**:
- **Low** (server only listens on 127.0.0.1, not exposed to network)
- **Medium** if other users have physical/RDP access to this machine
- Best practice: change it regardless of risk level

---

## Additional Recommendations

### Optional: Add More Providers

Your current my-combo has good coverage:
1. Ollama (local, free)
2. OpenCode (free)
3. Antigravity CLI (free quota)
4. Kiro AI (low cost)
5. Gemini Web (free)
6. Grok CLI (free)
7. Claude Code (paid subscription)

**Potential Additions**:
- **OpenRouter** - Access to 100+ models with unified API
- **Together AI** - Fast inference for open-source models
- **Groq** - Ultra-fast inference (llama, mixtral)

### Optional: Web UI Configuration

The Web UI on http://localhost:3000 provides a chat interface.

**To start Web UI separately**:
```powershell
cd "$env:USERPROFILE\.omniroute\web-ui"
node server.js
```

**Web UI Features**:
- Model dropdown (switch providers mid-conversation)
- Conversation history
- Markdown rendering
- Copy/paste code blocks

---

## Testing After OAuth Fix

Once Claude OAuth is re-authenticated, test the routing:

```powershell
# Test direct cc/ model access
curl http://localhost:20128/v1/chat/completions `
  -H "Authorization: Bearer YOUR_OMNIROUTE_API_KEY" `
  -H "Content-Type: application/json" `
  -d '{"model":"cc/claude-sonnet-5-5","messages":[{"role":"user","content":"test"}]}'

# Test my-combo routing (should try Ollama first, fall back to Claude)
curl http://localhost:20128/v1/chat/completions `
  -H "Authorization: Bearer YOUR_OMNIROUTE_API_KEY" `
  -H "Content-Type: application/json" `
  -d '{"model":"my-combo","messages":[{"role":"user","content":"test"}]}'
```

---

## Next Steps Priority

1. **CRITICAL**: Fix Claude OAuth (Task 1) - enables premium fallback
2. **RECOMMENDED**: Change dashboard password (Task 2) - security best practice
3. **OPTIONAL**: Test end-to-end routing after Task 1
4. **OPTIONAL**: Add more providers if needed

---

## Reference Links

- Dashboard: http://localhost:20128
- Web UI: http://localhost:3000 (if started)
- API Endpoint: http://localhost:20128/v1
- Full Guide: `~/.omniroute/OMNIROUTE_GUIDE.md`
- Model Routing: `~/.omniroute/MODEL-ROUTING-GUIDE.md`

---

## Backup Information

Last backup before Ollama integration:
- File: `storage_ollama_backup_20261012_111236.sqlite`
- Location: `~/.omniroute/`
- Contains: Full OmniRoute configuration pre-Ollama

If anything breaks during OAuth re-auth, the backup can be restored.
