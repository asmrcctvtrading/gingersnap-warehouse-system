'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const { evaluateLead, manilaParts, DEFAULTS } = require('./guard.js');

const cfg = { ...DEFAULTS, allowlist: ['+639171234567'] };
const at = (iso) => new Date(iso); // all times below are UTC; Manila = UTC+8
const noon = at('2026-11-17T04:00:00Z'); // 12:00 Manila
const lead = (phone = '+639171234567') => ({ phone });

test('allowlisted number inside window is a call and records state', () => {
  const r = evaluateLead(lead(), {}, noon, cfg);
  assert.equal(r.action, 'call');
  assert.equal(r.state.callsByDay['2026-11-17'], 1);
  assert.equal(r.state.lastSeen['+639171234567'], noon.getTime());
});

test('number not on the allowlist is skipped with reason not_allowlisted', () => {
  const r = evaluateLead(lead('+639189999999'), {}, noon, cfg);
  assert.deepEqual([r.action, r.reason], ['skip', 'not_allowlisted']);
});

test('empty allowlist with enforcement on skips everyone', () => {
  const r = evaluateLead(lead(), {}, noon, { ...cfg, allowlist: [] });
  assert.equal(r.reason, 'not_allowlisted');
});

test('same phone again within 10 minutes is a duplicate', () => {
  const first = evaluateLead(lead(), {}, noon, cfg);
  const r = evaluateLead(lead(), first.state, new Date(noon.getTime() + 9 * 60000), cfg);
  assert.deepEqual([r.action, r.reason], ['skip', 'duplicate']);
});

test('same phone after 10 minutes is allowed again', () => {
  const first = evaluateLead(lead(), {}, noon, cfg);
  const r = evaluateLead(lead(), first.state, new Date(noon.getTime() + 11 * 60000), cfg);
  assert.equal(r.action, 'call');
});

test('21:00 Manila is outside the window and reports next 08:00 Manila', () => {
  const r = evaluateLead(lead(), {}, at('2026-11-17T13:00:00Z'), cfg);
  assert.deepEqual([r.action, r.reason], ['skip', 'outside_window']);
  assert.equal(r.nextWindowStart, '2026-11-18T00:00:00.000Z'); // 08:00 Manila 18 Nov
});

test('03:00 Manila reports 08:00 Manila the same day', () => {
  const r = evaluateLead(lead(), {}, at('2026-11-16T19:00:00Z'), cfg);
  assert.equal(r.reason, 'outside_window');
  assert.equal(r.nextWindowStart, '2026-11-17T00:00:00.000Z');
});

test('20:00 Manila exactly is outside, 08:00 exactly is inside', () => {
  assert.equal(evaluateLead(lead(), {}, at('2026-11-17T12:00:00Z'), cfg).reason, 'outside_window');
  assert.equal(evaluateLead(lead(), {}, at('2026-11-17T00:00:00Z'), cfg).action, 'call');
});

test('daily cap: the 31st call of a Manila day is skipped', () => {
  const state = { callsByDay: { '2026-11-17': 30 }, lastSeen: {} };
  const r = evaluateLead(lead(), state, noon, cfg);
  assert.deepEqual([r.action, r.reason], ['skip', 'daily_cap']);
});

test('daily cap resets on the next Manila date', () => {
  const state = { callsByDay: { '2026-11-17': 30 }, lastSeen: {} };
  const r = evaluateLead(lead(), state, at('2026-11-18T04:00:00Z'), cfg);
  assert.equal(r.action, 'call');
});

test('skipped leads do not use up the cap or refresh the dedup clock', () => {
  const r = evaluateLead(lead('+639189999999'), {}, noon, cfg);
  assert.equal(r.state.callsByDay['2026-11-17'] || 0, 0);
  assert.equal(r.state.lastSeen['+639189999999'], undefined);
});

test('old state is pruned so static data does not grow forever', () => {
  const state = { callsByDay: { '2026-10-01': 5 }, lastSeen: { '+639170000000': noon.getTime() - 3 * 86400000 } };
  const r = evaluateLead(lead(), state, noon, cfg);
  assert.equal(r.state.callsByDay['2026-10-01'], undefined);
  assert.equal(r.state.lastSeen['+639170000000'], undefined);
});

test('manilaParts returns Manila date and hour', () => {
  assert.deepEqual(manilaParts(at('2026-11-17T17:30:00Z')), { date: '2026-11-18', hour: 1, minute: 30 });
});

test('missing or garbage input never throws', () => {
  assert.equal(evaluateLead(null, null, noon, cfg).action, 'skip');
  assert.equal(evaluateLead({}, undefined, noon, cfg).action, 'skip');
});
