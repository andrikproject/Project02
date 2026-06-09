// ── Progress bar mengikuti scroll ──
(function () {
  const bar = document.getElementById('progress-bar');
  if (!bar) return;
  function update() {
    const h = document.documentElement;
    const scrolled = h.scrollTop;
    const max = h.scrollHeight - h.clientHeight;
    const pct = max > 0 ? (scrolled / max) * 100 : 0;
    bar.style.width = pct + '%';
  }
  window.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
  update();
})();

// ── Toggle buka/tutup langkah ──
function toggleStep(headerEl) {
  const step = headerEl.closest('.step');
  if (step) step.classList.toggle('open');
}

// ── Salin isi blok kode ──
function copyCode(btn) {
  const block = btn.closest('.code-block');
  const pre = block ? block.querySelector('pre') : null;
  if (!pre) return;
  const text = pre.innerText;
  const done = () => {
    const old = btn.textContent;
    btn.textContent = 'Tersalin!';
    btn.classList.add('copied');
    setTimeout(() => { btn.textContent = old; btn.classList.remove('copied'); }, 1500);
  };
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(done).catch(() => fallbackCopy(text, done));
  } else {
    fallbackCopy(text, done);
  }
}

function fallbackCopy(text, cb) {
  const ta = document.createElement('textarea');
  ta.value = text;
  ta.style.position = 'fixed';
  ta.style.opacity = '0';
  document.body.appendChild(ta);
  ta.select();
  try { document.execCommand('copy'); } catch (e) {}
  document.body.removeChild(ta);
  if (cb) cb();
}

// ── Inisialisasi setelah DOM siap ──
document.addEventListener('DOMContentLoaded', function () {
  // Pasang handler pada header langkah (untuk konten yang di-render dari DB)
  document.querySelectorAll('.step-header').forEach(function (h) {
    h.addEventListener('click', function () { toggleStep(h); });
  });

  // Pasang handler tombol salin pada semua tombol .copy-btn
  document.querySelectorAll('.copy-btn').forEach(function (b) {
    b.addEventListener('click', function () { copyCode(b); });
  });

  // Buka langkah otomatis bila ada hash di URL (#step-3)
  if (location.hash) {
    const target = document.querySelector(location.hash);
    if (target && target.classList.contains('step')) {
      target.classList.add('open');
      setTimeout(() => target.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
    }
  }

  // Buka langkah pertama secara default (jika belum ada yang terbuka)
  const steps = document.querySelectorAll('.step');
  if (steps.length && !document.querySelector('.step.open')) {
    steps[0].classList.add('open');
  }
});

// ── Toolbar editor admin: sisipkan snippet ke textarea ──
function insertSnippet(btn, type) {
  const wrap = btn.closest('.step-edit') || document;
  const ta = wrap.querySelector('textarea.step-body-input');
  if (!ta) return;
  const snippets = {
    code: '<div class="code-block">\n  <div class="code-header"><span class="lang">bash</span><button class="copy-btn" type="button">Salin</button></div>\n  <pre>perintah di sini</pre>\n</div>\n',
    tip: '<div class="tip">Tulis tips di sini.</div>\n',
    warning: '<div class="warning">Tulis peringatan di sini.</div>\n',
    heading: '<h3>Sub-judul</h3>\n',
    para: '<p>Tulis paragraf di sini.</p>\n'
  };
  const text = snippets[type] || '';
  const start = ta.selectionStart;
  const end = ta.selectionEnd;
  ta.value = ta.value.substring(0, start) + text + ta.value.substring(end);
  ta.focus();
  ta.selectionStart = ta.selectionEnd = start + text.length;
}


// ── Toggle tema terang/gelap ──
(function () {
  function applyTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', t === 'light' ? '#f5f7fb' : '#070b14');
    try { localStorage.setItem('theme', t); } catch (e) {}
  }
  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('theme-toggle');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var cur = document.documentElement.getAttribute('data-theme') || 'dark';
      applyTheme(cur === 'dark' ? 'light' : 'dark');
    });
  });
})();
