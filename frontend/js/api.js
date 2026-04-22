import { getToken, logout } from './auth.js';

const BASE = '/api';

async function request(method, path, body) {
  const token = getToken();
  const res = await fetch(BASE + path, {
    method,
    headers: {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: body ? JSON.stringify(body) : undefined,
  });

  if (res.status === 401) { logout(); return; }
  if (!res.ok) {
    const err = await res.json().catch(() => ({ detail: res.statusText }));
    const detail = err.detail;
    const msg = typeof detail === 'string' ? detail
      : Array.isArray(detail) ? detail.map(d => d.msg || JSON.stringify(d)).join(', ')
      : detail ? JSON.stringify(detail) : `요청 실패 (${res.status})`;
    throw new Error(msg);
  }
  if (res.status === 204) return null;
  return res.json();
}

export const api = {
  login:  (b) => request('POST', '/auth/login', b),
  me:     ()  => request('GET',  '/auth/me'),

  listReports:  (limit=30, offset=0) => request('GET',    `/reports?limit=${limit}&offset=${offset}`),
  createReport: (b)   => request('POST',   '/reports', b),
  getReport:    (id)  => request('GET',    `/reports/${id}`),
  updateReport: (id,b)=> request('PUT',    `/reports/${id}`, b),
  deleteReport: (id)  => request('DELETE', `/reports/${id}`),

  listUsers:   ()      => request('GET',    '/users'),
  createUser:  (b)     => request('POST',   '/users', b),
  updateUser:  (id, b) => request('PUT',    `/users/${id}`, b),

  getSchedules: (from, to) => request('GET', `/schedules?date_from=${from}&date_to=${to}`),
  upsertSchedule: (b)         => request('PUT', '/schedules', b),
  adminUpsertSchedule: (uid,b)=> request('PUT', `/schedules/${uid}`, b),

  positions: () => request('GET', '/meta/positions'),
};

export function showToast(msg, duration = 2500) {
  let el = document.getElementById('toast');
  if (!el) {
    el = document.createElement('div');
    el.id = 'toast';
    el.className = 'toast';
    document.body.appendChild(el);
  }
  el.textContent = msg;
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), duration);
}
