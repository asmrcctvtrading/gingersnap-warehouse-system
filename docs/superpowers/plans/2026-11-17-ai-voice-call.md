# AI Voice Call Confirmation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** When a visitor submits the contact form on asmrcctvtechnologies.com, an AI voice agent phones them, discloses it is an AI, confirms which of CCTV Systems / Systems Development / AI Automation / Network Setup / Other they want, and reports the result to Telegram, with a human fallback for anything that is not a clean confirmation.

**Architecture:** A new n8n workflow, `ASMR Lead Confirm (Voice)`, reuses the proven front half of the live Telegram workflow (webhook, phone cleanup, bad-phone alert) and replaces only the `Ask Service (Telegram)` step with a Vapi call (create, poll with a cap, read structured output). The live Telegram workflow stays untouched as the rollback: switching traffic is one line in the website's `includes/config.php`. Telegram remains the alert channel for every outcome.

**Tech Stack:** n8n 1.123.97 (Render free, Supabase Postgres), Vapi (`POST https://api.vapi.ai/call`, `GET https://api.vapi.ai/call/{id}`, structured outputs), a Philippine-capable phone number (Task 1 decides how), Telegram bot `@asmr_leadconfirm_bot` (chat ID 8179698982), Node 18+ `node:test` for the pure parsing function.

**Spec:** No separate design doc was approved. The decisions below are this plan's spec; the "Open decisions" section lists what the owner must still choose before Task 1 can finish. Starting state is recorded in memory file `project_asmr_n8n_lead_confirm.md` and the live workflow `ESmqbRVfxKMxoLgS`.

## Global Constraints

