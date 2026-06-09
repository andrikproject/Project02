<?php
require_once __DIR__ . '/config.php';

/**
 * Mengembalikan koneksi PDO ke SQLite (singleton).
 * Otomatis membuat tabel + seed data pada pertama kali dijalankan.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $firstRun = !file_exists(DB_PATH);

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    init_schema($pdo);

    if ($firstRun) {
        seed_data($pdo);
    }

    return $pdo;
}

/** Membuat tabel jika belum ada. */
function init_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tutorials (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            title       TEXT NOT NULL,
            slug        TEXT NOT NULL UNIQUE,
            description TEXT NOT NULL DEFAULT '',
            category    TEXT NOT NULL DEFAULT 'Tools',
            icon        TEXT NOT NULL DEFAULT 'BOOK',
            tags        TEXT NOT NULL DEFAULT '',
            created_at  TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at  TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS steps (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            tutorial_id INTEGER NOT NULL,
            position    INTEGER NOT NULL DEFAULT 0,
            title       TEXT NOT NULL,
            body        TEXT NOT NULL DEFAULT '',
            FOREIGN KEY (tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE
        )
    ");
}

/** Membuat satu tutorial beserta langkah-langkahnya. */
function insert_tutorial(PDO $pdo, array $meta, array $steps): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO tutorials (title, slug, description, category, icon, tags) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $meta['title'], $meta['slug'], $meta['description'],
        $meta['category'], $meta['icon'], $meta['tags'],
    ]);
    $tutorialId = (int) $pdo->lastInsertId();

    $sstmt = $pdo->prepare(
        "INSERT INTO steps (tutorial_id, position, title, body) VALUES (?, ?, ?, ?)"
    );
    foreach ($steps as $i => $s) {
        $sstmt->execute([$tutorialId, $i + 1, $s[0], $s[1]]);
    }
}

