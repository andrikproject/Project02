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


// ════════════════════════════════════════════
// Dark code syntax highlighting (ringan, tanpa library)
// ════════════════════════════════════════════
(function () {
  function esc(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  var STRING = "\"(?:[^\"\\\\]|\\\\.)*\"|'(?:[^'\\\\]|\\\\.)*'|`(?:[^`\\\\]|\\\\.)*`";
  var NUMBER = "\\b\\d+(?:\\.\\d+)?\\b";

  function rulesFor(lang) {
    switch (lang) {
      case 'python':
        return [
          { re: '#[^\\n]*', cls: 'comment' },
          { re: STRING, cls: 'string' },
          { re: '\\b(?:import|from|as|def|return|class|if|elif|else|for|while|in|not|and|or|is|None|True|False|with|try|except|finally|print|lambda|yield|await|async|pass|break|continue)\\b', cls: 'keyword' },
          { re: NUMBER, cls: 'number' }
        ];
      case 'javascript': case 'js': case 'node':
        return [
          { re: '\\/\\/[^\\n]*', cls: 'comment' },
          { re: STRING, cls: 'string' },
          { re: '\\b(?:const|let|var|function|return|if|else|for|while|require|module|exports|async|await|new|class|extends|import|from|export|true|false|null|undefined|console|await)\\b', cls: 'keyword' },
          { re: NUMBER, cls: 'number' }
        ];
      case 'sql':
        return [
          { re: '--[^\\n]*', cls: 'comment' },
          { re: STRING, cls: 'string' },
          { re: '\\b(?:CREATE|DATABASE|USER|IDENTIFIED|BY|GRANT|ALL|PRIVILEGES|ON|TO|FLUSH|SELECT|INSERT|INTO|UPDATE|DELETE|FROM|WHERE|VALUES|TABLE|PRIMARY|KEY|NOT|NULL|DEFAULT)\\b', cls: 'keyword' },
          { re: NUMBER, cls: 'number' }
        ];
      case 'yaml': case 'yml': case 'nginx': case 'hcl': case 'text':
        return [
          { re: '#[^\\n]*', cls: 'comment' },
          { re: STRING, cls: 'string' },
          { re: NUMBER, cls: 'number' }
        ];
      default: // bash & lainnya
        return [
          { re: '#[^\\n]*', cls: 'comment' },
          { re: STRING, cls: 'string' },
          { re: '\\$\\{[^}]*\\}|\\$[A-Za-z_]\\w*', cls: 'var' },
          { re: '(?<=\\s)-{1,2}[A-Za-z][\\w-]*', cls: 'flag' },
          { re: '\\b(?:sudo|apt|apt-get|curl|wget|git|docker|npm|npx|pnpm|yarn|pip|pip3|python|python3|node|ssh|ssh-keygen|systemctl|service|cd|mkdir|rm|cp|mv|nano|vim|echo|export|source|bash|sh|chmod|chown|tar|unzip|crontab|ufw|ollama|pm2|ffmpeg|yt-dlp|caddy|nginx|mysql|mariadb|psql|redis-cli|mongosh|interpreter|vllm|whisper|flowise|cloudflared|kubectl|k3s|terraform|wg|install|enable|start|run|reload|status|up|down)\\b', cls: 'cmd' },
          { re: NUMBER, cls: 'number' }
        ];
    }
  }

  function highlight(text, lang) {
    var rules = rulesFor(lang);
    var combined;
    try {
      combined = new RegExp(rules.map(function (r) { return '(' + r.re + ')'; }).join('|'), 'g');
    } catch (e) {
      return esc(text); // lookbehind tak didukung -> fallback polos
    }
    var out = '', last = 0, m;
    while ((m = combined.exec(text)) !== null) {
      if (m.index > last) out += esc(text.slice(last, m.index));
      var gi = 1;
      for (; gi <= rules.length; gi++) { if (m[gi] !== undefined) break; }
      out += '<span class="tok-' + rules[gi - 1].cls + '">' + esc(m[0]) + '</span>';
      last = m.index + m[0].length;
      if (m[0].length === 0) combined.lastIndex++;
    }
    out += esc(text.slice(last));
    return out;
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.code-block').forEach(function (block) {
      var pre = block.querySelector('pre');
      var langEl = block.querySelector('.code-header .lang');
      if (!pre) return;
      var lang = (langEl ? langEl.textContent : '').trim().toLowerCase();
      pre.innerHTML = highlight(pre.textContent, lang);
    });
  });
})();

// ════════════════════════════════════════════
// Toast sederhana
// ════════════════════════════════════════════
function showToast(msg) {
  var t = document.getElementById('toast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'toast';
    t.className = 'toast';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(window.__toastTimer);
  window.__toastTimer = setTimeout(function () { t.classList.remove('show'); }, 2200);
}

// ════════════════════════════════════════════
// Share tutorial (Web Share API + fallback salin link)
// ════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function () {
  var btn = document.getElementById('share-btn');
  if (!btn) return;
  btn.addEventListener('click', function () {
    var data = {
      title: btn.getAttribute('data-title') || document.title,
      text: btn.getAttribute('data-text') || '',
      url: window.location.href
    };
    if (navigator.share) {
      navigator.share(data).catch(function () {});
    } else if (navigator.clipboard) {
      navigator.clipboard.writeText(data.url).then(function () {
        showToast('Link tutorial disalin!');
      }).catch(function () { showToast(data.url); });
    } else {
      showToast(data.url);
    }
  });
});
