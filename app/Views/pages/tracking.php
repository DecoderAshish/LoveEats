<?php
declare(strict_types=1);

$orderPublicId = $orderPublicId ?? '';
?>
<section class="le-hero" style="background:linear-gradient(135deg, rgba(255,227,106,.14), rgba(255,178,0,.08));">
  <div class="le-hero__grid">
    <div>
      <h1 class="le-hero__title" style="color:var(--text)">Live tracking</h1>
      <p class="le-hero__subtitle" style="color:var(--muted)">Polling-first, websocket-ready tracking events with ETA calculations.</p>
      <div class="le-chiprow">
        <span class="le-chip">Order: <?= htmlspecialchars((string)$orderPublicId, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="le-chip">Driver: Priya · Bike</span>
        <span class="le-chip">ETA 14 min</span>
      </div>
      <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap">
        <button class="le-btn" type="button">Chat</button>
        <button class="le-btn" type="button" style="background:linear-gradient(135deg,var(--yellow-1),var(--yellow-2))">Call</button>
      </div>
    </div>
    <div class="le-card">
      <div class="le-card__body">
        <div class="le-badge">Timeline</div>
        <div style="margin-top:12px;display:grid;gap:10px">
          <?php foreach (['Order confirmed', 'Preparing', 'Picked up', 'On the way', 'Delivered'] as $idx => $label): ?>
            <div style="display:flex;gap:10px;align-items:center">
              <span style="width:10px;height:10px;border-radius:999px;background:<?= $idx < 3 ? 'linear-gradient(135deg,var(--yellow-2),var(--yellow-3))' : 'color-mix(in srgb,var(--line) 100%, transparent)' ?>"></span>
              <span style="font-weight:<?= $idx < 3 ? '900' : '650' ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:14px" class="le-muted">Map container and location dots are enabled after delivery_tracking APIs + demo data.</div>
      </div>
    </div>
  </div>
</section>

<section class="le-section">
  <div class="le-card">
    <div class="le-card__body">
      <div class="le-badge">Map</div>
      <div class="le-skeleton" style="height:320px;border-radius:22px;margin-top:12px"></div>
    </div>
  </div>
</section>
