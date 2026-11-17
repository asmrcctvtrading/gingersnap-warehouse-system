'use strict';
/**
 * Decides whether the voice workflow may place a call for one lead.
 * Pure function: state in, new state out. In n8n the Code node passes
 * $getWorkflowStaticData('global') as `state` and writes `result.state` back.
 * Checks, in order: allowlist, duplicate, call window, daily cap.
 * Skipped leads never change the counters.
 */

const DEFAULTS = {
  allowlist: [],            // E.164 numbers; owner fills this in the n8n node
  enforceAllowlist: true,   // stays true until plan Task 7 is approved
  dedupMinutes: 10,
  windowStartHour: 8,       // Asia/Manila, inclusive
  windowEndHour: 20,        // Asia/Manila, exclusive
  dailyCap: 30,
};

const MANILA_OFFSET_MS = 8 * 3600000; // Manila has no DST
const DAY_MS = 86400000;

function manilaParts(now) {
  const m = new Date(now.getTime() + MANILA_OFFSET_MS);
  return {
    date: m.toISOString().slice(0, 10),
    hour: m.getUTCHours(),
    minute: m.getUTCMinutes(),
  };
}

function nextWindowStart(now, cfg) {
  const p = manilaParts(now);
  const [y, mo, d] = p.date.split('-').map(Number);
  let startUtc = Date.UTC(y, mo - 1, d, cfg.windowStartHour) - MANILA_OFFSET_MS;
  if (p.hour >= cfg.windowStartHour) startUtc += DAY_MS;
  return new Date(startUtc).toISOString();
}

function prune(state, now) {
  const today = manilaParts(now).date;
  const yesterday = manilaParts(new Date(now.getTime() - DAY_MS)).date;
  const callsByDay = {};
  for (const [day, n] of Object.entries(state.callsByDay || {})) {
    if (day === today || day === yesterday) callsByDay[day] = n;
  }
  const lastSeen = {};
  for (const [phone, t] of Object.entries(state.lastSeen || {})) {
    if (now.getTime() - t < DAY_MS) lastSeen[phone] = t;
  }
  return { callsByDay, lastSeen };
}

function evaluateLead(lead, state, now, config) {
  const cfg = { ...DEFAULTS, ...(config || {}) };
  const clean = prune(state && typeof state === 'object' ? state : {}, now);
  const skip = (reason, extra) => ({ action: 'skip', reason, state: clean, ...(extra || {}) });

  const phone = lead && typeof lead.phone === 'string' ? lead.phone : '';
  if (!/^\+63\d{9,10}$/.test(phone)) return skip('invalid_phone');

  if (cfg.enforceAllowlist && !cfg.allowlist.includes(phone)) return skip('not_allowlisted');

  const last = clean.lastSeen[phone];
  if (last !== undefined && now.getTime() - last < cfg.dedupMinutes * 60000) return skip('duplicate');

  const p = manilaParts(now);
  if (p.hour < cfg.windowStartHour || p.hour >= cfg.windowEndHour) {
    return skip('outside_window', { nextWindowStart: nextWindowStart(now, cfg) });
  }

  if ((clean.callsByDay[p.date] || 0) >= cfg.dailyCap) return skip('daily_cap');

  clean.callsByDay[p.date] = (clean.callsByDay[p.date] || 0) + 1;
  clean.lastSeen[phone] = now.getTime();
  return { action: 'call', reason: '', state: clean };
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = { evaluateLead, manilaParts, DEFAULTS };
}
