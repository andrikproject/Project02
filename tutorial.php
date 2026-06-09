<?php
require_once __DIR__ . '/helpers.php';

$id = (int) ($_GET['id'] ?? 0);
$tutorial = $id ? get_tutorial($id) : null;

if (!$tutorial) {
    http_response_code(404);
    $pageTitle = 'Tutorial tidak ditemukan';
    require __DIR__ . '/partials/header.php';
    echo '<div class="container section-pad"><div class="empty"><div class="ico">🔍</div>'
       . '<p>Tutorial tidak ditemukan.</p>'
       . '<p><a class="back-link" href="' . e(url('index.php')) . '">← Kembali ke beranda</a></p>'
       . '</div></div>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

$steps = get_steps($id);
$tags  = parse_tags($tutorial['tags']);

$pageTitle = $tutorial['title'] . ' — ' . APP_NAME;
$showProgress = true;
$activeNav = 'explore';
require __DIR__ . '/partials/header.php';
?>

<div class="container">

  <a class="back-link" href="<?= e(url('index.php')) ?>">← Semua Tutorial</a>

  <header class="detail-head">
    <span class="badge"><?= category_emoji($tutorial['category']) ?> <?= e($tutorial['category']) ?></span>
    <h1><?= icon_emoji($tutorial['icon']) ?> <?= e($tutorial['title']) ?></h1>
    <p><?= e($tutorial['description']) ?></p>
    <?php if ($tags): ?>
      <div class="specs">
        <?php foreach ($tags as $tag): ?>
          <span><?= e($tag) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </header>

  <?php if (empty($steps)): ?>
    <div class="empty"><div class="ico">📝</div><p>Tutorial ini belum memiliki langkah.</p></div>
  <?php else: ?>

    <!-- Daftar Isi -->
    <nav class="toc">
      <h2>📋 Daftar Isi — <?= count($steps) ?> Langkah</h2>
      <ol>
        <?php foreach ($steps as $i => $s): ?>
          <li><a href="#step-<?= $i + 1 ?>"><?= e($s['title']) ?></a></li>
        <?php endforeach; ?>
      </ol>
    </nav>

    <!-- Langkah-langkah -->
    <div class="section-pad">
      <?php foreach ($steps as $i => $s): $n = $i + 1; ?>
        <article class="step" id="step-<?= $n ?>">
          <div class="step-header">
            <span class="step-number"><?= $n ?></span>
            <span class="step-title"><?= e($s['title']) ?></span>
            <span class="step-icon">▼</span>
          </div>
          <div class="step-body">
            <?= $s['body'] /* HTML konten langkah, dikelola admin */ ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>

</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
