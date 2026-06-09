<?php
require_once __DIR__ . '/helpers.php';

$activeCat = $_GET['cat'] ?? 'Semua';
$search    = $_GET['q'] ?? '';

$catFilter = $activeCat === 'Semua' ? null : $activeCat;
$tutorials = get_tutorials($catFilter, $search);

$pageTitle = APP_NAME . ' — Kumpulan Tutorial AI, Termux & VPS';
require __DIR__ . '/partials/header.php';
?>

<section class="hero">
  <h1>🚀 Kumpulan Tutorial Keren</h1>
  <p>Panduan langkah demi langkah seputar <strong>AI</strong>, <strong>Termux</strong>, dan <strong>Install Tools di VPS</strong>. Pilih tutorial, ikuti, salin perintahnya, selesai.</p>
</section>

<div class="container section-pad">

  <!-- Pencarian -->
  <form method="get" action="<?= e(url('index.php')) ?>" class="search-box">
    <span>🔍</span>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cari tutorial... (mis. ollama, docker, ssh)">
    <?php if ($activeCat !== 'Semua'): ?>
      <input type="hidden" name="cat" value="<?= e($activeCat) ?>">
    <?php endif; ?>
    <button class="btn btn-primary btn-sm" type="submit">Cari</button>
  </form>

  <!-- Filter kategori -->
  <div class="filters">
    <?php
      $cats = array_merge(['Semua'], category_options());
      foreach ($cats as $c):
        $isActive = ($c === $activeCat);
        $qs = http_build_query(array_filter(['cat' => $c === 'Semua' ? null : $c, 'q' => $search ?: null]));
        $href = url('index.php') . ($qs ? '?' . $qs : '');
        $label = $c === 'Semua' ? '📚 Semua' : category_emoji($c) . ' ' . $c;
    ?>
      <a class="filter-chip <?= $isActive ? 'active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>

  <!-- Grid tutorial -->
  <?php if (empty($tutorials)): ?>
    <div class="empty">
      <div class="ico">🗂️</div>
      <p>Belum ada tutorial yang cocok.</p>
      <?php if ($search !== '' || $activeCat !== 'Semua'): ?>
        <p><a class="back-link" href="<?= e(url('index.php')) ?>">← Tampilkan semua tutorial</a></p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="cards">
      <?php foreach ($tutorials as $t): ?>
        <a class="card" href="<?= e(url('tutorial.php?id=' . (int)$t['id'])) ?>">
          <div class="card-icon"><?= icon_emoji($t['icon']) ?></div>
          <span class="badge"><?= category_emoji($t['category']) ?> <?= e($t['category']) ?></span>
          <h3><?= e($t['title']) ?></h3>
          <p><?= e($t['description']) ?></p>
          <div class="card-meta">
            <span>📋 <?= (int)$t['step_count'] ?> langkah</span>
            <span>→ Buka panduan</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
