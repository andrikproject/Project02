<?php
require_once __DIR__ . '/../helpers.php';
$pageTitle = $pageTitle ?? APP_NAME;
$showProgress = $showProgress ?? false;
$activeNav = $activeNav ?? 'home';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#070b14">
<meta name="description" content="Kumpulan tutorial langkah demi langkah seputar AI, Termux, VPS, Web, Bot, Database, dan Git.">
<title><?= e($pageTitle) ?></title>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<?php if ($showProgress): ?><div id="progress-bar"></div><?php endif; ?>
<nav class="topnav">
  <a class="brand" href="<?= e(url('index.php')) ?>">
    <span class="logo-badge">📘</span>
    <span class="logo-text"><?= e(APP_NAME) ?></span>
  </a>
  <div class="nav-actions">
    <?php if (is_logged_in()): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/index.php')) ?>">⚙️ Admin</a>
    <?php else: ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/login.php')) ?>">🔑 Login</a>
    <?php endif; ?>
  </div>
</nav>
