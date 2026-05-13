<?php
declare(strict_types=1);

$appName = $appName ?? 'Love Eats';
?>
<header class="le-topbar">
  <div class="le-topbar__inner">
    <a class="le-brand" href="/home">
      <span class="le-brand__mark">LE</span>
      <span class="le-brand__name"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></span>
    </a>
    <nav class="le-nav">
      <a class="le-nav__link" href="/restaurants">Restaurants</a>
      <a class="le-nav__link" href="/reels">Reels</a>
      <a class="le-nav__link" href="/cart">Cart</a>
      <a class="le-nav__link" href="/login">Login</a>
    </nav>
    <button class="le-iconbtn" type="button" data-action="toggle-theme" aria-label="Toggle theme">
      <span class="le-iconbtn__dot"></span>
    </button>
  </div>
</header>
