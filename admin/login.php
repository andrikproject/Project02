<?php
require_once __DIR__ . '/../helpers.php';
start_session();

if (is_logged_in()) {
    redirect(url('admin/index.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($user === ADMIN_USER && password_verify($pass, ADMIN_PASS_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        redirect(url('admin/index.php'));
    } else {
        $error = 'Username atau password salah.';
    }
}

$pageTitle = 'Login Admin — ' . APP_NAME;
$activeNav = 'admin';
require __DIR__ . '/../partials/header.php';
?>

<div class="login-wrap">
  <div class="login-card card-panel">
    <h2>🔑 Login Admin</h2>
    <?php if ($error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('admin/login.php')) ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label>Username</label>
        <input class="form-control" type="text" name="username" autofocus required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input class="form-control" type="password" name="password" required>
      </div>
      <button class="btn btn-primary" type="submit" style="width:100%">Masuk</button>
    </form>
    <p class="muted" style="margin-top:14px">Default: <code>admin</code> / <code>admin123</code></p>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
