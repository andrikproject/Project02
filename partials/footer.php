<footer class="footer">
  <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> · Dibangun dengan PHP Native.</p>
</footer>

<!-- Bottom navigation (mobile app style) -->
<nav class="bottom-nav">
  <a href="<?= e(url('index.php')) ?>" class="<?= ($activeNav ?? '') === 'home' ? 'active' : '' ?>">
    <span class="bi">🏠</span>
    <span>Beranda</span>
  </a>
  <a href="<?= e(url('index.php#jelajah')) ?>" class="<?= ($activeNav ?? '') === 'explore' ? 'active' : '' ?>">
    <span class="bi">🧭</span>
    <span>Jelajah</span>
  </a>
  <?php if (is_logged_in()): ?>
    <a href="<?= e(url('admin/index.php')) ?>" class="<?= ($activeNav ?? '') === 'admin' ? 'active' : '' ?>">
      <span class="bi">⚙️</span>
      <span>Admin</span>
    </a>
  <?php else: ?>
    <a href="<?= e(url('admin/login.php')) ?>" class="<?= ($activeNav ?? '') === 'admin' ? 'active' : '' ?>">
      <span class="bi">🔑</span>
      <span>Login</span>
    </a>
  <?php endif; ?>
</nav>

<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
