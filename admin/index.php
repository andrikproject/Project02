<?php
require_once __DIR__ . '/../helpers.php';
require_login();

$flash = $_GET['msg'] ?? '';
$tutorials = get_tutorials();

$pageTitle = 'Dashboard Admin — ' . APP_NAME;
$activeNav = 'admin';
require __DIR__ . '/../partials/header.php';
?>

<div class="admin-wrap section-pad">

  <div class="admin-bar">
    <h1>⚙️ Dashboard Admin</h1>
    <div class="row-actions">
      <a class="btn btn-primary btn-sm" href="<?= e(url('admin/edit.php')) ?>">+ Tutorial Baru</a>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/logout.php')) ?>">Keluar</a>
    </div>
  </div>

  <?php if ($flash === 'saved'): ?>
    <div class="alert alert-success">Tutorial berhasil disimpan.</div>
  <?php elseif ($flash === 'deleted'): ?>
    <div class="alert alert-success">Tutorial berhasil dihapus.</div>
  <?php endif; ?>

  <?php if (empty($tutorials)): ?>
    <div class="empty">
      <div class="ico">📭</div>
      <p>Belum ada tutorial. Klik "Tutorial Baru" untuk membuat.</p>
    </div>
  <?php else: ?>
    <div class="card-panel" style="padding:8px 12px">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Judul</th>
            <th>Kategori</th>
            <th>Langkah</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tutorials as $t): ?>
            <tr>
              <td>
                <strong><?= icon_emoji($t['icon']) ?> <?= e($t['title']) ?></strong><br>
                <span class="muted"><?= e($t['description']) ?></span>
              </td>
              <td><span class="badge"><?= category_emoji($t['category']) ?> <?= e($t['category']) ?></span></td>
              <td><?= (int)$t['step_count'] ?></td>
              <td>
                <div class="row-actions">
                  <a class="btn btn-ghost btn-sm" href="<?= e(url('tutorial.php?id=' . (int)$t['id'])) ?>" target="_blank">👁️</a>
                  <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/edit.php?id=' . (int)$t['id'])) ?>">✏️ Edit</a>
                  <form method="post" action="<?= e(url('admin/delete.php')) ?>" onsubmit="return confirm('Hapus tutorial ini beserta semua langkahnya?');" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                    <button class="btn btn-danger btn-sm" type="submit">🗑️</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
