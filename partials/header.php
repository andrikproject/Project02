<?php
require_once __DIR__ . '/../helpers.php';
$pageTitle = $pageTitle ?? APP_NAME;
$showProgress = $showProgress ?? false;
$activeNav = $activeNav ?? 'home';
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#070b14">
<meta name="description" content="Kumpulan tutorial langkah demi langkah seputar AI, Termux, VPS, Web, Bot, Database, dan Git.">
<title><?= e($pageTitle) ?></title>
<script>
/* Set tema sedini mungkin untuk mencegah kedipan (FOUC). */
(function(){try{var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t);}catch(e){}})();
</script>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<?php if ($showProgress): ?><div id="progress-bar"></div><?php endif; ?>
<nav class="topnav">
  <a class="brand" href="<?= e(url('index.php')) ?>">
    <span class="logo-badge"><?= svg_icon('book', 19) ?></span>
    <span class="logo-text"><?= e(APP_NAME) ?></span>
  </a>
  <div class="nav-actions">
    <button class="theme-toggle" id="theme-toggle" type="button" aria-label="Ganti tema" title="Ganti tema terang/gelap">
      <?= svg_icon('moon', 19, 'icn-moon') ?>
      <?= svg_icon('sun', 19, 'icn-sun') ?>
    </button>
    <?php if (is_logged_in()): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/index.php')) ?>"><?= svg_icon('settings', 17) ?> Admin</a>
    <?php else: ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/login.php')) ?>"><?= svg_icon('key', 17) ?> Login</a>
    <?php endif; ?>
  </div>
</nav>