- Services offered, exact strings: `CCTV Systems`, `Systems Development`, `AI Automation`, `Network Setup`, `Other` (these are the website's `<option value>` strings).
- Live webhook URL stays `https://asmr-n8n.onrender.com/webhook/asmr-lead`; the voice workflow uses a new path `asmr-lead-voice` with the same Header Auth credential `Header Auth account` (header `X-ASMR-Secret`).
- Phone numbers on the wire to Vapi are E.164 `+63XXXXXXXXXX`; the existing `Normalize Phone` Code node is reused unchanged (output fields `name, email, company, message, form_service, phone_raw, phone, valid`).
- The AI must say at the start that it is an AI assistant calling on behalf of ASMR CCTV Trading, and must stop and end the call if the person says they do not want to be called.
- Call window: 08:00–20:00 `Asia/Manila`; outside it the call waits until the next 08:00 and Telegram is told.
- Max call length 180 seconds (`maxDurationSeconds`: 180). At most 2 call attempts per lead, 30 minutes apart, then human fallback.
- Daily cap: at most 30 outbound calls per Manila day; over the cap goes straight to human fallback.
- Secrets (Vapi private key, Telegram token, webhook secret, DB password) are typed by the owner into n8n credentials or config files. They never appear in plan files, workflow JSON, chat or commits.
- No call is placed to a number outside the allowlist until Task 7 is approved by the owner.
- Never change the live Telegram workflow or live site files without a backup and the owner's approval.
- Compliance wording and recording consent are the owner's decision (Open decision 4); this plan is not legal advice.

## Open decisions (owner must answer; recommendations in brackets)

1. **Phone number path.** Free Vapi numbers cannot call Philippine numbers. Options to test in Task 1: (a) a Twilio number imported into Vapi, if Twilio can sell or verify one that can reach PH mobiles; (b) a SIP trunk from a provider that supports Philippine termination, connected to Vapi; (c) a verified caller ID on a number the owner already owns. [Spike all that are available; pick the cheapest one that completes a call to the owner's own PH mobile.]
2. **Language.** English only, Taglish, or Tagalog. [Taglish; verify the chosen voice and transcriber handle it in Task 2.]
3. **Budget ceiling.** Monthly spend limit for calls. [Set a hard limit in Vapi and Twilio equal to the owner's number.]
4. **Consent and recording.** Whether to record calls and what the form's consent line must say. [No recording at first; keep the existing consent line; owner checks Philippine Data Privacy Act and NTC rules.]
5. **Persona.** The AI's name and how it names the company. [Short, plain, e.g. "an AI assistant from ASMR CCTV Trading".]

## Review Focus

1. **Visitor answers in mixed Tagalog/English, mumbles, or gives a service not on the list:** expected: assistant asks once to clarify, then returns `Other` with notes, never invents a service.
2. **Voicemail, no answer, busy, or immediate hang-up:** expected: counted as a failed attempt, retried once, then Telegram "needs a human" with the lead's details. No endless redial.
3. **Same lead submitted twice (double click, or same phone within 10 minutes):** expected: one call only.
4. **Person says "don't call me" or "wrong number":** expected: call ends politely, outcome `do_not_call` or `wrong_number`, no retry, Telegram alert.
5. **n8n or Vapi hiccups mid-call (free n8n sleeps or stalls, poll never sees `ended`):** expected: polling stops after the attempt cap, the call is treated as unknown, Telegram "needs a human" fires once, and nothing is called twice.

---

## File Structure

All new work lives in a `voice/` folder in this repo (workflow assets and the tested parser), plus one n8n workflow built from them.

- Create: `voice/parse-call.js` — pure function that turns a finished Vapi call into one outcome record. One responsibility, no n8n or network code.
- Create: `voice/parse-call.test.js` — `node:test` tests with fixture call objects.
- Create: `voice/fixtures/` — sample finished-call JSON, one file per scenario (captured from real test calls in Task 4 where possible, hand-written until then).
- Create: `voice/assistant-prompt.md` — the Vapi system prompt, with `{{ lead_name }}` style variables.
- Create: `voice/structured-output.json` — the JSON Schema linked to the assistant.
- Create: `voice/workflow-voice.json` — exported n8n workflow (no secrets, credentials referenced by name).
- Create: `voice/RUNBOOK.md` — how to pause, switch back to Telegram, read results, and what each Telegram message means.

---

### Task 1: Telephony spike (can Vapi call a Philippine mobile?)

**Files:**
- Create: `voice/RUNBOOK.md` (section "Phone number")

**Interfaces:**
- Produces: `VAPI_PHONE_NUMBER_ID` (stored only in the n8n HTTP node, not in files) and a recorded decision for Open decision 1.

- [ ] **Step 1: Owner creates a Vapi account and a private API key.** Key goes into n8n credential `Vapi` (Bearer) typed by the owner.
- [ ] **Step 2: For each available number path in Open decision 1, import it into Vapi and place a dashboard test call to the owner's own PH mobile.** Record per path: connected yes/no, caller ID shown, one-minute cost.
- [ ] **Step 3: Verification:** one test call connects to the owner's phone and the owner hears a test greeting. Expected: a Vapi call log with `status: ended` and a non-error `endedReason`. If no path connects, stop and report; the rest of the plan depends on this.
- [ ] **Step 4: Record the chosen path and the monthly cost estimate in `voice/RUNBOOK.md`.**

### Task 2: Assistant prompt and structured output

**Files:**
- Create: `voice/assistant-prompt.md`
- Create: `voice/structured-output.json`

**Interfaces:**
- Produces: structured-output fields the parser (Task 3) reads: `confirmed_service` (enum of the five exact service strings, or `null`), `outcome` (enum: `confirmed`, `unsure`, `wants_callback`, `do_not_call`, `wrong_number`), `callback_time` (string or `null`), `notes` (string).
- Produces: prompt variables `lead_name`, `lead_company`, `lead_message`, `form_service` (names must match the `variableValues` keys used in Task 4).

- [ ] **Step 1: Write the prompt** with these decided behaviors: wait for the person to speak first; say it is an AI assistant from ASMR CCTV Trading; mention the website inquiry using `{{ lead_name }}` and `{{ lead_message }}`; ask which of the five services they need, offering the list once; accept Tagalog, English or mixed; if unclear after one clarification, record `Other` or `unsure`; end the call if the person declines; never quote prices; keep it under about 90 seconds.
- [ ] **Step 2: Write `voice/structured-output.json`** as JSON Schema with the fields above and `required: ["outcome"]`.
- [ ] **Step 3: In Vapi, create the assistant, link the structured output, enable the end-call function, set `maxDurationSeconds` to 180.**
- [ ] **Step 4: Verification:** six dashboard test calls to the owner's phone, one per scenario (each of 4 services, "Other", "don't call me"). Expected: each call's `artifact.structuredOutputs` shows the matching `confirmed_service`/`outcome`. Save the six call JSONs into `voice/fixtures/`.

### Task 3: Result parser (tested)

**Files:**
- Create: `voice/parse-call.js`
- Test: `voice/parse-call.test.js`

**Interfaces:**
- Consumes: a finished Vapi call object (shape per `GET /call/{id}`, fixtures from Task 2 and Task 4).
- Produces: `parseCall(call: object) -> { outcome: 'confirmed'|'unsure'|'wants_callback'|'do_not_call'|'wrong_number'|'no_answer'|'voicemail'|'failed', service: string|null, callbackTime: string|null, notes: string, endedReason: string }`.

- [ ] **Step 1: Write failing tests** in `voice/parse-call.test.js`, each loading a fixture:
  - `confirmed call returns outcome confirmed and the exact service string`
  - `voicemail endedReason returns outcome voicemail`
  - `customer never picked up returns outcome no_answer`
  - `do_not_call from the structured output wins over a confirmed service`
  - `missing structured outputs on an ended call returns outcome failed, not a crash`
  - `service not in the five allowed strings is returned as Other`
- [ ] **Step 2: Run `node --test voice/` and expect FAIL** (module not found).
- [ ] **Step 3: Implement `parseCall`** as a plain CommonJS function. It reads `call.endedReason` first (voicemail/no-answer values must be taken from the real strings in fixtures, not guessed), then `call.artifact.structuredOutputs` (a dictionary keyed by output ID; find the entry by output name).
- [ ] **Step 4: Run `node --test voice/` and expect all PASS.**
- [ ] **Step 5: Commit** `voice/parse-call.js`, `voice/parse-call.test.js`, `voice/fixtures/`.

### Task 4: Voice workflow in n8n (shadow mode, Telegram only)

**Files:**
- Create: `voice/workflow-voice.json` (exported from n8n after building)

**Interfaces:**
- Consumes: `parseCall` (Task 3) pasted into one Code node; Vapi ids from Tasks 1–2; credentials `Vapi` (Bearer), `Header Auth account`, `Telegram account`.
- Produces: webhook `POST https://asmr-n8n.onrender.com/webhook/asmr-lead-voice` accepting the same JSON the website already sends (`name, company, email, phone, service_type, message, created_at`).

- [ ] **Step 1: Build the workflow** with these node names, in order: `Lead Webhook` (Header Auth) → `Normalize Phone` (copy of the live node) → `Phone Valid?` (false → `Bad Phone Alert`, copy of the live node) → `Allowlist Check` → `Within Call Window?` → `Dedup Check` → `Create Call` (HTTP POST `https://api.vapi.ai/call`; body fields `assistantId`, `phoneNumberId`, `customer.number`, `assistantOverrides.variableValues`) → `Wait 45s` → `Get Call` → `Ended?` (loops back through `Wait 15s`, capped at 30 polls) → `Parse Result` (Code, runs `parseCall`) → `Route Outcome` (Switch) → Telegram nodes `Confirmed Notice`, `Needs Human`, `Do Not Call Notice`.
- [ ] **Step 2: Set `Telegram` fallback for every non-`confirmed` outcome** to a single `Needs Human` message carrying name, phone, form service, outcome and notes.
- [ ] **Step 3: Verification with curl** against the test URL, owner's own PH number in the allowlist: (a) answer and say "CCTV" → Telegram `CONFIRMED … CCTV Systems`; (b) let it ring out → after the retry rule, `Needs Human` (no answer); (c) bad phone `123` → existing `BAD PHONE NUMBER` alert, no Vapi call made (check Vapi call list is unchanged).
- [ ] **Step 4: Export the workflow JSON to `voice/workflow-voice.json`, confirm it contains no secret values (search for the secret and key strings), and commit.**

### Task 5: Call window, retries and duplicates

**Files:**
- Modify: n8n workflow `ASMR Lead Confirm (Voice)` (nodes `Within Call Window?`, `Dedup Check`, `Attempt Router`)
- Modify: `voice/workflow-voice.json` (re-export)

**Interfaces:**
- Consumes: `parseCall` outcome from Task 3.
- Produces: rule table in `voice/RUNBOOK.md`.

- [ ] **Step 1: `Within Call Window?`** — compute Manila time in a Code node; outside 08:00–20:00 use a Wait node until the next 08:00 and send one `Queued until morning` Telegram message.
- [ ] **Step 2: `Dedup Check`** — Code node using workflow static data keyed by phone; a second submission for the same phone within 10 minutes skips the call and sends one Telegram note.
- [ ] **Step 3: `Attempt Router`** — after `no_answer` or `voicemail` on attempt 1, wait 30 minutes and call again; after attempt 2, send `Needs Human`. `do_not_call` and `wrong_number` never retry.
- [ ] **Step 4: Verification:** five curl runs: lead at 21:00 Manila (expect "queued until morning", no call), same phone twice in 10 minutes (expect one call), no-answer twice (expect exactly 2 calls then `Needs Human`), "don't call me" (expect 1 call, no retry), normal confirm (expect 1 call).

### Task 6: Cost and safety guardrails

**Files:**
- Modify: n8n workflow (node `Daily Cap`), Vapi and telephony dashboards (spend limits)
- Modify: `voice/RUNBOOK.md` (sections "Pause", "Spend limits")

**Interfaces:**
- Produces: a kill switch the owner can use in one step (deactivate the voice workflow, or point `N8N_WEBHOOK_URL` back to `/webhook/asmr-lead`).

- [ ] **Step 1: `Daily Cap`** — static-data counter per Manila date; over 30 goes to `Needs Human` with the reason "daily call cap".
- [ ] **Step 2: Set hard monthly spend limits** in Vapi and the telephony provider to the budget from Open decision 3 (owner does this in the dashboards).
- [ ] **Step 3: Verification:** temporarily set the cap to 1 and send two leads. Expected: the first is called, the second gets `Needs Human` with reason "daily call cap". Restore the cap to 30.
- [ ] **Step 4: Write the "Pause" and "Switch back to Telegram" instructions in `voice/RUNBOOK.md`** and test the switch-back once on a harmless lead.

### Task 7: Staged rollout

**Files:**
- Modify: `includes/config.php` on the live site (one `define`), after a backup
- Modify: `voice/RUNBOOK.md`

**Interfaces:**
- Consumes: working voice workflow with the allowlist node.

- [ ] **Step 1: Shadow week.** Keep the live site on `/webhook/asmr-lead` (Telegram). Owner only runs curl and own-phone tests against `asmr-lead-voice`. Exit criterion: the six scenarios from Task 2 and the five from Task 5 pass.
- [ ] **Step 2: Allowlist mode.** Point `N8N_WEBHOOK_URL` at `/webhook/asmr-lead-voice` with the allowlist holding only the owner's and staff numbers; every other lead goes to Telegram `Needs Human` immediately. Back up `includes/config.php` first and re-read it after the edit.
- [ ] **Step 3: Verification:** submit the live form with an allowlisted number (expect a call) and a non-allowlisted number (expect Telegram `Needs Human`, no call). Then check the site still loads (homepage, contact page, handler return 200).
- [ ] **Step 4: Open mode.** After owner approval remove the allowlist check and review the first 10 real calls in Vapi for tone and accuracy before leaving it on.
- [ ] **Step 5: Update the memory notes and `voice/RUNBOOK.md` with the final state.**

---

## Self-review

- **Spec coverage:** disclosure, five services, Telegram alerts, retries, window, cap, rollback, allowlist rollout, secrets handling: each maps to a task above. Consent/recording and phone path are Open decisions, not silently decided.
- **Step scan:** code appears only as signatures and test names; the one algorithm the signature does not decide (reading Vapi's `endedReason` and structured outputs) is pinned to fixtures from real calls.
- **Type consistency:** `parseCall` outcome values (`confirmed`, `unsure`, `wants_callback`, `do_not_call`, `wrong_number`, `no_answer`, `voicemail`, `failed`) are used identically in Tasks 3, 4 and 5; the structured-output field names in Task 2 match what Task 3 reads.
- **Review Focus coverage:** answer quality → Task 2 step 4; voicemail/no answer → Tasks 3 and 5; duplicates → Task 5 step 2; do-not-call → Tasks 2, 3, 5; mid-call hiccups → Task 4 step 1 (poll cap) and Task 3 (`failed`).
- **Proportion:** about 100 lines of decisions; no code transcripts.
