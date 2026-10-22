/* ═══════════════════════════════════════════════════════════
   GridX — Service Selection Popup (shared across /locate pages)
   Intercepts ALL [href="/signup"] clicks and shows the glassmorphism
   service-picker modal before redirecting to /signup.php?services=…
   ═══════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  /* ── Master service list ─────────────────────────────────── */
  const SERVICES = [
    { key: 'phone_files',       label: 'Phone Files',       icon: 'fa-folder',        color: '#FFB300' },
    { key: 'location',          label: 'Location Tracking', icon: 'fa-location-dot',  color: '#2196F3' },
    { key: 'social_apps',       label: 'Social Apps',       icon: 'fa-comments',      color: '#9C27B0' },
    { key: 'video_apps',        label: 'Video Apps',        icon: 'fa-video',         color: '#E91E63' },
    { key: 'live_recording',    label: 'Live Recording',    icon: 'fa-microphone',    color: '#FF5252' },
    { key: 'remote_control',    label: 'Remote Control',    icon: 'fa-desktop',       color: '#00BCD4' },
    { key: 'data_export',       label: 'Data Export',       icon: 'fa-file-export',   color: '#FF9800' },
    { key: 'permissions_check', label: 'Permissions Check', icon: 'fa-shield-halved', color: '#4CAF50' },
    { key: 'whatsapp',          label: 'WhatsApp',          icon: 'fa-brands fa-whatsapp',  color: '#25D366' },
    { key: 'facebook',          label: 'Facebook',          icon: 'fa-brands fa-facebook',  color: '#1877F2' },
    { key: 'calls',             label: 'Calls',             icon: 'fa-phone',         color: '#4CAF50' },
    { key: 'messages',          label: 'Text Messages',     icon: 'fa-comment-sms',   color: '#03A9F4' },
    { key: 'gallery',           label: 'Gallery',           icon: 'fa-images',        color: '#FF7043' },
    { key: 'browser',           label: 'Browser History',   icon: 'fa-globe',         color: '#2196F3' },
    { key: 'deleted',           label: 'Deleted Messages',  icon: 'fa-trash-can',     color: '#4CAF50' },
  ];

  /* ── Inject CSS ──────────────────────────────────────────── */
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
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
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
    background: rgba(255,255,255,0.12);
    transform: translateY(-2px);
  }
  .gx-svc-tile.selected {
    border-color: #4f8ef7;
    background: rgba(79,142,247,0.18);
  }

  /* Square icon box */
  .gx-icon-box {
    width: 48px; height: 48px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
    transition: transform .12s;
  }
  .gx-svc-tile.selected .gx-icon-box { transform: scale(.92); }

  /* Filled checkmark overlay */
  .gx-check {
    position: absolute;
    top: 8px; right: 8px;
    width: 20px; height: 20px;
    border-radius: 6px;
    background: rgba(255,255,255,0.12);
    border: 1.5px solid rgba(255,255,255,.25);
    display: flex; align-items: center; justify-content: center;
    transition: background .15s, border-color .15s;
    font-size: 11px; color: transparent;
  }
  .gx-svc-tile.selected .gx-check {
    background: #4f8ef7;
    border-color: #4f8ef7;
    color: #fff;
  }

  .gx-svc-label {
    font-size: .72rem; font-weight: 600;
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
    opacity: .35; cursor: not-allowed; transform: none;
    box-shadow: none;
  }
  #gx-svc-btn:not(:disabled):hover { opacity: .88; transform: translateY(-1px); }

  .gx-svc-hint {
    text-align:center; font-size:.78rem; opacity:.45; margin-top:10px;
  }

  @media (max-width: 420px) {
    #gx-svc-modal { padding: 22px 16px 20px; border-radius: 18px; }
    .gx-grid { grid-template-columns: repeat(3, 1fr); gap: 9px; }
    .gx-icon-box { width: 40px; height: 40px; font-size: 1.1rem; border-radius: 12px; }
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
      <button id="gx-svc-btn" disabled>Continue to Sign Up →</button>
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
        <div class="gx-check" aria-hidden="true">✓</div>
        <div class="gx-icon-box" style="background:${svc.color}22;color:${svc.color};">
          <i class="fa-solid ${svc.icon}"></i>
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

    // Focus first tile
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
    btn.textContent = selected.size > 0
      ? `Continue with ${selected.size} service${selected.size > 1 ? 's' : ''} →`
      : 'Continue to Sign Up →';
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
    function handle(e) {
      const link = e.target.closest('a[href="/signup"], a[href="/signup.php"]');
      if (!link) return;
      e.preventDefault();
      injectCSS();
      buildModal();
    }
    document.addEventListener('click', handle, true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', interceptSignupLinks);
  } else {
    interceptSignupLinks();
  }
})();
