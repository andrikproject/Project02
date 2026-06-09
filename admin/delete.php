<?php
require_once __DIR__ . '/../helpers.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/index.php'));
}
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    $stmt = db()->prepare('DELETE FROM tutorials WHERE id = ?');
    $stmt->execute([$id]);
    // Langkah otomatis terhapus via ON DELETE CASCADE.
}
redirect(url('admin/index.php?msg=deleted'));
