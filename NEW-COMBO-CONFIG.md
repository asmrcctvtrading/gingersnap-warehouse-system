# Updated my-combo Configuration
**Date**: 2026-10-23
**Status**: Ready to apply

---

## New Routing Strategy

### **Recommended Priority Order**

Here's your new optimized `my-combo` with the added providers:

| Priority | Model | Provider | Speed | Cost | Notes |
|----------|-------|----------|-------|------|-------|
| 1 | `ollama/qwen2.5-coder:7b` | Ollama (local) | ⚡⚡⚡ | FREE | Local, instant, no internet |
| 2 | `groq/llama-3.3-70b-versatile` | Groq | ⚡⚡⚡ | FREE | Ultra-fast (800 tok/s) |
| 3 | `cerebras/gpt-oss-120b` | Cerebras | ⚡⚡⚡ | FREE | AI hardware, blazing fast |
| 4 | `oc/deepseek-v4-flash-free` | OpenCode | ⚡⚡ | FREE | DeepSeek V4 |
| 5 | `oc/north-mini-code-free` | OpenCode | ⚡⚡ | FREE | Code-specialized |
| 6 | `agy/claude-sonnet-4-6` | Antigravity CLI | ⚡⚡ | FREE | Claude Sonnet 4.6 |
| 7 | `kiro/qwen3-coder-next` | Kiro AI | ⚡⚡ | Low cost | Qwen3 Coder (credits) |
| 8 | `groq/qwen/qwen3.8-27b` | Groq | ⚡⚡⚡ | FREE | Qwen 3.8 fallback |
| 9 | `gweb/gemini-3.7-flash` | Gemini Web | ⚡ | FREE | Google web session |
| 10 | `gc/grok-4.6` | Grok CLI | ⚡ | FREE | xAI Grok |
| 11 | `cc/claude-sonnet-5-5` | Claude OAuth | ⚡ | PAID | Final fallback (re-auth needed) |

---

## What Changed?

### **Added (NEW)**
- ✨ `groq/llama-3.3-70b-versatile` at position 2
- ✨ `cerebras/gpt-oss-120b` at position 3
- ✨ `groq/qwen/qwen3.8-27b` at position 8

### **Why This Order?**

1. **Ollama first** - Local model, zero latency, zero cost
2. **Groq second** - Fastest cloud inference (800 tokens/sec)
3. **Cerebras third** - Specialized AI hardware, massive context (400K)
4. **OpenCode fourth/fifth** - Free tier with good models
5. **Rest cascade down** - From free → low cost → paid

---

## How to Apply This Configuration

### **Option 1: Via Omniroute Dashboard (RECOMMENDED)**

1. Open the dashboard:
   ```
   http://localhost:20128/dashboard/combos
   ```

2. Find `my-combo` and click **Edit**

3. Update the model list to match the priority order above

4. Click **Save**

5. Test the routing with a simple query

### **Option 2: Via CLI (Advanced)**

You can also update the combo via SQL directly, but the dashboard is easier.

---

## Testing Your New Routing

After updating the combo, test it:

```powershell
# Test that my-combo routes correctly
curl http://localhost:20128/v1/chat/completions `
  -H "Authorization: Bearer YOUR_OMNIROUTE_API_KEY" `
  -H "Content-Type: application/json" `
  -d '{"model":"my-combo","messages":[{"role":"user","content":"Hi, which model are you?"}]}'
```

The response should show which model handled the request.

---

## Alternative Specialized Combos

You can also create specialized combos for different use cases:

### **Fast Combo** (speed priority)
```
1. groq/llama-3.3-70b-versatile
2. cerebras/gpt-oss-120b
3. groq/qwen/qwen3.8-27b
```

### **Free Combo** (zero cost priority)
```
1. ollama/qwen2.5-coder:7b
2. oc/deepseek-v4-flash-free
3. gweb/gemini-3.7-flash
4. gc/grok-4.6
```

### **Quality Combo** (best output priority)
```
1. agy/claude-sonnet-4-6
2. groq/llama-3.3-70b-versatile
3. cerebras/gpt-oss-120b
4. cc/claude-sonnet-5-5
```

---

## Next Actions

1. ✅ Groq added and tested
2. ✅ Cerebras added
3. ✅ Together AI added
4. ⏳ **Update my-combo** (via dashboard)
5. ⏳ **Test the routing** (send a test query)
6. ⏳ **Re-auth Claude OAuth** (optional but recommended)

---

## Dashboard Access

**URL**: http://localhost:20128  
**Password**: (your dashboard password)

Navigate to: **Dashboard → Combos → my-combo → Edit**

---

## Summary

You now have **15 providers** configured with **3 new ultra-fast free options**:
- Groq (fastest)
- Cerebras (AI hardware)
- Together AI (50+ models)

Your routing will now try local → ultra-fast free → free → low-cost → paid.

This maximizes speed and minimizes cost while maintaining high quality.
