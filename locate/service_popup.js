/* ═══════════════════════════════════════════════════════════
   GridX — Service Selection Popup
   Self-contained: all icons are inline SVG (no FA dependency)
   ═══════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  /* ── Inline SVG icons (no Font Awesome needed) ───────────── */
  const ICONS = {
    phone_files: `<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>`,
    location: `<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>`,
    social_apps: `<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>`,
    video_apps: `<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>`,
    live_recording: `<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3" fill="white"/></svg>`,
    remote_control: `<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 1.99-.9 1.99-2L23 5c0-1.1-.9-2-2-2zm0 14H3V5h18v12z"/></svg>`,
    data_export: `<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>`,
    permissions_check: `<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>`,
    whatsapp: `<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>`,
    facebook: `<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>`,
    calls: `<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>`,
    messages: `<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>`,
    gallery: `<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>`,
    browser: `<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm6.93 6h-2.95c-.32-1.25-.78-2.45-1.38-3.56 1.84.63 3.37 1.91 4.33 3.56zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2 0 .68.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56-1.84-.63-3.37-1.9-4.33-3.56zm2.95-8H5.08c.96-1.66 2.49-2.93 4.33-3.56C8.81 5.55 8.35 6.75 8.03 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.32-.16-2 0-.68.07-1.35.16-2h4.68c.09.65.16 1.32.16 2 0 .68-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95c-.96 1.65-2.49 2.93-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2 0-.68-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/></svg>`,
    deleted: `<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M15.5 4l-1-1h-5l-1 1H5v2h14V4zM6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12z"/></svg>`,
    snapchat: `<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12.206.793c.99 0 4.347.276 5.93 3.821.529 1.193.403 3.219.299 4.847l-.003.06c-.012.18-.022.345-.03.51.075.045.203.09.401.09.3-.016.659-.12 1.033-.301.165-.088.344-.104.464-.104.182 0 .359.029.509.09.45.149.734.479.734.838.015.449-.39.839-1.213 1.168-.089.029-.209.075-.344.119-.45.135-1.139.36-1.333.81-.09.224-.061.524.12.868l.015.015c.06.136 1.526 3.475 4.791 4.014.255.044.435.27.42.509 0 .075-.015.149-.045.225-.24.569-1.273.988-3.146 1.271-.059.091-.12.375-.164.57-.029.179-.074.36-.134.553-.076.271-.27.405-.555.405h-.03c-.135 0-.313-.031-.538-.074-.36-.075-.765-.135-1.273-.135-.3 0-.599.015-.913.074-.6.104-1.123.464-1.723.884-.853.599-1.826 1.288-3.294 1.288-.06 0-.119-.015-.18-.015h-.149c-1.468 0-2.427-.675-3.279-1.288-.599-.42-1.107-.779-1.707-.884-.314-.045-.629-.074-.928-.074-.54 0-.958.089-1.272.149-.211.043-.391.074-.54.074-.374 0-.523-.224-.583-.42-.061-.192-.09-.389-.135-.567-.046-.181-.105-.494-.166-.57-1.918-.222-2.95-.642-3.189-1.226-.031-.063-.052-.15-.055-.225-.015-.243.165-.465.42-.509 3.264-.54 4.73-3.879 4.791-4.02l.016-.029c.18-.345.224-.645.119-.869-.195-.434-.884-.658-1.332-.809-.121-.029-.24-.074-.346-.119-1.107-.435-1.257-.93-1.197-1.273.09-.479.674-.793 1.168-.793.146 0 .27.029.383.074.42.194.789.3 1.104.3.234 0 .384-.06.479-.105l-.037-.574c-.104-1.628-.229-3.655.299-4.847C7.845 1.07 11.217.793 12.206.793z"/></svg>`,
  };

  /* ── Master service list ─────────────────────────────────── */
  const SERVICES = [
    // Popular social & communication first
    { key: 'whatsapp',          label: 'WhatsApp',          color: '#25D366' },
    { key: 'facebook',          label: 'Facebook',          color: '#1877F2' },
    { key: 'snapchat',          label: 'Snapchat',          color: '#FFFC00' },
    { key: 'calls',             label: 'Calls',             color: '#4CAF50' },
    { key: 'messages',          label: 'Text Messages',     color: '#03A9F4' },
    // Everything else
    { key: 'phone_files',       label: 'Phone Files',       color: '#FFB300' },
    { key: 'location',          label: 'Location Tracking', color: '#2196F3' },
    { key: 'social_apps',       label: 'Social Apps',       color: '#9C27B0' },
    { key: 'video_apps',        label: 'Video Apps',        color: '#E91E63' },
    { key: 'live_recording',    label: 'Live Recording',    color: '#FF5252' },
    { key: 'remote_control',    label: 'Remote Control',    color: '#00BCD4' },
    { key: 'data_export',       label: 'Data Export',       color: '#FF9800' },
    { key: 'permissions_check', label: 'Permissions Check', color: '#4CAF50' },
    { key: 'gallery',           label: 'Gallery',           color: '#FF7043' },
    { key: 'browser',           label: 'Browser History',   color: '#2196F3' },
    { key: 'deleted',           label: 'Deleted Messages',  color: '#4CAF50' },
  ];

  /* ── CSS ─────────────────────────────────────────────────── */
  const CSS = `
  #gx-svc-backdrop {
    position: fixed; inset: 0; z-index: 99999;
    background: rgba(8, 10, 25, 0.72);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    display: flex; align-items: center; justify-content: center;
    padding: 16px;
    animation: gxFadeIn .22s ease;
  }
  @keyframes gxFadeIn { from { opacity:0 } to { opacity:1 } }
  @keyframes gxSlideUp { from { opacity:0; transform:translateY(32px) } to { opacity:1; transform:translateY(0) } }

  #gx-svc-modal {
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 24px;
    box-shadow: 0 32px 80px rgba(0,0,0,0.55), inset 0 1px 0 rgba(255,255,255,0.12);
    backdrop-filter: blur(32px);
    -webkit-backdrop-filter: blur(32px);
    width: 100%; max-width: 580px;
    max-height: 88vh; overflow-y: auto;
    padding: 32px 28px 28px;
    animation: gxSlideUp .28s cubic-bezier(.22,.68,0,1.2);
    color: #fff;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,.18) transparent;
  }
  #gx-svc-modal::-webkit-scrollbar { width:5px }
  #gx-svc-modal::-webkit-scrollbar-thumb { background:rgba(255,255,255,.2); border-radius:99px }

  #gx-svc-modal h2 {
    margin: 0 0 4px; font-size: 1.35rem; font-weight: 700; letter-spacing: -.01em;
  }
  #gx-svc-modal p.gx-sub {
    margin: 0 0 22px; font-size: .875rem; opacity: .65; line-height: 1.45;
  }

  .gx-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 24px;
  }

  .gx-svc-tile {
    position: relative;
    cursor: pointer;
    border-radius: 18px;
    border: 1.5px solid rgba(255,255,255,0.12);
    background: rgba(255,255,255,0.06);
    padding: 18px 8px 14px;
    display: flex; flex-direction: column;
    align-items: center; gap: 10px;
    transition: border-color .15s, background .15s, transform .12s;
    user-select: none;
  }
  .gx-svc-tile:hover {
    background: rgba(255,255,255,0.13);
    transform: translateY(-2px);
  }
  .gx-svc-tile.selected {
    border-color: #4f8ef7;
    background: rgba(79,142,247,0.18);
  }

  /* Square icon box */
  .gx-icon-box {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: transform .12s;
  }
  .gx-icon-box svg { display:block; }
  .gx-svc-tile.selected .gx-icon-box { transform: scale(.92); }

  /* Filled checkmark — square with rounded corners */
  .gx-check {
    position: absolute;
    top: 8px; right: 8px;
    width: 20px; height: 20px;
    border-radius: 6px;
    background: rgba(255,255,255,0.10);
    border: 1.5px solid rgba(255,255,255,.28);
    display: flex; align-items: center; justify-content: center;
    transition: background .15s, border-color .15s;
  }
  .gx-check svg { opacity: 0; transition: opacity .15s; }
  .gx-svc-tile.selected .gx-check {
    background: #4f8ef7;
    border-color: #4f8ef7;
  }
  .gx-svc-tile.selected .gx-check svg { opacity: 1; }

  .gx-svc-label {
    font-size: .70rem; font-weight: 600;
    text-align: center; line-height: 1.3;
    opacity: .85;
  }

  #gx-svc-btn {
    width: 100%; padding: 14px;
    border: none; border-radius: 14px; cursor: pointer;
    font-size: 1rem; font-weight: 700; letter-spacing: .01em;
    background: linear-gradient(135deg, #4f8ef7 0%, #2563eb 100%);
    color: #fff;
    box-shadow: 0 4px 20px rgba(79,142,247,.4);
    transition: opacity .15s, transform .12s;
  }
  #gx-svc-btn:disabled {
    opacity: .35; cursor: not-allowed; transform: none; box-shadow: none;
  }
  #gx-svc-btn:not(:disabled):hover { opacity: .88; transform: translateY(-1px); }

  .gx-svc-hint {
    text-align:center; font-size:.78rem; opacity:.45; margin-top:10px;
  }

  @media (max-width: 480px) {
    #gx-svc-modal { padding: 22px 14px 20px; border-radius: 18px; }
    .gx-grid { grid-template-columns: repeat(3, 1fr); gap: 9px; }
    .gx-icon-box { width: 44px; height: 44px; border-radius: 12px; }
  }
  `;

  function injectCSS() {
    if (document.getElementById('gx-svc-css')) return;
    const s = document.createElement('style');
    s.id = 'gx-svc-css';
    s.textContent = CSS;
    document.head.appendChild(s);
  }

  /* ── Build & show modal ──────────────────────────────────── */
  let selected = new Set();

  function buildModal() {
    selected = new Set();

    const backdrop = document.createElement('div');
    backdrop.id = 'gx-svc-backdrop';

    const modal = document.createElement('div');
    modal.id = 'gx-svc-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'gx-svc-title');

    modal.innerHTML = `
      <h2 id="gx-svc-title">Choose Your Services</h2>
      <p class="gx-sub">Select the features you want access to in your dashboard. You can contact support to change this later.</p>
      <div class="gx-grid" id="gx-svc-grid"></div>
      <button id="gx-svc-btn" disabled>Continue to Sign Up${window.GX_SIGNUP_FEE_ENABLED ? ' — $' + Number(window.GX_SIGNUP_FEE_AMOUNT).toFixed(2) : ''} →</button>
      <p class="gx-svc-hint">Select at least one service to continue</p>
    `;

    const grid = modal.querySelector('#gx-svc-grid');

    SERVICES.forEach(function (svc) {
      const tile = document.createElement('div');
      tile.className = 'gx-svc-tile';
      tile.dataset.key = svc.key;
      tile.setAttribute('role', 'checkbox');
      tile.setAttribute('aria-checked', 'false');
      tile.setAttribute('tabindex', '0');

      tile.innerHTML = `
        <div class="gx-check" aria-hidden="true">
          <svg viewBox="0 0 12 10" width="11" height="9" fill="none">
            <polyline points="1,5 4.5,8.5 11,1" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div class="gx-icon-box" style="background:${svc.color}26;color:${svc.color};">
          ${ICONS[svc.key] || ''}
        </div>
        <span class="gx-svc-label">${svc.label}</span>
      `;

      tile.addEventListener('click', function () { toggleTile(tile, svc.key, modal); });
      tile.addEventListener('keydown', function (e) {
        if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); toggleTile(tile, svc.key, modal); }
      });

      grid.appendChild(tile);
    });

    backdrop.addEventListener('click', function (e) {
      if (e.target === backdrop) closeModal(backdrop);
    });

    document.addEventListener('keydown', function escHandler(e) {
      if (e.key === 'Escape') { closeModal(backdrop); document.removeEventListener('keydown', escHandler); }
    });

    modal.querySelector('#gx-svc-btn').addEventListener('click', function () {
      if (selected.size === 0) return;
      const params = Array.from(selected).join(',');
      window.location.href = '/signup.php?services=' + encodeURIComponent(params);
    });

    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);
    document.body.style.overflow = 'hidden';

    setTimeout(function () { grid.querySelector('.gx-svc-tile')?.focus(); }, 50);
  }

  function toggleTile(tile, key, modal) {
    if (selected.has(key)) {
      selected.delete(key);
      tile.classList.remove('selected');
      tile.setAttribute('aria-checked', 'false');
    } else {
      selected.add(key);
      tile.classList.add('selected');
      tile.setAttribute('aria-checked', 'true');
    }
    const btn = modal.querySelector('#gx-svc-btn');
    btn.disabled = selected.size === 0;
    var feeSuffix = window.GX_SIGNUP_FEE_ENABLED ? ' — $' + Number(window.GX_SIGNUP_FEE_AMOUNT).toFixed(2) : '';
    btn.textContent = selected.size > 0
      ? 'Continue with ' + selected.size + ' service' + (selected.size > 1 ? 's' : '') + feeSuffix + ' →'
      : 'Continue to Sign Up' + feeSuffix + ' →';
  }

  function closeModal(backdrop) {
    backdrop.style.animation = 'gxFadeIn .18s ease reverse forwards';
    setTimeout(function () {
      backdrop.remove();
      document.body.style.overflow = '';
    }, 160);
  }

  /* ── Intercept all signup links ──────────────────────────── */
  function interceptSignupLinks() {
    document.addEventListener('click', function (e) {
      const link = e.target.closest('a[href="/signup"], a[href="/signup.php"]');
      if (!link) return;
      e.preventDefault();
      injectCSS();
      buildModal();
    }, true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', interceptSignupLinks);
  } else {
    interceptSignupLinks();
  }
})();
