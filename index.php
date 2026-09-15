<?php
require "config/database.php";
require "includes/image_helper.php";
// Fetch a few available food items for the public menu preview
$preview = $conn->query("SELECT f.name, f.description, f.price, f.image, c.name AS category
    FROM foods f JOIN categories c ON c.id = f.category_id
    WHERE f.available = 1 ORDER BY f.id DESC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Aura Bistro — Online Restaurant</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ── NAV ── -->
<header class="pub-nav">
    <div class="logo">● Aura <span>Bistro</span></div>
    <nav>
        <a href="index.php" class="active">Home</a>
        <a href="index.php#menu">Menu</a>
        <a href="index.php#reserve">Reservations</a>
        <a href="login.php">Login</a>
        <a href="register.php" class="btn btn-sm" style="margin-left:16px">Book a Table</a>
    </nav>
</header>

<!-- ── HERO ── -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-tag">Summer 2026 Experience</div>
        <h1>Where culinary<br><em>sophistication</em> meets<br>warm hospitality</h1>
        <p>Thoughtfully curated, locally sourced, beautifully served. Book a table or order online and experience dining redefined.</p>
        <div class="hero-btns">
            <a href="register.php" class="btn">Reserve a Table</a>
            <a href="login.php" class="btn btn-outline">Order Online</a>
        </div>
    </div>
</section>

<!-- ── ABOUT ── -->
<section class="section">
    <div class="grid-2" style="align-items:center;max-width:1100px;margin:0 auto;">
        <div>
            <div class="hero-tag" style="margin-bottom:16px">About Us</div>
            <h2 style="font-size:32px;font-weight:700;line-height:1.25;margin-bottom:16px">
                Thoughtfully curated,<br>locally sourced,<br><span class="text-gold">beautifully served</span>
            </h2>
            <p style="color:var(--muted);font-size:15px;line-height:1.8;margin-bottom:20px">
                At Aura Bistro, every dish tells a story. Our chefs craft each plate using seasonal, locally sourced ingredients that celebrate the richness of regional flavors. Whether you're joining us for a quiet dinner or a festive celebration, we promise an experience that stays with you long after the last bite.
            </p>
            <a href="register.php" class="btn btn-outline">Create an Account</a>
        </div>
        <div style="text-align:center;font-size:120px;opacity:.6">🍽️</div>
    </div>
</section>

<!-- ── FEATURES ── -->
<section class="section section-dark" id="features">
    <div class="section-title">
        <div class="eyebrow">Why Choose Us</div>
        <h2>Indulge in all dimensions</h2>
        <p>From fine dining to easy online ordering, we make every experience effortless.</p>
    </div>
    <div class="grid-3" style="max-width:1100px;margin:0 auto">
        <div class="card" style="text-align:center;padding:32px 24px">
            <div style="font-size:44px;margin-bottom:16px">🍴</div>
            <h3 style="color:var(--gold);margin-bottom:10px">Fine Dining</h3>
            <p style="color:var(--muted);font-size:14px">Elegant atmosphere with impeccable service, crafted for memorable moments.</p>
        </div>
        <div class="card" style="text-align:center;padding:32px 24px">
            <div style="font-size:44px;margin-bottom:16px">📱</div>
            <h3 style="color:var(--gold);margin-bottom:10px">Online Ordering</h3>
            <p style="color:var(--muted);font-size:14px">Browse our menu, add to cart, and place orders from the comfort of your home.</p>
        </div>
        <div class="card" style="text-align:center;padding:32px 24px">
            <div style="font-size:44px;margin-bottom:16px">📝</div>
            <h3 style="color:var(--gold);margin-bottom:10px">Table Reservation</h3>
            <p style="color:var(--muted);font-size:14px">Book your table in seconds. We'll have everything ready when you arrive.</p>
        </div>
        <div class="card" style="text-align:center;padding:32px 24px">
            <div style="font-size:44px;margin-bottom:16px">⏱️</div>
            <h3 style="color:var(--gold);margin-bottom:10px">Real-time Tracking</h3>
            <p style="color:var(--muted);font-size:14px">Track your order status in real time from kitchen to your table.</p>
        </div>
        <div class="card" style="text-align:center;padding:32px 24px">
            <div style="font-size:44px;margin-bottom:16px">⭐️</div>
            <h3 style="color:var(--gold);margin-bottom:10px">Ratings & Reviews</h3>
            <p style="color:var(--muted);font-size:14px">Share your experience and help us keep our quality at its finest.</p>
        </div>
        <div class="card" style="text-align:center;padding:32px 24px">
            <div style="font-size:44px;margin-bottom:16px">🧾</div>
            <h3 style="color:var(--gold);margin-bottom:10px">Digital Invoices</h3>
            <p style="color:var(--muted);font-size:14px">Instant e-invoices, payment records, and voucher support — paperless and precise.</p>
        </div>
    </div>
</section>

<!-- ── MENU PREVIEW ── -->
<section class="section" id="menu">
    <div class="section-title">
        <div class="eyebrow">Our Curated Menu</div>
        <h2>Popular Selections</h2>
        <p>A taste of what awaits you. Login to place an order.</p>
    </div>
    <div class="food-grid" style="max-width:1100px;margin:0 auto 32px">
        <?php while ($f = $preview->fetch_assoc()): ?>
        <div class="food-card">
            <div class="food-img">
                <img src="<?= htmlspecialchars(food_image($f)) ?>" alt="<?= htmlspecialchars($f['name']) ?>" loading="lazy">
            </div>
            <div class="food-body">
                <div class="food-name"><?= htmlspecialchars($f['name']) ?></div>
                <div class="food-desc"><?= htmlspecialchars($f['description'] ?: 'Freshly prepared with premium ingredients.') ?></div>
                <div class="food-footer">
                    <span class="food-price">৳<?= number_format($f['price'], 2) ?></span>
                    <a href="login.php" class="btn btn-sm">Order Now</a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <div class="text-center">
        <a href="login.php" class="btn">View Full Menu</a>
    </div>
</section>

<!-- ── QUOTE ── -->
<section class="section section-dark" style="text-align:center;padding:60px 6%">
    <p style="font-size:22px;font-style:italic;color:var(--text);max-width:640px;margin:0 auto;line-height:1.6">
        "Absolutely masterpiece of hotel dining. The filet mignon literally melted in my mouth, and the twilight cocktails on the rooftop offer an unparalleled view of the city skyline. Unrivaled hospitality."
    </p>
    <p style="color:var(--gold);margin-top:20px;font-size:14px;letter-spacing:1px">— Victoria Sterling, Verified Guest</p>
</section>

<!-- ── RESERVATION CTA ── -->
<section class="section" id="reserve" style="padding:72px 6%">
    <div style="max-width:700px;margin:0 auto;text-align:center">
        <div class="hero-tag" style="margin-bottom:16px">Reserve a Table</div>
        <h2 style="font-size:34px;font-weight:700;margin-bottom:14px">Plan your perfect evening</h2>
        <p style="color:var(--muted);margin-bottom:30px">We'll hold your preferred table. Arrive, relax, and let us take care of the rest.</p>
        <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:32px">
            <p style="color:var(--muted);margin-bottom:20px">Create a free account to book a table, place orders, and track your dining experience.</p>
            <div class="flex-center gap-12" style="justify-content:center;flex-wrap:wrap">
                <a href="register.php" class="btn">Create Account</a>
                <a href="login.php" class="btn btn-outline">Already a Member? Login</a>
            </div>
        </div>
    </div>
</section>

<!-- ── FOOTER ── -->
<footer class="pub-footer">
    <div>
        <div class="pf-logo">● Aura Bistro</div>
        <p>Developing upscale hotel dining through fresh regional ingredients, brilliant culinary mastery, and a warm casual ambiance.</p>
    </div>
    <div>
        <h4>Hours</h4>
        <p>Breakfast: 7:00 AM - 11:00 AM</p>
        <p>Lunch & Dinner: 12:00 PM - 10:00 PM</p>
        <p>Bar/Fine Lounge: open until 1:00 AM</p>
    </div>
    <div>
        <h4>Location & Contact</h4>
        <p>Grand Horizon Hotel, 4th Floor</p>
        <p>137 Skyline Drive, Downtown</p>
        <p>reservations@aurabistro.com</p>
        <p>+1 (888) 820-0001</p>
    </div>
    <div>
        <h4>Navigate</h4>
        <a href="index.php">Home</a>
        <a href="index.php#menu">Menu</a>
        <a href="index.php#reserve">Reservations</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    </div>
</footer>
<div class="pub-footer-bottom">
    © <?= date('Y') ?> Aura Bistro. All rights reserved. — AIUB CSC 3215 Group 08
</div>

<script src="assets/js/script.js"></script>
</body>
</html>
