<?php
declare(strict_types=1);

$query = isset($_GET['query']) ? (string)$_GET['query'] : '';
?>
<section class="le-hero" style="background:linear-gradient(135deg, rgba(255,227,106,.22), rgba(255,178,0,.10));">
  <div class="le-hero__grid">
    <div>
      <h1 class="le-hero__title" style="color:var(--text)">Restaurants</h1>
      <p class="le-hero__subtitle" style="color:var(--muted)">Filters, offers, distance, veg/non-veg — wired to API v1.</p>
      <div class="le-search" style="background:color-mix(in srgb,var(--surface) 88%, transparent)">
        <input class="le-input" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search restaurants or dishes…" onkeydown="if(event.key==='Enter'){location.href='/restaurants?query='+encodeURIComponent(this.value||'')}" />
        <button class="le-btn" type="button" onclick="location.href='/restaurants'">Reset</button>
      </div>
    </div>
    <div class="le-card">
      <div class="le-card__body">
        <div class="le-badge">Filters</div>
        <div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:8px">
          <span class="le-chip">Offers</span>
          <span class="le-chip">Veg</span>
          <span class="le-chip">★ 4.0+</span>
          <span class="le-chip">Under 30 min</span>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="le-section">
  <div class="le-grid2" id="le_restaurants_grid">
    <?php for ($i = 0; $i < 8; $i++): ?>
      <div class="le-card">
        <div class="le-card__body">
          <div class="le-skeleton" style="height:12px;width:120px"></div>
          <div class="le-skeleton" style="height:14px;width:160px;margin-top:10px"></div>
          <div class="le-skeleton" style="height:12px;width:200px;margin-top:8px"></div>
        </div>
      </div>
    <?php endfor; ?>
  </div>
</section>

<script>
  (() => {
    const grid = document.getElementById('le_restaurants_grid')
    const url = new URL('/api/v1/restaurants', location.origin)
    const q = new URLSearchParams(location.search)
    if (q.get('query')) url.searchParams.set('query', q.get('query'))
    window.leApi(url.pathname + url.search).then((res) => {
      if (!res.ok) return
      const items = res.data?.data?.items || []
      grid.innerHTML = items.map((r) => {
        const cuisines = (() => { try { return JSON.parse(r.cuisines || '[]') } catch { return [] } })()
        return `
          <a class="le-card" href="/r/${encodeURIComponent(r.slug)}">
            <div class="le-card__body">
              <div class="le-badge">★ ${Number(r.rating).toFixed(1)} · Fast</div>
              <div style="margin-top:10px;font-weight:900">${r.name}</div>
              <div class="le-muted" style="margin-top:4px">${cuisines.slice(0,3).join(' · ')}</div>
              <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
                <span class="le-chip">${r.type === 'platform' ? 'Kitchen' : 'Restaurant'}</span>
                <span class="le-chip">Offers</span>
              </div>
            </div>
          </a>
        `
      }).join('')
    })
  })()
</script>
