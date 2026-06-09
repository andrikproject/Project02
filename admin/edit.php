<?php
require_once __DIR__ . '/../helpers.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$error = '';

/* ── Tangani aksi POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // Simpan metadata tutorial (buat baru / update)
    if ($action === 'save_tutorial') {
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $cat   = $_POST['category'] ?? 'Tools';
        $icon  = $_POST['icon'] ?? 'BOOK';
        $tags  = trim($_POST['tags'] ?? '');

        if (!in_array($cat, category_options(), true)) $cat = 'Tools';
        if (!in_array($icon, icon_options(), true)) $icon = 'BOOK';

        if ($title === '') {
            $error = 'Judul wajib diisi.';
        } else {
            if ($id) {
                $slug = unique_slug(slugify($title), $id);
                $stmt = db()->prepare(
                    'UPDATE tutorials SET title=?, slug=?, description=?, category=?, icon=?, tags=?, updated_at=datetime(\'now\') WHERE id=?'
                );
                $stmt->execute([$title, $slug, $desc, $cat, $icon, $tags, $id]);
            } else {
                $slug = unique_slug(slugify($title));
                $stmt = db()->prepare(
                    'INSERT INTO tutorials (title, slug, description, category, icon, tags) VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$title, $slug, $desc, $cat, $icon, $tags]);
                $id = (int) db()->lastInsertId();
            }
            redirect(url('admin/edit.php?id=' . $id . '&msg=saved'));
        }
    }

    // Tambah langkah baru
    if ($action === 'add_step' && $id) {
        $stitle = trim($_POST['step_title'] ?? '');
        $sbody  = $_POST['step_body'] ?? '';
        if ($stitle !== '') {
            $max = db()->prepare('SELECT COALESCE(MAX(position),0) FROM steps WHERE tutorial_id=?');
            $max->execute([$id]);
            $pos = (int) $max->fetchColumn() + 1;
            $stmt = db()->prepare('INSERT INTO steps (tutorial_id, position, title, body) VALUES (?, ?, ?, ?)');
            $stmt->execute([$id, $pos, $stitle, $sbody]);
        }
        redirect(url('admin/edit.php?id=' . $id . '#langkah'));
    }

    // Update langkah
    if ($action === 'update_step' && $id) {
        $sid    = (int) ($_POST['step_id'] ?? 0);
        $stitle = trim($_POST['step_title'] ?? '');
        $sbody  = $_POST['step_body'] ?? '';
        if ($sid && $stitle !== '') {
            $stmt = db()->prepare('UPDATE steps SET title=?, body=? WHERE id=? AND tutorial_id=?');
            $stmt->execute([$stitle, $sbody, $sid, $id]);
        }
        redirect(url('admin/edit.php?id=' . $id . '#step-edit-' . $sid));
    }

    // Hapus langkah
    if ($action === 'delete_step' && $id) {
        $sid = (int) ($_POST['step_id'] ?? 0);
        $stmt = db()->prepare('DELETE FROM steps WHERE id=? AND tutorial_id=?');
        $stmt->execute([$sid, $id]);
        redirect(url('admin/edit.php?id=' . $id . '#langkah'));
    }

    // Pindah urutan langkah (atas/bawah)
    if ($action === 'move_step' && $id) {
        $sid = (int) ($_POST['step_id'] ?? 0);
        $dir = $_POST['dir'] ?? '';
        $steps = get_steps($id);
        $index = null;
        foreach ($steps as $i => $s) { if ((int)$s['id'] === $sid) { $index = $i; break; } }
        if ($index !== null) {
            $swap = $dir === 'up' ? $index - 1 : $index + 1;
            if ($swap >= 0 && $swap < count($steps)) {
                $a = $steps[$index]; $b = $steps[$swap];
                $u = db()->prepare('UPDATE steps SET position=? WHERE id=?');
                $u->execute([(int)$b['position'], (int)$a['id']]);
                $u->execute([(int)$a['position'], (int)$b['id']]);
            }
        }
        redirect(url('admin/edit.php?id=' . $id . '#langkah'));
    }
}

/* ── Ambil data untuk ditampilkan ── */
$tutorial = $id ? get_tutorial($id) : null;
if ($id && !$tutorial) {
    redirect(url('admin/index.php'));
}
$steps = $id ? get_steps($id) : [];
$flash = $_GET['msg'] ?? '';

$pageTitle = ($id ? 'Edit' : 'Tutorial Baru') . ' — ' . APP_NAME;
$activeNav = 'admin';
require __DIR__ . '/../partials/header.php';
?>

