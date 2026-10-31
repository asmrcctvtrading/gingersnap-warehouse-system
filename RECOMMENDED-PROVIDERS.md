# Recommended Providers to Add to Omniroute
**Date**: 2026-10-23
**Current Setup**: 8 models in `my-combo`

---

## High Priority - Free Providers

### 1. **Groq** (groq) - HIGHLY RECOMMENDED ⭐
- **Why**: Extremely fast inference (up to 800 tokens/sec)
- **Models**: llama-3.3-70b, llama-3.1-8b, mixtral-8x7b, gemma2-9b
- **Cost**: FREE with generous quota
- **Use case**: Fast responses, coding tasks
- **Setup**: 
  ```powershell
  omniroute providers add groq
  # Enter your Groq API key from https://console.groq.com/keys
  ```

### 2. **Together AI** (together) - HIGHLY RECOMMENDED ⭐
- **Why**: 50+ open-source models, good free tier
- **Models**: Llama 3.3 70B, Qwen2.5, DeepSeek Coder, Mixtral
- **Cost**: $5 free credits, then pay-as-you-go
- **Use case**: Open source models, experimentation
- **Setup**:
  ```powershell
  omniroute providers add together
  # Get API key from https://api.together.xyz/settings/api-keys
  ```

### 3. **Cerebras** (cerebras) - HIGHLY RECOMMENDED ⭐
- **Why**: Ultra-fast inference on specialized hardware
- **Models**: llama-3.3-70b
- **Cost**: FREE (no credit card required)
- **Use case**: When you need blazing fast Llama responses
- **Setup**:
  ```powershell
  omniroute providers add cerebras
  # Get API key from https://cloud.cerebras.ai/
  ```

### 4. **SambaNova** (samba)
- **Why**: Fast, free Llama models
- **Models**: Llama 3.3 70B, Llama 3.1 405B
- **Cost**: FREE
- **Setup**:
  ```powershell
  omniroute providers add samba
  # Get API key from https://cloud.sambanova.ai/
  ```

### 5. **Fireworks AI** (fireworks)
- **Why**: Fast inference, good free tier
- **Models**: 40+ models including Qwen, DeepSeek, Llama
- **Cost**: $1 free credit
- **Setup**:
  ```powershell
  omniroute providers add fireworks
  # Get API key from https://fireworks.ai/api-keys
  ```

### 6. **HuggingFace** (hf)
- **Why**: Access to thousands of models
- **Models**: All HF hosted models
- **Cost**: FREE tier available
- **Setup**:
  ```powershell
  omniroute providers add hf
  # Get token from https://huggingface.co/settings/tokens
  ```

### 7. **Mistral** (mistral)
- **Why**: Official Mistral models, free tier
- **Models**: Mistral Small, Pixtral, Codestral
- **Cost**: FREE tier available
- **Setup**:
  ```powershell
  omniroute providers add mistral
  # Get API key from https://console.mistral.ai/
  ```

---

## Medium Priority - Paid but Low Cost

### 8. **OpenRouter** (openrouter) - Aggregator ⭐
- **Why**: Single API for 200+ models from all providers
- **Models**: Claude, GPT, Gemini, Llama, and more
- **Cost**: Pay only for what you use, some free models
- **Use case**: Access everything through one provider
- **Setup**:
  ```powershell
  omniroute providers add openrouter
  # Get API key from https://openrouter.ai/keys
  ```

### 9. **DeepInfra** (deepinfra)
- **Why**: Cheap inference for open models
- **Models**: Llama, Qwen, DeepSeek, Mixtral
- **Cost**: $1 free credit, very cheap after
- **Setup**:
  ```powershell
  omniroute providers add deepinfra
  # Get API key from https://deepinfra.com/dash/api_keys
  ```

### 10. **Hyperbolic** (hyp)
- **Why**: Free inference for popular models
- **Models**: Llama, Qwen, DeepSeek
- **Cost**: FREE tier
- **Setup**:
  ```powershell
  omniroute providers add hyp
  # Get API key from https://app.hyperbolic.xyz/
  ```

---

## Already Configured (Keep These)

✅ **Ollama** (ollama) - Local, completely free
✅ **OpenCode** (oc) - Free tier
✅ **Antigravity CLI** (agy) - Free quota
✅ **Kiro AI** (kiro) - Low cost, credit-based
✅ **Gemini Web** (gweb) - Free via Google session
✅ **Grok CLI** (gc) - Free via xAI
✅ **Claude Code** (cc) - Needs re-auth

---

## Suggested New `my-combo` Order

After adding the recommended providers, update your combo:

```
Priority 1: ollama/qwen2.5-coder:7b          # Local, free, instant
Priority 2: cerebras/llama-3.3-70b           # Ultra-fast, free
Priority 3: groq/llama-3.3-70b-versatile     # Very fast, free
Priority 4: oc/deepseek-v4-flash-free        # OpenCode free
Priority 5: samba/llama-3.3-70b              # Free
Priority 6: agy/claude-sonnet-4-6            # Antigravity free quota
Priority 7: fireworks/qwen2.5-coder-32b      # Fast, cheap
Priority 8: kiro/qwen3-coder-next            # Low cost
Priority 9: gweb/gemini-3.7-flash            # Google free
Priority 10: cc/claude-sonnet-5-5            # Paid fallback (after re-auth)
```

This gives you:
- 3 ultra-fast free providers (Cerebras, Groq, SambaNova)
- 2 local/free options (Ollama, OpenCode)
- Multiple fallback options before hitting paid tier

---

## Quick Setup Commands

```powershell
# Install the top 3 recommended providers
omniroute providers add groq
omniroute providers add together
omniroute providers add cerebras

# Test them
omniroute providers test groq
omniroute providers test together
omniroute providers test cerebras

# List all configured providers
omniroute providers list

# Update my-combo via dashboard
# http://localhost:20128/dashboard/combos
```

---

## Notes

- All "free" providers have rate limits (usually generous)
- Setup takes ~5 minutes per provider (get API key, add to Omniroute)
- Test each provider after adding
- Update `my-combo` to use new providers
- Keep Claude OAuth as final fallback (unlimited, paid)

---

## Action Items

1. ⏳ **Add Groq** - Ultra-fast free inference
2. ⏳ **Add Cerebras** - Blazing fast Llama
3. ⏳ **Add Together AI** - 50+ open models
4. ⏳ **Re-auth Claude OAuth** - Unlock paid fallback
5. ⏳ **Update my-combo** - Rebuild with new providers
6. ⏳ **Test routing** - Verify fallback chain works
