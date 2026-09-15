<aside class="sidebar" id="sidebar">
    <div class="sb-logo">● Aura Bistro<small>Kitchen Staff</small></div>
    <nav>
        <a href="<?= BASE_URL ?>kitchen/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <span class="ico">📊</span> Dashboard
        </a>
        <a href="<?= BASE_URL ?>kitchen/orders.php" class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">
            <span class="ico">🍳</span> Orders Queue
        </a>
        <a href="<?= BASE_URL ?>kitchen/ingredients.php" class="<?= basename($_SERVER['PHP_SELF']) === 'ingredients.php' ? 'active' : '' ?>">
            <span class="ico">🧂</span> Ingredients
        </a>
        <a href="<?= BASE_URL ?>kitchen/profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
            <span class="ico">👤</span> My Profile
        </a>
    </nav>
    <div class="sb-footer">
        <div class="user-name"><?= htmlspecialchars($_SESSION['user']['name']) ?></div>
        <div><?= htmlspecialchars($_SESSION['user']['email']) ?></div>
        <a href="<?= BASE_URL ?>logout.php">⬅ Logout</a>
    </div>
</aside>
