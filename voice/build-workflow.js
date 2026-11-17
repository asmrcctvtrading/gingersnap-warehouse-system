'use strict';
// Generates workflow-voice.json (shadow mode) from the live Telegram workflow's
// Normalize Phone / Bad Phone Alert nodes plus guard.js. No secrets are written;
// credentials are assigned in the n8n UI.
const fs = require('node:fs');
const path = require('node:path');

const live = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
const pick = (name) => JSON.parse(JSON.stringify(live.nodes.find((n) => n.name === name)));
const guardSrc = fs.readFileSync(path.join(__dirname, 'guard.js'), 'utf8')
  .replace(/if \(typeof module[\s\S]*$/, '');

const webhook = pick('Lead Webhook');
webhook.parameters.path = 'asmr-lead-voice';
webhook.id = 'v1-webhook';
webhook.webhookId = 'asmr-lead-voice-0001';

const normalize = pick('Normalize Phone'); normalize.id = 'v2-normalize';
const valid = pick('Phone Valid?'); valid.id = 'v3-valid';
const bad = pick('Bad Phone Alert'); bad.id = 'v4-bad'; bad.position = [660, 120];

const guardCode = `// SHADOW MODE: decides only. Nothing in this workflow places a call.
// Owner: put allowlisted numbers (E.164) in ALLOWLIST below. Empty = nobody is allowed.
const ALLOWLIST = [];
${guardSrc}
const lead = $input.first().json;
const state = $getWorkflowStaticData('global');
const res = evaluateLead(lead, state, new Date(), { allowlist: ALLOWLIST });
state.callsByDay = res.state.callsByDay;
state.lastSeen = res.state.lastSeen;
return [{ json: { ...lead, action: res.action, reason: res.reason, nextWindowStart: res.nextWindowStart || '' } }];`;

const guard = { parameters: { jsCode: guardCode }, id: 'v5-guard', name: 'Guard', type: 'n8n-nodes-base.code', typeVersion: 2, position: [660, -120] };

const route = JSON.parse(JSON.stringify(valid));
route.id = 'v6-route'; route.name = 'Allowed To Call?'; route.position = [880, -120];
route.parameters.conditions.conditions[0] = {
  id: 'c2', leftValue: '={{ $json.action }}', rightValue: 'call',
  operator: { type: 'string', operation: 'equals' },
};

const would = {
  parameters: {
    chatId: '8179698982',
    text: '=[SHADOW] Would call via Vapi (no call placed)\nName: {{ $json.name }}\nPhone: {{ $json.phone }}\nForm service: {{ $json.form_service }}\nvariableValues: lead_name={{ $json.name }}, lead_company={{ $json.company }}, form_service={{ $json.form_service }}',
    additionalFields: {},
  },
  id: 'v7-would', name: 'Shadow Would Call', type: 'n8n-nodes-base.telegram', typeVersion: 1.2, position: [1100, -200],
};
const skipped = {
  parameters: {
    chatId: '8179698982',
    text: '=[SHADOW] Call skipped: {{ $json.reason }}\nName: {{ $json.name }}\nPhone: {{ $json.phone }}\nNext window: {{ $json.nextWindowStart || "n/a" }}',
    additionalFields: {},
  },
  id: 'v8-skip', name: 'Shadow Skipped', type: 'n8n-nodes-base.telegram', typeVersion: 1.2, position: [1100, -40],
};

const out = {
  name: 'ASMR Lead Confirm (Voice, shadow)',
  nodes: [webhook, normalize, valid, bad, guard, route, would, skipped],
  connections: {
    [webhook.name]: { main: [[{ node: normalize.name, type: 'main', index: 0 }]] },
    [normalize.name]: { main: [[{ node: valid.name, type: 'main', index: 0 }]] },
    [valid.name]: { main: [[{ node: guard.name, type: 'main', index: 0 }], [{ node: bad.name, type: 'main', index: 0 }]] },
    [guard.name]: { main: [[{ node: route.name, type: 'main', index: 0 }]] },
    [route.name]: { main: [[{ node: would.name, type: 'main', index: 0 }], [{ node: skipped.name, type: 'main', index: 0 }]] },
  },
  settings: { executionOrder: 'v1' },
};
fs.writeFileSync(path.join(__dirname, 'workflow-voice.json'), JSON.stringify(out, null, 2) + '\n');
console.log('wrote workflow-voice.json with', out.nodes.length, 'nodes');
