'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const path = require('node:path');
const { parseCall } = require('./parse-call.js');

const fx = (name) => require(path.join(__dirname, 'fixtures', name + '.json'));

test('confirmed call returns outcome confirmed and the exact service string', () => {
  const r = parseCall(fx('confirmed'));
  assert.equal(r.outcome, 'confirmed');
  assert.equal(r.service, 'CCTV Systems');
  assert.match(r.notes, /4-channel/);
});

test('voicemail endedReason returns outcome voicemail even if a service was captured', () => {
  const r = parseCall(fx('voicemail'));
  assert.equal(r.outcome, 'voicemail');
  assert.equal(r.service, null);
});

test('customer never picked up returns outcome no_answer', () => {
  assert.equal(parseCall(fx('no-answer')).outcome, 'no_answer');
});

test('customer busy returns outcome no_answer', () => {
  assert.equal(parseCall(fx('busy')).outcome, 'no_answer');
});

test('do_not_call from the structured output wins over a captured service', () => {
  const r = parseCall(fx('do-not-call'));
  assert.equal(r.outcome, 'do_not_call');
  assert.equal(r.service, null);
});

test('wrong_number is passed through with no service', () => {
  const r = parseCall(fx('wrong-number'));
  assert.equal(r.outcome, 'wrong_number');
  assert.equal(r.service, null);
});

test('wants_callback keeps the callback time', () => {
  const r = parseCall(fx('wants-callback'));
  assert.equal(r.outcome, 'wants_callback');
  assert.equal(r.callbackTime, 'tomorrow after 3pm');
});

test('missing structured outputs on an ended call returns outcome failed, not a crash', () => {
  assert.equal(parseCall(fx('no-structured')).outcome, 'failed');
});

test('service not in the five allowed strings is returned as Other', () => {
  const r = parseCall(fx('unknown-service'));
  assert.equal(r.outcome, 'confirmed');
  assert.equal(r.service, 'Other');
});

test('confirmed with no service is downgraded to unsure', () => {
  const r = parseCall(fx('confirmed-without-service'));
  assert.equal(r.outcome, 'unsure');
  assert.equal(r.service, null);
});

test('provider failure endedReason returns outcome failed', () => {
  assert.equal(parseCall(fx('provider-failure')).outcome, 'failed');
});

test('a call that has not ended returns outcome failed instead of guessing', () => {
  assert.equal(parseCall(fx('not-ended')).outcome, 'failed');
});

test('null or empty input never throws', () => {
  assert.equal(parseCall(null).outcome, 'failed');
  assert.equal(parseCall({}).outcome, 'failed');
});

test('result always carries the endedReason for logging', () => {
  assert.equal(parseCall(fx('no-answer')).endedReason, 'customer-did-not-answer');
});
