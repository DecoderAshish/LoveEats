<?php
declare(strict_types=1);
?>
<section class="le-hero">
  <div class="le-hero__grid">
    <div>
      <h1 class="le-hero__title">Bold cravings.<br/>Warm delivery.</h1>
      <p class="le-hero__subtitle">Premium kitchens + partner restaurants, curated for “just one more bite”.</p>
      <form class="le-search" onsubmit="event.preventDefault(); location.href='/restaurants?query='+encodeURIComponent(this.query.value||'')">
        <input class="le-input" name="query" placeholder="Search dishes, restaurants, midnight cravings…" autocomplete="off" />
        <button class="le-btn" type="submit">Search</button>
      </form>
      <div class="le-chiprow">
        <span class="le-chip">15–30 min delivery</span>
        <span class="le-chip">Wallet cashback</span>
        <span class="le-chip">Couple meals</span>
        <span class="le-chip">Midnight delivery</span>
      </div>
    </div>
    <div class="le-card">
      <div class="le-card__body">
        <div class="le-badge">AI-ready</div>
        <h3 style="margin:10px 0 6px;font-size:16px;font-weight:880">Smart recommendations</h3>
        <div class="le-muted">Taste-aware rails, reorder predictions, and diet tags (scaffolded for future AI integration).</div>
        <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap">
          <a class="le-btn" href="/home" style="text-align:center">Enter Home</a>
          <a class="le-nav__link" href="/reels" style="background:rgba(255,255,255,.58);border:1px solid rgba(23,23,23,.12)">Watch Reels</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="le-section">
  <div class="le-section__head">
    <h2 class="le-section__title">Trending restaurants</h2>
    <a class="le-section__link" href="/restaurants">View all</a>
  </div>
  <div class="le-rail">
    <?php for ($i = 0; $i < 6; $i++): ?>
      <div class="le-card">
        <div class="le-card__body">
          <div class="le-badge">★ 4.6 · 22 min</div>
          <div style="margin-top:10px;font-weight:880">Sunrise Kitchen</div>
          <div class="le-muted" style="margin-top:4px">Bowls · Wraps · Desserts</div>
        </div>
      </div>
    <?php endfor; ?>
  </div>
</section>

<section class="le-section">
  <div class="le-section__head">
    <h2 class="le-section__title">Food reels</h2>
    <a class="le-section__link" href="/reels">Open feed</a>
  </div>
  <div class="le-rail">
    <?php for ($i = 0; $i < 8; $i++): ?>
      <div class="le-card" style="min-width:160px">
        <div class="le-card__body">
          <div class="le-skeleton" style="height:170px;border-radius:18px"></div>
          <div style="margin-top:10px;font-weight:860">Cheesy melt</div>
          <div class="le-muted" style="margin-top:4px">Tap to watch</div>
        </div>
      </div>
    <?php endfor; ?>
  </div>
</section>

<section class="le-section">
  <div class="le-grid2">
    <div class="le-card">
      <div class="le-card__body">
        <div class="le-badge">Couple meals</div>
        <div style="margin-top:10px;font-weight:900;font-size:18px">For two. For tonight.</div>
        <div class="le-muted" style="margin-top:6px">Curated combos with add-ons and dessert pairings.</div>
        <div style="margin-top:12px"><a class="le-btn" href="/couples">Explore</a></div>
      </div>
    </div>
    <div class="le-card">
      <div class="le-card__body">
        <div class="le-badge">Midnight delivery</div>
        <div style="margin-top:10px;font-weight:900;font-size:18px">Cravings don’t sleep.</div>
        <div class="le-muted" style="margin-top:6px">Late-night kitchens, limited drops, fast checkout.</div>
        <div style="margin-top:12px"><a class="le-btn" href="/midnight">Open</a></div>
      </div>
    </div>
  </div>
</section>