/** Mengisi data contoh saat database baru dibuat. */
function seed_data(PDO $pdo): void
{
    // ── 1. AI: Ollama (Local LLM) ──
    insert_tutorial($pdo, [
        'title' => 'Jalankan AI Lokal dengan Ollama',
        'slug' => 'jalankan-ai-lokal-ollama',
        'description' => 'Install Ollama untuk menjalankan LLM (Llama, Qwen, DeepSeek) langsung di komputer/VPS tanpa API key.',
        'category' => 'AI', 'icon' => 'FIRE',
        'tags' => 'LLM,Privacy,Offline,OpenAI API',
    ], [
        ['Install Ollama',
            '<p>Ollama membungkus <code>llama.cpp</code> dengan satu perintah install dan CLI sederhana. Jalankan:</p>'
            . code_block('bash', "curl -fsSL https://ollama.com/install.sh | sh")
            . '<div class="tip">Tersedia juga untuk Windows & macOS lewat aplikasi installer di situs resmi.</div>'],
        ['Jalankan Model Pertama',
            '<p>Tarik dan jalankan model hanya dengan satu perintah:</p>'
            . code_block('bash', "ollama run llama3")
            . '<p>Contoh model populer lain:</p>'
            . code_block('bash', "ollama run qwen2.5\nollama run deepseek-r1\nollama run gemma2")],
        ['Akses via API (OpenAI-compatible)',
            '<p>Ollama otomatis menyediakan endpoint lokal di port 11434:</p>'
            . code_block('bash', "curl http://localhost:11434/api/generate -d '{\n  \"model\": \"llama3\",\n  \"prompt\": \"Halo, apa kabar?\"\n}'")
            . '<div class="info-box"><div class="info-grid">'
            . '<div><div class="label">Privasi</div><div class="value">100% Lokal</div></div>'
            . '<div><div class="label">Biaya</div><div class="value">Gratis</div></div>'
            . '<div><div class="label">Port</div><div class="value">11434</div></div>'
            . '</div></div>'],
    ]);

    // ── 2. AI: Open WebUI ──
    insert_tutorial($pdo, [
        'title' => 'Chat UI untuk AI dengan Open WebUI',
        'slug' => 'open-webui-chat-ai',
        'description' => 'Pasang antarmuka chat ala ChatGPT untuk model Ollama Anda menggunakan Docker.',
        'category' => 'AI', 'icon' => 'CLOUD',
        'tags' => 'Docker,Ollama,Web UI,Self-hosted',
    ], [
        ['Jalankan dengan Docker',
            '<div class="warning">Pastikan Docker & Ollama sudah terpasang lebih dulu.</div>'
            . code_block('bash', "docker run -d -p 3000:8080 \\\n  --add-host=host.docker.internal:host-gateway \\\n  -v open-webui:/app/backend/data \\\n  --name open-webui --restart always \\\n  ghcr.io/open-webui/open-webui:main")],
        ['Akses Antarmuka',
            '<p>Buka browser ke alamat berikut lalu buat akun admin pertama:</p>'
            . code_block('text', "http://localhost:3000")
            . '<div class="tip">Akun pertama yang mendaftar otomatis menjadi admin.</div>'],
    ]);

    // ── 3. Termux: Setup Dasar ──
    insert_tutorial($pdo, [
        'title' => 'Setup Awal Termux + Tools Keren',
        'slug' => 'setup-termux-tools-keren',
        'description' => 'Ubah HP Android jadi terminal Linux: update, storage, dan tools wajib (git, python, tmux, neofetch).',
        'category' => 'Termux', 'icon' => 'PHONE',
        'tags' => 'Android,Linux,CLI,Python',
    ], [
        ['Update & Beri Akses Storage',
            code_block('bash', "pkg update && pkg upgrade -y\ntermux-setup-storage")
            . '<div class="tip">Perintah <code>termux-setup-storage</code> membuat folder ~/storage agar bisa akses file HP.</div>'],
        ['Install Tools Wajib',
            '<p>Paket esensial untuk produktivitas:</p>'
            . code_block('bash', "pkg install -y git python nodejs openssh nano wget curl")],
        ['Tools Keren Tambahan',
            '<p>Beberapa tool yang membuat terminal lebih nyaman:</p>'
            . code_block('bash', "pkg install -y tmux neofetch htop fzf ripgrep")
            . '<div class="info-box"><div class="info-grid">'
            . '<div><div class="label">tmux</div><div class="value">Multi-sesi terminal</div></div>'
            . '<div><div class="label">neofetch</div><div class="value">Info sistem keren</div></div>'
            . '<div><div class="label">htop</div><div class="value">Monitor proses</div></div>'
            . '<div><div class="label">fzf</div><div class="value">Fuzzy finder</div></div>'
            . '</div></div>'],
    ]);

    // ── 4. Termux: SSH ke VPS ──
    insert_tutorial($pdo, [
        'title' => 'Remote VPS dari HP via Termux',
        'slug' => 'remote-vps-dari-termux',
        'description' => 'Kelola server VPS langsung dari Android menggunakan SSH di Termux.',
        'category' => 'Termux', 'icon' => 'SERVER',
        'tags' => 'SSH,VPS,Remote',
    ], [
        ['Install OpenSSH',
            code_block('bash', "pkg install -y openssh")],
        ['Koneksi ke VPS',
            code_block('bash', "ssh user@ip-vps-anda")
            . '<div class="tip">Pakai SSH key agar lebih aman: <code>ssh -i ~/.ssh/key user@ip</code></div>'],
        ['Bonus: Generate SSH Key',
            code_block('bash', "ssh-keygen -t ed25519 -C \"hp-saya\"\ncat ~/.ssh/id_ed25519.pub")
            . '<p>Salin isi public key ke VPS pada file <code>~/.ssh/authorized_keys</code>.</p>'],
    ]);

    // ── 5. VPS: Docker + PostgreSQL + Redis ──
    insert_tutorial($pdo, [
        'title' => 'Install Docker + PostgreSQL + Redis',
        'slug' => 'install-docker-postgres-redis',
        'description' => 'Stack backend lengkap di VPS menggunakan Docker dalam hitungan menit.',
        'category' => 'VPS', 'icon' => 'DATABASE',
        'tags' => 'Docker,PostgreSQL,Redis,Ubuntu',
    ], [
        ['Install Docker',
            code_block('bash', "curl -fsSL https://get.docker.com | sh\nsudo usermod -aG docker \$USER")
            . '<div class="warning">Logout lalu login lagi agar grup docker aktif.</div>'],
        ['Jalankan PostgreSQL',
            code_block('bash', "docker run -d --name postgres \\\n  -e POSTGRES_PASSWORD=rahasia \\\n  -p 5432:5432 --restart always \\\n  postgres:16")],
        ['Jalankan Redis',
            code_block('bash', "docker run -d --name redis \\\n  -p 6379:6379 --restart always \\\n  redis:7")
            . '<div class="tip">Cek status: <code>docker ps</code></div>'],
    ]);

    // ── 6. VPS: Netdata Monitoring ──
    insert_tutorial($pdo, [
        'title' => 'Monitoring VPS Real-time dengan Netdata',
        'slug' => 'monitoring-vps-netdata',
        'description' => 'Dashboard monitoring CPU, RAM, disk, dan jaringan dengan granularitas 1 detik. Sekali install langsung jalan.',
        'category' => 'VPS', 'icon' => 'GEAR',
        'tags' => 'Monitoring,Dashboard,Open Source',
    ], [
        ['Install Netdata',
            '<p>Satu perintah, auto-deteksi OS & service:</p>'
            . code_block('bash', "wget -qO- https://my-netdata.io/kickstart.sh | sh")],
        ['Akses Dashboard',
            code_block('text', "http://ip-vps-anda:19999")
            . '<div class="warning">Jangan biarkan port 19999 terbuka ke publik tanpa proteksi. Gunakan reverse proxy + auth.</div>'],
    ]);

    // ── 7. Tools: UFW + Fail2ban ──
    insert_tutorial($pdo, [
        'title' => 'Amankan VPS dengan UFW + Fail2ban',
        'slug' => 'amankan-vps-ufw-fail2ban',
        'description' => 'Firewall default-deny dan proteksi otomatis dari serangan brute force SSH.',
        'category' => 'VPS', 'icon' => 'LOCK',
        'tags' => 'Security,Firewall,SSH',
    ], [
        ['Konfigurasi UFW',
            code_block('bash', "sudo apt install -y ufw\nsudo ufw default deny incoming\nsudo ufw default allow outgoing\nsudo ufw allow ssh\nsudo ufw allow 80/tcp\nsudo ufw allow 443/tcp\nsudo ufw enable")],
        ['Pasang Fail2ban',
            code_block('bash', "sudo apt install -y fail2ban\nsudo systemctl enable --now fail2ban")
            . '<div class="tip">Fail2ban otomatis memblokir IP yang gagal login berkali-kali.</div>'],
        ['Cek Status',
            code_block('bash', "sudo ufw status verbose\nsudo fail2ban-client status sshd")],
    ]);

    // ── 8. AI: Install Hermes Agent ──
    insert_tutorial($pdo, [
        'title' => 'Install Hermes Agent (Nous Research)',
        'slug' => 'install-hermes-agent',
        'description' => 'AI agent open-source (MIT) yang berjalan di infrastruktur sendiri, belajar dari setiap tugas, dan menyimpan memori antar sesi.',
        'category' => 'AI', 'icon' => 'ROCKET',
        'tags' => 'Agent,Self-hosted,Open Source,VPS',
    ], [
        ['Install (Linux / macOS)',
            '<p>Hermes Agent dapat berjalan di laptop, VPS, Docker, maupun serverless. Install dengan satu perintah:</p>'
            . code_block('bash', "curl -fsSL https://hermes-agent.nousresearch.com/install.sh | bash")
            . '<div class="tip">Windows: jalankan di PowerShell <code>iex (irm https://hermes-agent.nousresearch.com/install.ps1)</code> — disarankan via WSL2.</div>'],
        ['Setup via Portal',
            '<p>Satu kali OAuth mencakup model + 4 tool gateway (web search, image generation, TTS, browser):</p>'
            . code_block('bash', "hermes setup --portal")
            . '<div class="warning">Restart terminal setelah install agar PATH mengenali perintah <code>hermes</code>.</div>'],
        ['Mulai Chat Pertama',
            '<p>Aturan praktis: pastikan satu percakapan chat berjalan bersih dulu sebelum menambah fitur lain (gateway, cron, skills, voice).</p>'
            . code_block('bash', "hermes")],
        ['Opsional: Pakai Model Lokal (Ollama)',
            '<p>Secara default Hermes terhubung ke provider cloud (Anthropic/OpenAI). Anda bisa menggantinya dengan Ollama agar 100% lokal & tanpa biaya per-pesan.</p>'
            . code_block('bash', "ollama run llama3\n# arahkan Hermes ke endpoint OpenAI-compatible: http://localhost:11434/v1")
            . '<div class="tip">Cocok untuk privasi penuh dan tanpa rate limit.</div>'],
    ]);

    // ── 9. AI: OpenRouter (multi-model API) ──
    insert_tutorial($pdo, [
        'title' => 'Akses 400+ Model AI via OpenRouter',
        'slug' => 'openrouter-multi-model-api',
        'description' => 'Satu API key untuk menjangkau ratusan model (OpenAI, Anthropic, Google, Meta) lewat satu endpoint.',
        'category' => 'AI', 'icon' => 'CLOUD',
        'tags' => 'API,Multi-model,Gateway',
    ], [
        ['Buat API Key',
            '<p>Daftar di <code>openrouter.ai</code>, lalu buka <code>openrouter.ai/keys</code> &rarr; "Create Key". Salin segera karena tidak ditampilkan lagi.</p>'
            . code_block('bash', "export OPENROUTER_API_KEY=\"sk-or-...\"")],
        ['Request Pertama',
            '<p>Endpoint kompatibel dengan format OpenAI — cukup ganti URL & model slug:</p>'
            . code_block('bash', "curl https://openrouter.ai/api/v1/chat/completions \\\n  -H \"Authorization: Bearer \$OPENROUTER_API_KEY\" \\\n  -H \"Content-Type: application/json\" \\\n  -d '{\n    \"model\": \"openai/gpt-4o\",\n    \"messages\": [{\"role\":\"user\",\"content\":\"Halo!\"}]\n  }'")],
        ['Ganti Model Tanpa Ubah Kode',
            '<p>Cukup ganti nilai <code>model</code>. Lihat katalog lengkap di <code>openrouter.ai/models</code>.</p>'
            . code_block('text', "anthropic/claude-3.5-sonnet\ngoogle/gemini-flash-1.5\nmeta-llama/llama-3.1-70b-instruct")
            . '<div class="warning">Harga berbeda antar model — pantau dashboard penggunaan Anda.</div>'],
    ]);

    // ── 10. AI: OpenAI API ──
    insert_tutorial($pdo, [
        'title' => 'Setup OpenAI API (Python & cURL)',
        'slug' => 'setup-openai-api',
        'description' => 'Mulai memakai model GPT lewat API resmi OpenAI: dari API key hingga request pertama.',
        'category' => 'AI', 'icon' => 'CODE',
        'tags' => 'OpenAI,GPT,Python,API',
    ], [
        ['Dapatkan API Key',
            '<p>Buat key di <code>platform.openai.com/api-keys</code>, lalu simpan sebagai environment variable:</p>'
            . code_block('bash', "export OPENAI_API_KEY=\"sk-...\"")],
        ['Uji Key dengan cURL',
            code_block('bash', "curl https://api.openai.com/v1/chat/completions \\\n  -H \"Authorization: Bearer \$OPENAI_API_KEY\" \\\n  -H \"Content-Type: application/json\" \\\n  -d '{\n    \"model\": \"gpt-4o-mini\",\n    \"messages\": [{\"role\":\"user\",\"content\":\"Halo!\"}]\n  }'")],
        ['Pakai di Python',
            code_block('bash', "pip install openai")
            . code_block('python', "from openai import OpenAI\nclient = OpenAI()  # baca OPENAI_API_KEY otomatis\n\nresp = client.chat.completions.create(\n    model=\"gpt-4o-mini\",\n    messages=[{\"role\": \"user\", \"content\": \"Halo!\"}],\n)\nprint(resp.choices[0].message.content)")
            . '<div class="tip">Jangan pernah commit API key ke Git. Gunakan file <code>.env</code> dan tambahkan ke <code>.gitignore</code>.</div>'],
    ]);

    // ── 11. AI: OpenClaw (automation agent) ──
    insert_tutorial($pdo, [
        'title' => 'OpenClaw — Asisten AI Otomasi via Chat',
        'slug' => 'openclaw-automation-agent',
        'description' => 'Agen AI open-source yang mengotomasi tugas lewat WhatsApp/Telegram/Discord dengan dukungan multi-model.',
        'category' => 'AI', 'icon' => 'FIRE',
        'tags' => 'Agent,Automation,Multi-channel,VPS',
    ], [
        ['Apa itu OpenClaw?',
            '<p>OpenClaw adalah gateway AI yang menghubungkan model bahasa ke aplikasi pesan, jadwal berulang, dan memori persisten. Berbeda dari Claude Code (fokus coding di terminal), OpenClaw fokus pada otomasi umum lintas channel.</p>'
            . '<div class="info-box"><div class="info-grid">'
            . '<div><div class="label">Model</div><div class="value">BYOM (bawa API sendiri)</div></div>'
            . '<div><div class="label">Lisensi</div><div class="value">Open Source</div></div>'
            . '<div><div class="label">Channel</div><div class="value">WhatsApp, Telegram, Discord</div></div>'
            . '<div><div class="label">Biaya</div><div class="value">VPS + API saja</div></div>'
            . '</div></div>'],
        ['Install Orchestrator',
            '<p>Plugin orchestrator menambahkan banyak tool ke setiap agen OpenClaw:</p>'
            . code_block('bash', "npm install -g @enderfga/claw-orchestrator")
            . '<p>Atau via skrip install:</p>'
            . code_block('bash', "curl -fsSL https://raw.githubusercontent.com/Enderfga/claw-orchestrator/main/install.sh | bash")],
        ['Catatan Keamanan',
            '<div class="warning">Karena OpenClaw bisa mengeksekusi aksi di sistem & akun pesan Anda, jalankan di user VPS terkunci (least privilege) dan batasi akses pengguna yang diizinkan.</div>'],
    ]);

    // ── 12. AI: Claude Code ──
    insert_tutorial($pdo, [
        'title' => 'Claude Code — Agen Coding di Terminal',
        'slug' => 'claude-code-terminal-agent',
        'description' => 'Agen coding dari Anthropic yang membaca repo, mengedit file, dan menjalankan perintah langsung dari terminal.',
        'category' => 'AI', 'icon' => 'CODE',
        'tags' => 'Coding,CLI,Anthropic',
    ], [
        ['Install via npm',
            code_block('bash', "npm install -g @anthropic-ai/claude-code")
            . '<div class="tip">Butuh Node.js 18+. Cek dengan <code>node --version</code>.</div>'],
        ['Jalankan di Folder Proyek',
            code_block('bash', "cd proyek-anda\nclaude")
            . '<p>Claude Code memahami seluruh struktur repo Anda dan dapat mengedit kode, menjalankan test, serta otomasi review.</p>'],
    ]);
}

/** Helper untuk membuat markup blok kode (dipakai saat seed). */
function code_block(string $lang, string $code): string
{
    return '<div class="code-block">'
        . '<div class="code-header"><span class="lang">' . htmlspecialchars($lang) . '</span>'
        . '<button class="copy-btn" type="button">Salin</button></div>'
        . '<pre>' . htmlspecialchars($code) . '</pre>'
        . '</div>';
}
