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

    // ── 13. AI: n8n (workflow automation) ──
    insert_tutorial($pdo, [
        'title' => 'Otomasi Workflow AI dengan n8n',
        'slug' => 'otomasi-workflow-n8n',
        'description' => 'Platform automation open-source: hubungkan ratusan app & API (termasuk LLM) lewat editor visual, self-hosted.',
        'category' => 'AI', 'icon' => 'GEAR',
        'tags' => 'Automation,Self-hosted,No-code,Docker',
    ], [
        ['Jalankan Cepat dengan Docker',
            code_block('bash', "docker run -it --rm \\\n  --name n8n -p 5678:5678 \\\n  -v n8n_data:/home/node/.n8n \\\n  docker.n8n.io/n8nio/n8n")
            . '<div class="tip">Alternatif tanpa Docker: <code>npx n8n</code> (butuh Node.js 18+).</div>'],
        ['Akses Editor',
            code_block('text', "http://localhost:5678")
            . '<p>Buat akun owner pertama, lalu mulai membangun workflow dengan drag-and-drop node.</p>'],
        ['Spesifikasi Minimum',
            '<div class="info-box"><div class="info-grid">'
            . '<div><div class="label">CPU</div><div class="value">2 core</div></div>'
            . '<div><div class="label">RAM</div><div class="value">2 GB</div></div>'
            . '<div><div class="label">Disk</div><div class="value">20 GB</div></div>'
            . '<div><div class="label">Port</div><div class="value">5678</div></div>'
            . '</div></div>'
            . '<div class="warning">Untuk produksi gunakan PostgreSQL + reverse proxy (Caddy/Nginx) + SSL.</div>'],
    ]);

    // ── 14. AI: Flowise (low-code LLM builder) ──
    insert_tutorial($pdo, [
        'title' => 'Bangun Chatbot AI Visual dengan Flowise',
        'slug' => 'flowise-chatbot-visual',
        'description' => 'Builder drag-and-drop untuk aplikasi LLM, agen AI, dan RAG. Dibangun di atas LangChain & LlamaIndex.',
        'category' => 'AI', 'icon' => 'CLOUD',
        'tags' => 'Low-code,RAG,Chatbot,LangChain',
    ], [
        ['Install via NPM',
            '<div class="warning">Butuh Node.js v18.15.0 / v20 ke atas.</div>'
            . code_block('bash', "npm install -g flowise\nnpx flowise start")],
        ['Alternatif: Docker',
            code_block('bash', "docker run -d --name flowise \\\n  -p 3000:3000 \\\n  flowiseai/flowise")],
        ['Akses Dashboard',
            code_block('text', "http://localhost:3000")
            . '<p>Hubungkan model (OpenAI/Ollama), susun chatflow, uji chatbot, lalu embed ke website atau pakai via API.</p>'
            . '<div class="tip">Aktifkan auth dengan env <code>FLOWISE_USERNAME</code> & <code>FLOWISE_PASSWORD</code>.</div>'],
    ]);

    // ── 15. AI: LangChain (framework) ──
    insert_tutorial($pdo, [
        'title' => 'Mulai dengan LangChain (Python)',
        'slug' => 'mulai-langchain-python',
        'description' => 'Framework untuk membangun aplikasi LLM: chain, agen, RAG, dan integrasi banyak provider model.',
        'category' => 'AI', 'icon' => 'CODE',
        'tags' => 'Framework,Python,LLM,RAG',
    ], [
        ['Install',
            '<p>Disarankan pakai virtual environment agar dependency tidak bentrok. Butuh Python 3.9+.</p>'
            . code_block('bash', "python -m venv venv\nsource venv/bin/activate\npip install langchain langchain-openai python-dotenv")],
        ['Contoh Chain Sederhana',
            code_block('python', "import os\nfrom langchain_openai import ChatOpenAI\n\nos.environ[\"OPENAI_API_KEY\"] = \"sk-...\"\n\nllm = ChatOpenAI(model=\"gpt-4o-mini\")\nresp = llm.invoke(\"Jelaskan apa itu LangChain singkat.\")\nprint(resp.content)")
            . '<div class="tip">Simpan API key di file <code>.env</code>, jangan hardcode di kode produksi.</div>'],
    ]);

    // ── 16. AI: ComfyUI (Stable Diffusion) ──
    insert_tutorial($pdo, [
        'title' => 'Generate Gambar AI dengan ComfyUI',
        'slug' => 'generate-gambar-comfyui',
        'description' => 'Antarmuka berbasis node untuk Stable Diffusion: kontrol penuh atas workflow generasi gambar.',
        'category' => 'AI', 'icon' => 'FIRE',
        'tags' => 'Stable Diffusion,Image,GPU,Node-based',
    ], [
        ['Prasyarat',
            '<div class="info-box"><div class="info-grid">'
            . '<div><div class="label">GPU</div><div class="value">NVIDIA 4GB+ VRAM</div></div>'
            . '<div><div class="label">Python</div><div class="value">3.10 / 3.11</div></div>'
            . '<div><div class="label">Disk</div><div class="value">20-30 GB</div></div>'
            . '<div><div class="label">Lain</div><div class="value">Git, CUDA 11.8+</div></div>'
            . '</div></div>'
            . '<div class="warning">Python 3.12 dapat bermasalah dengan beberapa extension. Gunakan 3.10/3.11.</div>'],
        ['Clone & Install',
            code_block('bash', "git clone https://github.com/comfyanonymous/ComfyUI\ncd ComfyUI\npython -m venv venv\nsource venv/bin/activate\npip install -r requirements.txt")],
        ['Jalankan',
            code_block('bash', "python main.py")
            . '<p>Buka antarmuka di browser:</p>'
            . code_block('text', "http://localhost:8188")
            . '<div class="tip">Letakkan file model checkpoint (.safetensors) di folder <code>models/checkpoints/</code>.</div>'],
    ]);

    // ── 17. AI: AnythingLLM (RAG / Chat dokumen) ──
    insert_tutorial($pdo, [
        'title' => 'Chat dengan Dokumen via AnythingLLM',
        'slug' => 'chat-dokumen-anythingllm',
        'description' => 'Aplikasi all-in-one untuk RAG: ngobrol dengan PDF/dokumen Anda menggunakan LLM lokal maupun cloud.',
        'category' => 'AI', 'icon' => 'BOOK',
        'tags' => 'RAG,Dokumen,Self-hosted,Docker',
    ], [
        ['Jalankan dengan Docker',
            code_block('bash', "docker run -d --name anythingllm \\\n  -p 3001:3001 \\\n  -v anythingllm_storage:/app/server/storage \\\n  mintplexlabs/anythingllm")],
        ['Konfigurasi & Pakai',
            code_block('text', "http://localhost:3001")
            . '<p>Pilih provider LLM (OpenAI, Ollama, dll), buat workspace, unggah dokumen, lalu mulai bertanya berdasarkan isi dokumen tersebut.</p>'],
    ]);

    // ── 18. AI: Whisper (speech-to-text) ──
    insert_tutorial($pdo, [
        'title' => 'Transkrip Audio dengan OpenAI Whisper',
        'slug' => 'transkrip-audio-whisper',
        'description' => 'Ubah suara menjadi teks secara lokal dan gratis dengan model speech-to-text Whisper.',
        'category' => 'AI', 'icon' => 'PHONE',
        'tags' => 'Speech-to-text,Audio,Python,Offline',
    ], [
        ['Install',
            '<p>Butuh Python & ffmpeg terpasang.</p>'
            . code_block('bash', "sudo apt install -y ffmpeg\npip install -U openai-whisper")],
        ['Transkrip File Audio',
            code_block('bash', "whisper rekaman.mp3 --model small --language Indonesian")
            . '<div class="tip">Pilihan model: tiny, base, small, medium, large. Makin besar = makin akurat tapi lebih berat.</div>'],
    ]);

    // ── 19. AI: Stable Diffusion WebUI (Automatic1111) ──
    insert_tutorial($pdo, [
        'title' => 'Stable Diffusion WebUI (Automatic1111)',
        'slug' => 'stable-diffusion-webui-automatic1111',
        'description' => 'GUI paling populer untuk generasi gambar Stable Diffusion, dengan ekosistem extension terbesar.',
        'category' => 'AI', 'icon' => 'FIRE',
        'tags' => 'Stable Diffusion,Image,GPU,WebUI',
    ], [
        ['Prasyarat',
            '<div class="info-box"><div class="info-grid">'
            . '<div><div class="label">GPU</div><div class="value">NVIDIA 4GB+ (8GB disarankan)</div></div>'
            . '<div><div class="label">Python</div><div class="value">3.10</div></div>'
            . '<div><div class="label">Disk</div><div class="value">10-30 GB</div></div>'
            . '<div><div class="label">Lain</div><div class="value">Git</div></div>'
            . '</div></div>'],
        ['Clone Repository',
            code_block('bash', "git clone https://github.com/AUTOMATIC1111/stable-diffusion-webui\ncd stable-diffusion-webui")],
        ['Jalankan',
            '<p>Linux/macOS:</p>'
            . code_block('bash', "./webui.sh")
            . '<p>Windows: jalankan <code>webui-user.bat</code>. Skrip akan otomatis menyiapkan environment & dependency.</p>'
            . code_block('text', "http://localhost:7860")
            . '<div class="tip">Letakkan model checkpoint di <code>models/Stable-diffusion/</code>.</div>'],
    ]);

    // ── 20. AI: LocalAI ──
    insert_tutorial($pdo, [
        'title' => 'LocalAI — API OpenAI-Compatible Lokal',
        'slug' => 'localai-openai-compatible',
        'description' => 'Pengganti drop-in OpenAI API yang berjalan di hardware sendiri (CPU/GPU), mendukung teks, gambar, dan audio.',
        'category' => 'AI', 'icon' => 'SERVER',
        'tags' => 'API,Self-hosted,OpenAI,Docker',
    ], [
        ['Jalankan dengan Docker (CPU)',
            code_block('bash', "docker run -d --name localai \\\n  -p 8080:8080 \\\n  localai/localai:latest-aio-cpu")
            . '<div class="tip">Versi "aio" sudah berisi paket model siap pakai.</div>'],
        ['Uji Endpoint',
            '<p>Kompatibel penuh dengan format OpenAI:</p>'
            . code_block('bash', "curl http://localhost:8080/v1/chat/completions \\\n  -H \"Content-Type: application/json\" \\\n  -d '{\n    \"model\": \"gpt-4\",\n    \"messages\": [{\"role\":\"user\",\"content\":\"Halo!\"}]\n  }'")
            . '<div class="warning">Untuk performa lebih tinggi gunakan image GPU (NVIDIA/AMD) yang sesuai.</div>'],
    ]);

    // ── 21. AI: Dify ──
    insert_tutorial($pdo, [
        'title' => 'Dify — Platform Aplikasi LLM Self-Hosted',
        'slug' => 'dify-platform-llm',
        'description' => 'Builder visual untuk workflow AI, RAG bawaan, manajemen prompt, dan generasi API — di balik UI web bersih.',
        'category' => 'AI', 'icon' => 'CLOUD',
        'tags' => 'RAG,Workflow,Self-hosted,Docker Compose',
    ], [
        ['Prasyarat',
            '<div class="warning">Alokasikan minimal 2 vCPU dan 8 GB RAM untuk Docker, jika tidak instalasi bisa gagal.</div>'],
        ['Deploy dengan Docker Compose',
            code_block('bash', "git clone https://github.com/langgenius/dify.git\ncd dify/docker\ncp .env.example .env\ndocker compose up -d")],
        ['Akses & Setup',
            '<p>Buka di browser lalu buat akun admin awal:</p>'
            . code_block('text', "http://localhost")
            . '<p>Setelah masuk, konfigurasikan model system (OpenAI/Ollama/dll), lalu mulai membangun aplikasi AI.</p>'],
    ]);

    // ── 22. AI: Open Interpreter ──
    insert_tutorial($pdo, [
        'title' => 'Open Interpreter — Jalankan Kode via Chat',
        'slug' => 'open-interpreter-jalankan-kode',
        'description' => 'Biarkan LLM menjalankan kode (Python, JavaScript, Shell) di komputer Anda lewat antarmuka chat di terminal.',
        'category' => 'AI', 'icon' => 'CODE',
        'tags' => 'Agent,Python,CLI,Otomasi',
    ], [
        ['Install',
            '<p>Disarankan dalam virtual environment. Butuh Python 3.10 / 3.11.</p>'
            . code_block('bash', "pip install open-interpreter")],
        ['Jalankan',
            code_block('bash', "interpreter")
            . '<p>Ngobrol seperti ChatGPT, tetapi ia bisa benar-benar mengeksekusi kode di mesin Anda.</p>'
            . '<div class="warning">Selalu tinjau kode sebelum dijalankan — ia memiliki akses ke sistem Anda.</div>'],
        ['Pakai Model Lokal',
            code_block('bash', "interpreter --local")
            . '<div class="tip">Mode lokal dapat dihubungkan ke Ollama untuk privasi penuh.</div>'],
    ]);

    // ── 23. AI: vLLM ──
    insert_tutorial($pdo, [
        'title' => 'vLLM — Serving LLM Throughput Tinggi',
        'slug' => 'vllm-serving-llm',
        'description' => 'Engine inferensi berkinerja tinggi (PagedAttention) untuk melayani LLM open-weight dalam skala produksi.',
        'category' => 'AI', 'icon' => 'ROCKET',
        'tags' => 'Inference,GPU,Production,OpenAI API',
    ], [
        ['Prasyarat',
            '<div class="info-box"><div class="info-grid">'
            . '<div><div class="label">GPU</div><div class="value">NVIDIA + CUDA 12.1</div></div>'
            . '<div><div class="label">Python</div><div class="value">3.10+</div></div>'
            . '<div><div class="label">Throughput</div><div class="value">5-20x Ollama</div></div>'
            . '<div><div class="label">API</div><div class="value">OpenAI-compatible</div></div>'
            . '</div></div>'],
        ['Install',
            code_block('bash', "pip install vllm")],
        ['Serve Model (OpenAI-compatible)',
            code_block('bash', "vllm serve meta-llama/Llama-3.1-8B-Instruct")
            . '<p>Server berjalan di port 8000 dengan format OpenAI. Uji:</p>'
            . code_block('bash', "curl http://localhost:8000/v1/chat/completions \\\n  -H \"Content-Type: application/json\" \\\n  -d '{\n    \"model\": \"meta-llama/Llama-3.1-8B-Instruct\",\n    \"messages\": [{\"role\":\"user\",\"content\":\"Halo!\"}]\n  }'")],
    ]);

    // ── 24. AI: Jan ──
    insert_tutorial($pdo, [
        'title' => 'Jan — Aplikasi AI Offline Privasi-First',
        'slug' => 'jan-ai-offline',
        'description' => 'Aplikasi desktop open-source untuk menjalankan LLM 100% offline, alternatif ChatGPT yang privat.',
        'category' => 'AI', 'icon' => 'LOCK',
        'tags' => 'Desktop,Offline,Privacy,GUI',
    ], [
        ['Unduh & Pasang',
            '<p>Jan tersedia untuk Windows, macOS, dan Linux. Unduh installer dari situs resmi:</p>'
            . code_block('text', "https://jan.ai")
            . '<div class="tip">Tidak perlu terminal — cukup pasang seperti aplikasi biasa.</div>'],
        ['Mulai Pakai',
            '<p>Buka Jan, unduh model dari Hub bawaan (mis. Llama, Mistral), lalu mulai chat sepenuhnya offline. Jan juga menyediakan API lokal kompatibel-OpenAI untuk aplikasi Anda.</p>'],
    ]);

    // ── 25. AI: LM Studio ──
    insert_tutorial($pdo, [
        'title' => 'LM Studio — GUI Mudah untuk LLM Lokal',
        'slug' => 'lm-studio-gui-llm',
        'description' => 'Jalankan LLM lokal tanpa perintah terminal: cari, unduh, dan chat dengan UI yang rapi + API lokal.',
        'category' => 'AI', 'icon' => 'BOOK',
        'tags' => 'Desktop,GUI,Offline,No-CLI',
    ], [
        ['Unduh Aplikasi',
            '<p>Tersedia untuk Windows, macOS (Apple Silicon), dan Linux:</p>'
            . code_block('text', "https://lmstudio.ai")],
        ['Pilih & Jalankan Model',
            '<p>Cari model di tab Discover, klik unduh, lalu klik Run. Anda langsung bisa chat dengan UI yang polished — tanpa satu pun perintah terminal.</p>'
            . '<div class="tip">LM Studio juga bisa menjadi server API lokal (OpenAI-compatible) untuk dipakai aplikasi lain.</div>'],
    ]);

    // ── 26. Web: Deploy Website ke VPS (Nginx) ──
    insert_tutorial($pdo, [
        'title' => 'Deploy Website ke VPS dengan Nginx',
        'slug' => 'deploy-website-vps-nginx',
        'description' => 'Host website statis/PHP di VPS sendiri menggunakan Nginx, lengkap dengan struktur folder yang benar.',
        'category' => 'Web', 'icon' => 'CLOUD',
        'tags' => 'Nginx,Deploy,Ubuntu,Hosting',
    ], [
        ['Install Nginx',
            code_block('bash', "sudo apt update\nsudo apt install -y nginx\nsudo systemctl enable --now nginx")
            . '<div class="tip">Cek di browser: <code>http://ip-vps-anda</code> akan menampilkan halaman default Nginx.</div>'],
        ['Upload File Website',
            '<p>Letakkan file website Anda di folder web root:</p>'
            . code_block('bash', "sudo mkdir -p /var/www/situs-saya\nsudo chown -R \$USER:\$USER /var/www/situs-saya\n# salin file: scp -r ./dist/* user@ip:/var/www/situs-saya/")],
        ['Buat Server Block',
            code_block('bash', "sudo nano /etc/nginx/sites-available/situs-saya")
            . code_block('nginx', "server {\n    listen 80;\n    server_name domainanda.com;\n    root /var/www/situs-saya;\n    index index.html index.php;\n\n    location / {\n        try_files \$uri \$uri/ =404;\n    }\n}")
            . code_block('bash', "sudo ln -s /etc/nginx/sites-available/situs-saya /etc/nginx/sites-enabled/\nsudo nginx -t && sudo systemctl reload nginx")],
    ]);

    // ── 27. Web: HTTPS gratis dengan Caddy ──
    insert_tutorial($pdo, [
        'title' => 'Website HTTPS Otomatis dengan Caddy',
        'slug' => 'website-https-caddy',
        'description' => 'Reverse proxy + sertifikat SSL otomatis (Let\'s Encrypt) hanya dengan beberapa baris konfigurasi.',
        'category' => 'Web', 'icon' => 'LOCK',
        'tags' => 'Caddy,SSL,HTTPS,Reverse Proxy',
    ], [
        ['Install Caddy',
            code_block('bash', "sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https curl\ncurl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg\ncurl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list\nsudo apt update && sudo apt install -y caddy")],
        ['Konfigurasi Caddyfile',
            code_block('bash', "sudo nano /etc/caddy/Caddyfile")
            . code_block('text', "domainanda.com {\n    root * /var/www/situs-saya\n    file_server\n}")
            . '<div class="tip">Caddy otomatis mengurus sertifikat HTTPS — tidak perlu setup manual!</div>'],
        ['Reload',
            code_block('bash', "sudo systemctl reload caddy")],
    ]);

    // ── 28. Bot: Telegram ──
    insert_tutorial($pdo, [
        'title' => 'Buat Bot Telegram (Python)',
        'slug' => 'buat-bot-telegram-python',
        'description' => 'Dari membuat token via BotFather hingga bot pertama yang membalas pesan dengan python-telegram-bot.',
        'category' => 'Bot', 'icon' => 'PHONE',
        'tags' => 'Telegram,Python,BotFather',
    ], [
        ['Dapatkan Token dari BotFather',
            '<p>Buka Telegram, cari <code>@BotFather</code>, kirim <code>/newbot</code>, ikuti instruksi, lalu salin token yang diberikan.</p>'
            . '<div class="warning">Jangan bagikan token bot Anda ke publik.</div>'],
        ['Install Library',
            code_block('bash', "pip install python-telegram-bot")],
        ['Kode Bot Pertama',
            code_block('python', "from telegram import Update\nfrom telegram.ext import Application, CommandHandler, ContextTypes\n\nasync def start(update: Update, context: ContextTypes.DEFAULT_TYPE):\n    await update.message.reply_text(\"Halo! Bot aktif 🚀\")\n\napp = Application.builder().token(\"TOKEN_ANDA\").build()\napp.add_handler(CommandHandler(\"start\", start))\napp.run_polling()")
            . '<p>Jalankan, lalu kirim <code>/start</code> ke bot Anda di Telegram.</p>'],
    ]);

    // ── 29. Bot: WhatsApp ──
    insert_tutorial($pdo, [
        'title' => 'Buat Bot WhatsApp dengan Baileys',
        'slug' => 'buat-bot-whatsapp-baileys',
        'description' => 'Bot WhatsApp berbasis Node.js menggunakan Baileys — login via QR code, tanpa browser.',
        'category' => 'Bot', 'icon' => 'PHONE',
        'tags' => 'WhatsApp,Node.js,Baileys',
    ], [
        ['Siapkan Proyek',
            code_block('bash', "mkdir wa-bot && cd wa-bot\nnpm init -y\nnpm install @whiskeysockets/baileys qrcode-terminal")],
        ['Kode Dasar',
            code_block('javascript', "const { default: makeWASocket, useMultiFileAuthState } = require('@whiskeysockets/baileys')\nconst qrcode = require('qrcode-terminal')\n\nasync function start() {\n  const { state, saveCreds } = await useMultiFileAuthState('auth')\n  const sock = makeWASocket({ auth: state })\n\n  sock.ev.on('connection.update', ({ qr }) => { if (qr) qrcode.generate(qr, { small: true }) })\n  sock.ev.on('creds.update', saveCreds)\n\n  sock.ev.on('messages.upsert', async ({ messages }) => {\n    const m = messages[0]\n    if (!m.message || m.key.fromMe) return\n    await sock.sendMessage(m.key.remoteJid, { text: 'Halo dari bot! 🤖' })\n  })\n}\nstart()")
            . '<div class="warning">Scan QR code yang muncul di terminal memakai WhatsApp di HP (Perangkat Tertaut).</div>'],
    ]);

    // ── 30. Database: MySQL/MariaDB ──
    insert_tutorial($pdo, [
        'title' => 'Setup MySQL / MariaDB di VPS',
        'slug' => 'setup-mysql-mariadb-vps',
        'description' => 'Install database MySQL/MariaDB, amankan instalasi, dan buat database + user pertama Anda.',
        'category' => 'Database', 'icon' => 'DATABASE',
        'tags' => 'MySQL,MariaDB,SQL,Ubuntu',
    ], [
        ['Install',
            code_block('bash', "sudo apt update\nsudo apt install -y mariadb-server\nsudo systemctl enable --now mariadb")],
        ['Amankan Instalasi',
            code_block('bash', "sudo mysql_secure_installation")
            . '<div class="tip">Set password root, hapus user anonim, dan nonaktifkan login root jarak jauh.</div>'],
        ['Buat Database & User',
            code_block('sql', "CREATE DATABASE aplikasi;\nCREATE USER 'appuser'@'localhost' IDENTIFIED BY 'password_kuat';\nGRANT ALL PRIVILEGES ON aplikasi.* TO 'appuser'@'localhost';\nFLUSH PRIVILEGES;")],
    ]);

    // ── 31. Database: MongoDB ──
    insert_tutorial($pdo, [
        'title' => 'Jalankan MongoDB dengan Docker',
        'slug' => 'jalankan-mongodb-docker',
        'description' => 'Database NoSQL populer untuk aplikasi modern, dijalankan cepat lewat Docker.',
        'category' => 'Database', 'icon' => 'DATABASE',
        'tags' => 'MongoDB,NoSQL,Docker',
    ], [
        ['Jalankan Container',
            code_block('bash', "docker run -d --name mongodb \\\n  -p 27017:27017 \\\n  -e MONGO_INITDB_ROOT_USERNAME=admin \\\n  -e MONGO_INITDB_ROOT_PASSWORD=rahasia \\\n  -v mongo_data:/data/db \\\n  --restart always \\\n  mongo:7")],
        ['Koneksi',
            code_block('bash', "docker exec -it mongodb mongosh -u admin -p rahasia")
            . '<div class="tip">String koneksi aplikasi: <code>mongodb://admin:rahasia@localhost:27017</code></div>'],
    ]);

    // ── 32. Git: Dasar Git & GitHub ──
    insert_tutorial($pdo, [
        'title' => 'Dasar Git & Push ke GitHub',
        'slug' => 'dasar-git-github',
        'description' => 'Dari init repo, commit pertama, hingga push ke GitHub. Wajib untuk semua developer.',
        'category' => 'Git', 'icon' => 'CODE',
        'tags' => 'Git,GitHub,Version Control',
    ], [
        ['Konfigurasi Awal',
            code_block('bash', "git config --global user.name \"Nama Anda\"\ngit config --global user.email \"email@anda.com\"")],
        ['Init & Commit Pertama',
            code_block('bash', "git init\ngit add .\ngit commit -m \"Commit pertama\"")],
        ['Hubungkan ke GitHub',
            '<p>Buat repo kosong di GitHub, lalu hubungkan dan push:</p>'
            . code_block('bash', "git remote add origin https://github.com/username/repo.git\ngit branch -M main\ngit push -u origin main")
            . '<div class="tip">Gunakan Personal Access Token sebagai password saat diminta (GitHub tidak lagi menerima password akun).</div>'],
    ]);

    // ── 33. Git: SSH Key untuk GitHub ──
    insert_tutorial($pdo, [
        'title' => 'Setup SSH Key untuk GitHub',
        'slug' => 'setup-ssh-key-github',
        'description' => 'Push & pull tanpa memasukkan password berulang dengan autentikasi SSH key.',
        'category' => 'Git', 'icon' => 'LOCK',
        'tags' => 'Git,SSH,GitHub,Security',
    ], [
        ['Generate SSH Key',
            code_block('bash', "ssh-keygen -t ed25519 -C \"email@anda.com\"\ncat ~/.ssh/id_ed25519.pub")],
        ['Tambahkan ke GitHub',
            '<p>Salin isi public key, lalu di GitHub buka <strong>Settings &rarr; SSH and GPG keys &rarr; New SSH key</strong> dan tempel.</p>'],
        ['Uji Koneksi',
            code_block('bash', "ssh -T git@github.com")
            . '<div class="tip">Setelah ini, gunakan URL remote SSH: <code>git@github.com:username/repo.git</code></div>'],
    ]);

    // ── 34. Web: Cloudflare Tunnel ──
    insert_tutorial($pdo, [
        'title' => 'Ekspos Lokal ke Internet (Cloudflare Tunnel)',
        'slug' => 'cloudflare-tunnel-ekspos-lokal',
        'description' => 'Akses aplikasi di komputer/VPS dari internet lewat domain Cloudflare, tanpa buka port atau IP publik.',
        'category' => 'Web', 'icon' => 'CLOUD',
        'tags' => 'Cloudflare,Tunnel,Tanpa Port,HTTPS',
    ], [
        ['Install cloudflared',
            code_block('bash', "curl -L https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 -o cloudflared\nsudo install cloudflared /usr/local/bin/")],
        ['Tunnel Cepat (uji coba)',
            '<p>Membuat URL publik acak untuk aplikasi lokal di port 8000:</p>'
            . code_block('bash', "cloudflared tunnel --url http://localhost:8000")
            . '<div class="tip">Cocok untuk demo cepat. Untuk permanen, gunakan named tunnel + domain Anda.</div>'],
    ]);

    // ── 35. VPS: Portainer (Docker GUI) ──
    insert_tutorial($pdo, [
        'title' => 'Kelola Docker via GUI dengan Portainer',
        'slug' => 'kelola-docker-portainer',
        'description' => 'Dashboard web untuk mengelola container, image, volume, dan network Docker tanpa hafal perintah.',
        'category' => 'VPS', 'icon' => 'GEAR',
        'tags' => 'Docker,GUI,Management',
    ], [
        ['Jalankan Portainer',
            code_block('bash', "docker volume create portainer_data\ndocker run -d -p 9443:9443 --name portainer --restart always \\\n  -v /var/run/docker.sock:/var/run/docker.sock \\\n  -v portainer_data:/data \\\n  portainer/portainer-ce:latest")],
        ['Akses & Buat Admin',
            code_block('text', "https://ip-vps-anda:9443")
            . '<div class="tip">Buat akun admin pada akses pertama (jangan tunda terlalu lama demi keamanan).</div>'],
    ]);

    // ── 36. VPS: Uptime Kuma ──
    insert_tutorial($pdo, [
        'title' => 'Monitoring Uptime dengan Uptime Kuma',
        'slug' => 'monitoring-uptime-kuma',
        'description' => 'Pantau status website/server Anda dan dapatkan notifikasi saat down. Self-hosted & cantik.',
        'category' => 'VPS', 'icon' => 'GEAR',
        'tags' => 'Monitoring,Uptime,Notifikasi',
    ], [
        ['Jalankan dengan Docker',
            code_block('bash', "docker run -d --name uptime-kuma --restart always \\\n  -p 3001:3001 \\\n  -v uptime-kuma:/app/data \\\n  louislam/uptime-kuma:1")],
        ['Akses Dashboard',
            code_block('text', "http://ip-vps-anda:3001")
            . '<p>Tambahkan monitor (HTTP, TCP, ping), atur interval, dan hubungkan notifikasi (Telegram, Discord, email).</p>'],
    ]);

    // ── 37. Tools: yt-dlp ──
    insert_tutorial($pdo, [
        'title' => 'Download Video dengan yt-dlp',
        'slug' => 'download-video-yt-dlp',
        'description' => 'Unduh video/audio dari berbagai situs lewat command line. Penerus youtube-dl yang aktif dikembangkan.',
        'category' => 'Tools', 'icon' => 'WRENCH',
        'tags' => 'Download,Video,CLI,FFmpeg',
    ], [
        ['Install',
            code_block('bash', "sudo apt install -y ffmpeg\npip install -U yt-dlp")
            . '<div class="tip">ffmpeg dibutuhkan untuk menggabungkan video+audio kualitas tinggi.</div>'],
        ['Contoh Penggunaan',
            code_block('bash', "# Unduh video terbaik\nyt-dlp \"https://situs.com/video\"\n\n# Hanya audio (mp3)\nyt-dlp -x --audio-format mp3 \"https://situs.com/video\"")
            . '<div class="warning">Hanya unduh konten yang Anda miliki haknya atau yang diizinkan untuk diunduh.</div>'],
    ]);

    // ── 38. Tools: FFmpeg ──
    insert_tutorial($pdo, [
        'title' => 'Olah Video & Audio dengan FFmpeg',
        'slug' => 'olah-media-ffmpeg',
        'description' => 'Konversi, potong, kompres, dan ubah format media dari command line — tool serbaguna untuk multimedia.',
        'category' => 'Tools', 'icon' => 'WRENCH',
        'tags' => 'Video,Audio,Konversi,CLI',
    ], [
        ['Install',
            code_block('bash', "sudo apt install -y ffmpeg\nffmpeg -version")],
        ['Perintah Berguna',
            code_block('bash', "# Konversi MP4 ke MP3\nffmpeg -i input.mp4 output.mp3\n\n# Kompres video\nffmpeg -i input.mp4 -vcodec libx264 -crf 28 output.mp4\n\n# Potong (mulai 00:10 selama 30 detik)\nffmpeg -ss 00:00:10 -i input.mp4 -t 30 -c copy potong.mp4")],
    ]);

    // ── 39. Web: PM2 (Node process manager) ──
    insert_tutorial($pdo, [
        'title' => 'Jalankan App Node.js 24/7 dengan PM2',
        'slug' => 'nodejs-pm2-production',
        'description' => 'Process manager untuk menjaga aplikasi Node.js tetap hidup, auto-restart, dan jalan otomatis saat boot.',
        'category' => 'Web', 'icon' => 'ROCKET',
        'tags' => 'Node.js,PM2,Production,Deploy',
    ], [
        ['Install PM2',
            code_block('bash', "npm install -g pm2")],
        ['Jalankan & Kelola App',
            code_block('bash', "pm2 start app.js --name aplikasi\npm2 list\npm2 logs aplikasi\npm2 restart aplikasi")],
        ['Auto-start saat Boot',
            code_block('bash', "pm2 startup\npm2 save")
            . '<div class="tip">Perintah <code>pm2 save</code> menyimpan daftar proses agar dipulihkan setelah reboot.</div>'],
    ]);

    // ── 40. VPS: Cron (penjadwalan tugas) ──
    insert_tutorial($pdo, [
        'title' => 'Jadwalkan Tugas Otomatis dengan Cron',
        'slug' => 'jadwal-tugas-cron',
        'description' => 'Jalankan script/backup secara otomatis pada waktu tertentu menggunakan crontab di Linux.',
        'category' => 'VPS', 'icon' => 'GEAR',
        'tags' => 'Cron,Otomasi,Backup,Linux',
    ], [
        ['Buka Editor Crontab',
            code_block('bash', "crontab -e")],
        ['Contoh Jadwal',
            code_block('bash', "# menit jam tgl bln hari-pekan  perintah\n0 2 * * *   /home/user/backup.sh        # tiap hari pukul 02:00\n*/15 * * * * curl -s https://situs/ping  # tiap 15 menit\n0 0 * * 0   docker system prune -f       # tiap Minggu tengah malam")
            . '<div class="tip">Gunakan <code>crontab -l</code> untuk melihat daftar jadwal aktif.</div>'],
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
