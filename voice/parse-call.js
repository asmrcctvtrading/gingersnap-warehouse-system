'use strict';
/**
 * Turns a finished Vapi call (GET /call/{id}) into one outcome record.
 * Pure function: no network, no n8n APIs. Paste the body into an n8n Code node
 * (the `module.exports` line at the bottom is guarded for that).
 *
 * NOTE: the endedReason strings below come from Vapi's documented values and
 * hand-written fixtures. Replace/confirm them with fixtures captured from real
 * test calls (plan Task 4) before relying on them in production.
 */

const SERVICES = [
  'CCTV Systems',
  'Systems Development',
  'AI Automation',
  'Network Setup',
  'Other',
];

const OUTPUT_NAME = 'lead_confirmation';

const VOICEMAIL_RE = /voicemail/i;
const NO_ANSWER_RE = /did-not-answer|no-answer|customer-busy|busy|rejected|declined/i;
const PROVIDER_FAILURE_RE = /failed|error|sip-gateway|provider|unreachable|invalid-number|couldnt|could-not/i;

function failed(endedReason, notes) {
  return {
    outcome: 'failed',
    service: null,
    callbackTime: null,
    notes: notes || '',
    endedReason: endedReason || '',
  };
}

function findResult(call) {
  const outputs = call && call.artifact && call.artifact.structuredOutputs;
  if (!outputs || typeof outputs !== 'object') return null;
  const entries = Object.values(outputs).filter((e) => e && e.result && typeof e.result === 'object');
  const named = entries.find((e) => e.name === OUTPUT_NAME);
  return (named || entries.find((e) => e.result.outcome) || {}).result || null;
}

function parseCall(call) {
  if (!call || typeof call !== 'object') return failed('', 'no call data');
  const endedReason = typeof call.endedReason === 'string' ? call.endedReason : '';

  if (call.status !== 'ended') return failed(endedReason, 'call not ended');
  if (VOICEMAIL_RE.test(endedReason)) {
    return { outcome: 'voicemail', service: null, callbackTime: null, notes: '', endedReason };
  }
  if (NO_ANSWER_RE.test(endedReason)) {
    return { outcome: 'no_answer', service: null, callbackTime: null, notes: '', endedReason };
  }
  if (PROVIDER_FAILURE_RE.test(endedReason)) return failed(endedReason, 'provider failure');

  const r = findResult(call);
  if (!r || typeof r.outcome !== 'string') return failed(endedReason, 'no structured output');

  const notes = typeof r.notes === 'string' ? r.notes : '';
  const callbackTime = typeof r.callback_time === 'string' && r.callback_time ? r.callback_time : null;
  const base = { notes, endedReason };

  switch (r.outcome) {
    case 'do_not_call':
    case 'wrong_number':
      return { ...base, outcome: r.outcome, service: null, callbackTime: null };
    case 'wants_callback':
      return { ...base, outcome: 'wants_callback', service: null, callbackTime };
    case 'unsure':
      return { ...base, outcome: 'unsure', service: null, callbackTime: null };
    case 'confirmed': {
      const s = typeof r.confirmed_service === 'string' ? r.confirmed_service.trim() : '';
      if (!s) return { ...base, outcome: 'unsure', service: null, callbackTime: null };
      const service = SERVICES.includes(s) ? s : 'Other';
      return { ...base, outcome: 'confirmed', service, callbackTime: null };
    }
    default:
      return failed(endedReason, 'unknown outcome: ' + r.outcome);
  }
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = { parseCall, SERVICES };
}