<div class="admin-wrap section-pad">

  <div class="admin-bar">
    <h1><?= $id ? svg_icon('edit', 20) . ' Edit Tutorial' : svg_icon('plus', 20) . ' Tutorial Baru' ?></h1>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/index.php')) ?>"><?= svg_icon('arrow-left', 16) ?> Dashboard</a>
  </div>

  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <?php if ($flash === 'saved'): ?><div class="alert alert-success">Tersimpan.</div><?php endif; ?>

  <!-- ── Form metadata tutorial ── -->
  <div class="card-panel">
    <h2><?= svg_icon('book', 18) ?> Informasi Tutorial</h2>
    <form method="post" action="<?= e(url('admin/edit.php' . ($id ? '?id=' . $id : ''))) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_tutorial">

      <div class="form-group">
        <label>Judul</label>
        <input class="form-control" type="text" name="title" required
               value="<?= e($tutorial['title'] ?? '') ?>" placeholder="mis. Install Docker di VPS">
      </div>

      <div class="form-group">
        <label>Deskripsi singkat</label>
        <input class="form-control" type="text" name="description"
               value="<?= e($tutorial['description'] ?? '') ?>" placeholder="Ringkasan satu kalimat">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Kategori</label>
          <select class="form-control" name="category">
            <?php foreach (category_options() as $c): ?>
              <option value="<?= e($c) ?>" <?= ($tutorial['category'] ?? '') === $c ? 'selected' : '' ?>>
                <?= category_emoji($c) ?> <?= e($c) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Ikon</label>
          <select class="form-control" name="icon">
            <?php foreach (icon_options() as $ic): ?>
              <option value="<?= e($ic) ?>" <?= ($tutorial['icon'] ?? '') === $ic ? 'selected' : '' ?>>
                <?= icon_emoji($ic) ?> <?= e($ic) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Tag / Spesifikasi <span class="muted">(pisahkan dengan koma)</span></label>
        <input class="form-control" type="text" name="tags"
               value="<?= e($tutorial['tags'] ?? '') ?>" placeholder="Docker, Ubuntu, Gratis">
      </div>

      <button class="btn btn-primary" type="submit"><?= svg_icon('save', 17) ?> Simpan Tutorial</button>
    </form>
  </div>

  <?php if (!$id): ?>
    <p class="muted">Simpan informasi tutorial terlebih dahulu, lalu Anda dapat menambahkan langkah-langkah.</p>
  <?php else: ?>

    <!-- ── Kelola langkah ── -->
    <div id="langkah">
      <div class="admin-bar"><h1 style="font-size:1.1rem"><?= svg_icon('list', 18) ?> Langkah-langkah (<?= count($steps) ?>)</h1></div>

      <?php foreach ($steps as $i => $s): $n = $i + 1; ?>
        <div class="step-edit" id="step-edit-<?= (int)$s['id'] ?>">
          <form method="post" action="<?= e(url('admin/edit.php?id=' . $id)) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_step">
            <input type="hidden" name="step_id" value="<?= (int)$s['id'] ?>">

            <div class="step-edit-head">
              <span class="pos"><?= $n ?></span>
              <input class="form-control" type="text" name="step_title" value="<?= e($s['title']) ?>" required>
            </div>

            <div class="toolbar">
              <button type="button" onclick="insertSnippet(this,'heading')">+ Sub-judul</button>
              <button type="button" onclick="insertSnippet(this,'para')">+ Paragraf</button>
              <button type="button" onclick="insertSnippet(this,'code')">+ Kode</button>
              <button type="button" onclick="insertSnippet(this,'tip')">+ Tips</button>
              <button type="button" onclick="insertSnippet(this,'warning')">+ Peringatan</button>
            </div>
            <div class="form-group">
              <textarea class="form-control step-body-input" name="step_body" rows="8"><?= e($s['body']) ?></textarea>
            </div>

            <div class="row-actions">
              <button class="btn btn-primary btn-sm" type="submit"><?= svg_icon('save', 15) ?> Simpan langkah</button>
            </div>
          </form>

          <div class="row-actions" style="margin-top:8px">
            <!-- Pindah atas -->
            <form method="post" action="<?= e(url('admin/edit.php?id=' . $id)) ?>" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="move_step">
              <input type="hidden" name="step_id" value="<?= (int)$s['id'] ?>">
              <input type="hidden" name="dir" value="up">
              <button class="btn btn-ghost btn-sm" type="submit" title="Naik" <?= $i === 0 ? 'disabled' : '' ?>><span style="transform:rotate(180deg);display:inline-grid"><?= svg_icon('chevron-down', 15) ?></span></button>
            </form>
            <!-- Pindah bawah -->
            <form method="post" action="<?= e(url('admin/edit.php?id=' . $id)) ?>" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="move_step">
              <input type="hidden" name="step_id" value="<?= (int)$s['id'] ?>">
              <input type="hidden" name="dir" value="down">
              <button class="btn btn-ghost btn-sm" type="submit" title="Turun" <?= $i === count($steps) - 1 ? 'disabled' : '' ?>><?= svg_icon('chevron-down', 15) ?></button>
            </form>
            <!-- Hapus -->
            <form method="post" action="<?= e(url('admin/edit.php?id=' . $id)) ?>" style="display:inline"
                  onsubmit="return confirm('Hapus langkah ini?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete_step">
              <input type="hidden" name="step_id" value="<?= (int)$s['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit"><?= svg_icon('trash', 15) ?> Hapus</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- ── Tambah langkah baru ── -->
      <div class="card-panel">
        <h2><?= svg_icon('plus', 18) ?> Tambah Langkah</h2>
        <form method="post" action="<?= e(url('admin/edit.php?id=' . $id)) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add_step">
          <div class="form-group">
            <label>Judul langkah</label>
            <input class="form-control" type="text" name="step_title" required placeholder="mis. Install Docker">
          </div>
          <div class="toolbar">
            <button type="button" onclick="insertSnippet(this,'heading')">+ Sub-judul</button>
            <button type="button" onclick="insertSnippet(this,'para')">+ Paragraf</button>
            <button type="button" onclick="insertSnippet(this,'code')">+ Kode</button>
            <button type="button" onclick="insertSnippet(this,'tip')">+ Tips</button>
            <button type="button" onclick="insertSnippet(this,'warning')">+ Peringatan</button>
          </div>
          <div class="form-group">
            <label>Isi langkah <span class="muted">(HTML didukung — gunakan tombol di atas)</span></label>
            <textarea class="form-control step-body-input" name="step_body" rows="8" placeholder="<p>Penjelasan...</p>"></textarea>
          </div>
          <button class="btn btn-primary" type="submit"><?= svg_icon('plus', 16) ?> Tambah Langkah</button>
        </form>
      </div>
    </div>

  <?php endif; ?>

</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
