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
    return ['AI', 'Termux', 'VPS', 'Tools'];
}

/** Pemetaan kategori -> emoji untuk badge. */
function category_emoji(string $cat): string
{
    $map = ['AI' => '🤖', 'Termux' => '📱', 'VPS' => '🖥️', 'Tools' => '🔧'];
    return $map[$cat] ?? '📦';
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
