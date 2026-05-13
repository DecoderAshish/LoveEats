<?php
declare(strict_types=1);
?>
<section class="le-hero" style="background:linear-gradient(135deg, rgba(255,227,106,.16), rgba(255,178,0,.08));">
  <div class="le-hero__grid">
    <div>
      <h1 class="le-hero__title" style="color:var(--text)">Cart</h1>
      <p class="le-hero__subtitle" style="color:var(--muted)">Persistent cart with variants, add-ons, instructions, and tips.</p>
      <div class="le-chiprow">
        <span class="le-chip">Add-ons</span>
        <span class="le-chip">Special instructions</span>
        <span class="le-chip">Dynamic fees</span>
      </div>
    </div>
    <div class="le-card">
      <div class="le-card__body">
        <div class="le-badge">Upsell</div>
        <div style="margin-top:10px;font-weight:900">Add dessert?</div>
        <div class="le-muted" style="margin-top:4px">AI upsells will land here (service scaffolding ready).</div>
      </div>
    </div>
  </div>
</section>

<section class="le-section">
  <div class="le-grid2">
    <div class="le-card">
      <div class="le-card__body">
        <div class="le-badge">Items</div>
        <div style="margin-top:12px;display:grid;gap:12px" id="le_cart_items"></div>
        <div class="le-muted" id="le_cart_hint" style="margin-top:12px;font-size:13px"></div>
      </div>
    </div>
    <div class="le-card">
      <div class="le-card__body">
        <div class="le-badge">Summary</div>
        <div style="margin-top:12px;display:grid;gap:10px" id="le_cart_summary"></div>
        <div style="margin-top:14px">
          <a class="le-btn" href="/checkout" style="display:block;text-align:center">Checkout</a>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="le-stickybar">
  <div>
    <div style="font-weight:900" id="le_sticky_total">₹0</div>
    <div class="le-muted" style="font-size:13px" id="le_sticky_meta">0 items</div>
  </div>
  <a class="le-btn" href="/checkout">Proceed</a>
</div>

<script>
  (() => {
    const itemsEl = document.getElementById('le_cart_items')
    const sumEl = document.getElementById('le_cart_summary')
    const hintEl = document.getElementById('le_cart_hint')
    const stickyTotal = document.getElementById('le_sticky_total')
    const stickyMeta = document.getElementById('le_sticky_meta')

    const render = (cart) => {
      const items = cart.items || []
      if (!items.length) {
        itemsEl.innerHTML = '<div class="le-muted">Your cart is empty.</div>'
        sumEl.innerHTML = ''
        stickyTotal.textContent = '₹0'
        stickyMeta.textContent = '0 items'
        return
      }
      itemsEl.innerHTML = items.map((it) => {
        const addOns = (it.add_ons || []).map((a) => a.name).join(' · ')
        return `
          <div style="border-top:1px solid var(--line);padding-top:12px;display:flex;justify-content:space-between;gap:12px;align-items:start">
            <div>
              <div style="font-weight:900">${it.food_name}</div>
              <div class="le-muted" style="margin-top:2px;font-size:13px">${addOns ? ('Add-ons: ' + addOns) : ''}</div>
              <div class="le-muted" style="margin-top:6px;font-size:13px">${it.instructions ? ('Instructions: ' + it.instructions) : ''}</div>
            </div>
            <div style="text-align:right">
              <div style="font-weight:900">₹${Number(it.line_total).toFixed(0)}</div>
              <div style="margin-top:10px;display:flex;gap:8px;justify-content:end">
                <button class="le-iconbtn" type="button" style="width:40px;height:40px;border-radius:14px" data-action="qty" data-id="${it.id}" data-delta="-1">−</button>
                <div style="min-width:28px;text-align:center;font-weight:850;padding-top:8px">${it.quantity}</div>
                <button class="le-iconbtn" type="button" style="width:40px;height:40px;border-radius:14px" data-action="qty" data-id="${it.id}" data-delta="1">+</button>
              </div>
              <div style="margin-top:10px">
                <button class="le-nav__link" type="button" data-action="remove" data-id="${it.id}" style="background:color-mix(in srgb,var(--surface) 88%, transparent);border:1px solid var(--line)">Remove</button>
              </div>
            </div>
          </div>
        `
      }).join('')

      const subtotal = Number(cart.subtotal || 0)
      sumEl.innerHTML = `
        <div style="display:flex;justify-content:space-between"><span class="le-muted">Subtotal</span><span style="font-weight:850">₹${subtotal.toFixed(0)}</span></div>
        <div style="display:flex;justify-content:space-between"><span class="le-muted">Tip</span><span style="font-weight:850">₹${Number(cart.cart?.tip_amount || 0).toFixed(0)}</span></div>
        <div style="border-top:1px solid var(--line);padding-top:10px;display:flex;justify-content:space-between"><span style="font-weight:900">Total (est.)</span><span style="font-weight:900">₹${subtotal.toFixed(0)}</span></div>
      `
      stickyTotal.textContent = '₹' + subtotal.toFixed(0)
      stickyMeta.textContent = items.length + ' items'
    }

    const load = async () => {
      const res = await window.leApi('/api/v1/cart')
      if (!res.ok) {
        hintEl.innerHTML = `Login required for cart. <a href="/login" style="text-decoration:underline">Go to login</a>.`
        itemsEl.innerHTML = '<div class="le-muted">No cart loaded.</div>'
        return
      }
      hintEl.textContent = ''
      render(res.data.data)
    }

    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('[data-action="qty"],[data-action="remove"]')
      if (!btn) return
      const id = Number(btn.dataset.id)
      if (!id) return
      if (btn.dataset.action === 'remove') {
        await window.leApi('/api/v1/cart/items/' + id, { method: 'DELETE' })
        load()
        return
      }
      const delta = Number(btn.dataset.delta || 0)
      const res = await window.leApi('/api/v1/cart')
      if (!res.ok) return
      const items = res.data.data.items || []
      const item = items.find((x) => Number(x.id) === id)
      if (!item) return
      const nextQty = Math.max(0, Number(item.quantity) + delta)
      if (nextQty === 0) {
        await window.leApi('/api/v1/cart/items/' + id, { method: 'DELETE' })
      } else {
        await window.leApi('/api/v1/cart/items/' + id, { method: 'PATCH', body: JSON.stringify({ quantity: nextQty }) })
      }
      load()
    })

    load()
  })()
</script>
