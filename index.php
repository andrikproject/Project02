<?php
require_once __DIR__ . '/helpers.php';

$activeCat = $_GET['cat'] ?? 'Semua';
$search    = $_GET['q'] ?? '';

$catFilter = $activeCat === 'Semua' ? null : $activeCat;
$tutorials = get_tutorials($catFilter, $search);

// Statistik untuk hero
$allCount = (int) db()->query('SELECT COUNT(*) FROM tutorials')->fetchColumn();
$catCount = count(category_options());

$activeNav = 'home';
$pageTitle = APP_NAME . ' — Tutorial AI, Termux, VPS, Web & Bot';
require __DIR__ . '/partials/header.php';
?>

<section class="hero">
  <span class="pill"><span class="dot"></span> <?= $allCount ?> tutorial siap pakai</span>
  <h1>Belajar <span class="grad">Tools Keren</span><br>Langkah demi Langkah</h1>
  <p>Panduan praktis seputar AI, Termux, VPS, deploy web, bot, database, dan Git. Pilih, ikuti, salin perintahnya — selesai.</p>
  <div class="stat-row">
    <div class="stat"><b><?= $allCount ?></b><span>Tutorial</span></div>
    <div class="stat"><b><?= $catCount ?></b><span>Kategori</span></div>
    <div class="stat"><b>100%</b><span>Gratis</span></div>
  </div>
</section>

<div class="container section-pad" id="jelajah">

  <!-- Pencarian -->
  <form method="get" action="<?= e(url('index.php')) ?>" class="search-box">
    <span class="ico">🔍</span>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cari tutorial... (ollama, docker, telegram)">
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
        $href = url('index.php') . ($qs ? '?' . $qs : '') . '#jelajah';
        $label = $c === 'Semua' ? '📚 Semua' : category_emoji($c) . ' ' . $c;
    ?>
      <a class="filter-chip <?= $isActive ? 'active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>

  <!-- Judul daftar -->
  <div class="list-head">
    <h2>
      <?php if ($search !== ''): ?>Hasil: "<?= e($search) ?>"
      <?php elseif ($activeCat !== 'Semua'): ?><?= category_emoji($activeCat) ?> <?= e($activeCat) ?>
      <?php else: ?>Semua Tutorial<?php endif; ?>
    </h2>
    <span class="count"><?= count($tutorials) ?> item</span>
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
        <a class="card cat-<?= e($t['category']) ?>" href="<?= e(url('tutorial.php?id=' . (int)$t['id'])) ?>">
          <div class="card-top">
            <div class="card-icon"><?= icon_emoji($t['icon']) ?></div>
            <span class="badge"><?= category_emoji($t['category']) ?> <?= e($t['category']) ?></span>
          </div>
          <h3><?= e($t['title']) ?></h3>
          <p><?= e($t['description']) ?></p>
          <div class="card-meta">
            <span>📋 <?= (int)$t['step_count'] ?> langkah</span>
            <span class="go">Buka →</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
