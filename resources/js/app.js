/* ============================================================
   app.js — centralized helpers on window.App
   Vanilla JS. No framework.
   ============================================================ */

const App = (() => {
  const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

  /* ---------- HTTP ---------- */
  async function request(method, url, body = null, isForm = false) {
    const headers = {
      'X-CSRF-TOKEN': csrf(),
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json',
    };
    let payload = body;
    if (body && !isForm) {
      headers['Content-Type'] = 'application/json';
      payload = JSON.stringify(body);
    }
    const res = await fetch(url, { method, headers, body: payload });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw Object.assign(new Error(data.message || 'Request failed'), { data, status: res.status });
    return data;
  }

  const get    = (url)        => request('GET', url);
  const post   = (url, body, isForm = false) => request('POST', url, body, isForm);
  const put    = (url, body)  => request('PUT', url, body);
  const del    = (url)        => request('DELETE', url);

  /* ---------- Form serialization ---------- */
  function serialize(formEl) {
    const out = {};
    new FormData(formEl).forEach((v, k) => {
      if (out[k] !== undefined) {
        out[k] = [].concat(out[k], v);
      } else out[k] = v;
    });
    return out;
  }

  /* ---------- Toasts ---------- */
  function ensureStack() {
    let s = document.querySelector('.toast-stack');
    if (!s) { s = document.createElement('div'); s.className = 'toast-stack'; document.body.appendChild(s); }
    return s;
  }
  function toast(message, type = 'info', ttl = 3200) {
    const el = document.createElement('div');
    el.className = `toast-item is-${type}`;
    el.textContent = message;
    ensureStack().appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 200); }, ttl);
  }

  /* ---------- Loading state ---------- */
  function loading(el, on = true) {
    if (!el) return;
    el.classList.toggle('is-loading', on);
    if (on) {
      if (el.querySelector('.spinner-inline')) return; // already loading
      // Hide existing content, show only the spinner
      [...el.children].forEach(c => {
        c.classList.add('js-hidden-by-loading');
        c.style.display = 'none';
      });
      const sp = document.createElement('span');
      sp.className = 'spinner-inline';
      el.appendChild(sp);
    } else {
      el.querySelector('.spinner-inline')?.remove();
      el.querySelectorAll('.js-hidden-by-loading').forEach(c => {
        c.style.display = '';
        c.classList.remove('js-hidden-by-loading');
      });
    }
  }

  /* ---------- Modal (Bootstrap wrapper) ---------- */
  function modal(id) {
    const node = document.getElementById(id);
    if (!node || !window.bootstrap) return null;
    return window.bootstrap.Modal.getOrCreateInstance(node);
  }

  /* ---------- Util ---------- */
  function fmtTime(seconds) {
    if (!seconds || isNaN(seconds)) return '0:00';
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
  }

  return { get, post, put, del, serialize, toast, loading, modal, fmtTime };
})();

window.App = App;
export default App;