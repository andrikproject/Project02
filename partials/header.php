<?php
require_once __DIR__ . '/../helpers.php';
$pageTitle = $pageTitle ?? APP_NAME;
$showProgress = $showProgress ?? false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<?php if ($showProgress): ?><div id="progress-bar"></div><?php endif; ?>
<nav class="topnav">
  <a class="brand" href="<?= e(url('index.php')) ?>">
    <span class="logo">📘 <?= e(APP_NAME) ?></span>
  </a>
  <div class="nav-actions">
    <?php if (is_logged_in()): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/index.php')) ?>">⚙️ Admin</a>
    <?php else: ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/login.php')) ?>">🔑 Login</a>
    <?php endif; ?>
  </div>
</nav>
