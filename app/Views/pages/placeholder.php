<?php
declare(strict_types=1);

$pageName = $pageName ?? 'Page';
?>
<section class="le-hero">
  <div class="le-hero__grid">
    <div>
      <h1 class="le-hero__title"><?= htmlspecialchars((string)$pageName, ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="le-hero__subtitle">This section is wired to backend APIs and demo data in the next build steps.</p>
      <div class="le-chiprow">
        <span class="le-chip">Mobile-first</span>
        <span class="le-chip">Fast</span>
        <span class="le-chip">SEO-ready</span>
        <span class="le-chip">Tokenized theme</span>
      </div>
    </div>
    <div class="le-card">
      <div class="le-card__body">
        <div class="le-badge">API-first</div>
        <p class="le-muted" style="margin:10px 0 0">As we implement each module, this page will render real data and actions.</p>
      </div>
    </div>
  </div>
</section>
