const TOKEN_KEY = 'ihrd_token';
const USER_KEY  = 'ihrd_user';

export function saveSession(token, user) {
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(USER_KEY, JSON.stringify(user));
}

export function clearSession() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
}

export function getToken() {
  return localStorage.getItem(TOKEN_KEY);
}

export function getUser() {
  const raw = localStorage.getItem(USER_KEY);
  return raw ? JSON.parse(raw) : null;
}

export function isLoggedIn() {
  return !!getToken();
}

export function requireAuth() {
  if (!isLoggedIn()) {
    location.href = '/login.html';
  }
}

export function requireAdmin() {
  requireAuth();
  const user = getUser();
  if (!user || user.role !== 'admin') {
    location.href = '/index.html';
  }
}

export function logout() {
  clearSession();
  location.href = '/login.html';
}

export function renderTopbarUser() {
  const user = getUser();
  if (!user) return;
  const el = document.getElementById('topbar-user');
  if (el) el.innerHTML = `<strong>${user.name}</strong> (${user.role === 'admin' ? '관리자' : '보고자'})`;
  const adminLinks = document.querySelectorAll('.admin-only');
  adminLinks.forEach(el => {
    el.style.display = user.role === 'admin' ? '' : 'none';
  });
}
