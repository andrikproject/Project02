<?php
require_once __DIR__ . '/db.php';

/** Mulai session sekali saja. */
function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/** Escape HTML untuk output aman. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Base URL aplikasi (folder tempat skrip berjalan), tanpa trailing slash. */
function base_url(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // Jika berada di dalam folder /admin, naik satu level.
    if (basename($dir) === 'admin') {
        $dir = dirname($dir);
    }
    return rtrim($dir, '/');
}

/** Membuat URL relatif terhadap root aplikasi. */
function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

/** Redirect lalu hentikan eksekusi. */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** Apakah admin sudah login? */
function is_logged_in(): bool
{
    start_session();
    return !empty($_SESSION['admin_logged_in']);
}

/** Paksa login untuk halaman admin. */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect(url('admin/login.php'));
    }
}

/** Token CSRF untuk form. */
function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Field input tersembunyi untuk CSRF. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Verifikasi token CSRF, hentikan jika tidak valid. */
function verify_csrf(): void
{
    start_session();
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        die('Token kedaluwarsa atau tidak valid. Silakan muat ulang halaman.');
    }
}

/** Buat slug URL-friendly dari teks. */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'tutorial';
}

/** Pastikan slug unik di tabel tutorials. */
function unique_slug(string $base, ?int $ignoreId = null): string
{
    $slug = $base;
    $i = 2;
    while (true) {
        if ($ignoreId) {
            $stmt = db()->prepare('SELECT COUNT(*) FROM tutorials WHERE slug = ? AND id <> ?');
            $stmt->execute([$slug, $ignoreId]);
        } else {
            $stmt = db()->prepare('SELECT COUNT(*) FROM tutorials WHERE slug = ?');
            $stmt->execute([$slug]);
        }
        if ((int) $stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

/** Pemetaan kode ikon -> emoji. */
function icon_emoji(string $code): string
{
    $map = [
        'ROCKET'   => '🚀',
        'BOOK'     => '📘',
        'GEAR'     => '⚙️',
        'SERVER'   => '🖥️',
        'CODE'     => '💻',
        'LOCK'     => '🔒',
        'DATABASE' => '🗄️',
        'CLOUD'    => '☁️',
        'PHONE'    => '📱',
        'WRENCH'   => '🔧',
        'LIGHT'    => '💡',
        'FIRE'     => '🔥',
    ];
    return $map[$code] ?? '📘';
}

/** Daftar ikon tersedia (untuk dropdown admin). */
function icon_options(): array
{
    return ['ROCKET', 'BOOK', 'GEAR', 'SERVER', 'CODE', 'LOCK', 'DATABASE', 'CLOUD', 'PHONE', 'WRENCH', 'LIGHT', 'FIRE'];
}

/** Daftar kategori tersedia. */
function category_options(): array
{
    return ['AI', 'Termux', 'VPS', 'Web', 'Bot', 'Database', 'Git', 'Tools'];
}

/** Pemetaan kategori -> emoji untuk badge. */
function category_emoji(string $cat): string
{
    $map = [
        'AI' => '🤖', 'Termux' => '📱', 'VPS' => '🖥️', 'Tools' => '🔧',
        'Web' => '🌐', 'Bot' => '💬', 'Database' => '🗄️', 'Git' => '🐙',
    ];
    return $map[$cat] ?? '📦';
}

/**
 * Mengembalikan markup inline SVG (gaya stroke/feather) berdasarkan nama ikon.
 * Semua ikon memakai currentColor sehingga mewarisi warna teks induk.
 */
function svg_icon(string $name, int $size = 20, string $cls = ''): string
{
    $paths = [
        'home'        => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'compass'     => '<circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>',
        'settings'    => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'key'         => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="M21 2l-9.6 9.6"/><path d="M15.5 7.5l3 3L22 7l-3-3"/>',
        'search'      => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'copy'        => '<rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'chevron-down'=> '<polyline points="6 9 12 15 18 9"/>',
        'arrow-left'  => '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
        'arrow-right' => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        'plus'        => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'trash'       => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'edit'        => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        'eye'         => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'check'       => '<polyline points="20 6 9 17 4 12"/>',
        'sun'         => '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>',
        'moon'        => '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>',
        'book'        => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
        'save'        => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>',
        'logout'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'list'        => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>',
        'grid'        => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
        'rocket'      => '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>',
        'server'      => '<rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/>',
        'code'        => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
        'lock'        => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'database'    => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>',
        'cloud'       => '<path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/>',
        'smartphone'  => '<rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>',
        'tool'        => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'zap'         => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
        'flame'       => '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>',
        'cpu'         => '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/>',
        'globe'       => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'message'     => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
        'git-branch'  => '<line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/>',
        'terminal'    => '<polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>',
    ];
    $inner = $paths[$name] ?? $paths['book'];
    $clsAttr = $cls !== '' ? ' ' . $cls : '';
    return '<svg class="icn' . $clsAttr . '" width="' . $size . '" height="' . $size
        . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
        . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}

/** Ikon SVG untuk kode ikon tutorial (ROCKET, BOOK, dst). */
function tutorial_icon(string $code, int $size = 24): string
{
    $map = [
        'ROCKET' => 'rocket', 'BOOK' => 'book', 'GEAR' => 'settings', 'SERVER' => 'server',
        'CODE' => 'code', 'LOCK' => 'lock', 'DATABASE' => 'database', 'CLOUD' => 'cloud',
        'PHONE' => 'smartphone', 'WRENCH' => 'tool', 'LIGHT' => 'zap', 'FIRE' => 'flame',
    ];
    return svg_icon($map[$code] ?? 'book', $size);
}

/** Ikon SVG untuk kategori. */
function category_icon(string $cat, int $size = 16): string
{
    $map = [
        'AI' => 'cpu', 'Termux' => 'terminal', 'VPS' => 'server', 'Web' => 'globe',
        'Bot' => 'message', 'Database' => 'database', 'Git' => 'git-branch', 'Tools' => 'tool',
        'Semua' => 'grid',
    ];
    return svg_icon($map[$cat] ?? 'grid', $size);
}

/** Ambil semua tutorial beserta jumlah langkah. Bisa difilter kategori & pencarian. */
function get_tutorials(?string $category = null, ?string $search = null): array
{
    $sql = 'SELECT t.*, (SELECT COUNT(*) FROM steps s WHERE s.tutorial_id = t.id) AS step_count
            FROM tutorials t WHERE 1=1';
    $params = [];
    if ($category !== null && $category !== '' && $category !== 'Semua') {
        $sql .= ' AND t.category = ?';
        $params[] = $category;
    }
    if ($search !== null && trim($search) !== '') {
        $sql .= ' AND (t.title LIKE ? OR t.description LIKE ? OR t.tags LIKE ?)';
        $like = '%' . trim($search) . '%';
        array_push($params, $like, $like, $like);
    }
    $sql .= ' ORDER BY t.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Ambil satu tutorial berdasarkan id. */
function get_tutorial(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM tutorials WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Ambil satu tutorial berdasarkan slug. */
function get_tutorial_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM tutorials WHERE slug = ?');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Ambil langkah-langkah sebuah tutorial, terurut. */
function get_steps(int $tutorialId): array
{
    $stmt = db()->prepare('SELECT * FROM steps WHERE tutorial_id = ? ORDER BY position ASC, id ASC');
    $stmt->execute([$tutorialId]);
    return $stmt->fetchAll();
}

/** Ubah string tags "a,b,c" menjadi array bersih. */
function parse_tags(string $tags): array
{
    $parts = array_map('trim', explode(',', $tags));
    return array_values(array_filter($parts, fn($p) => $p !== ''));
}
