<?php
require "../config/database.php";
require "../config/auth.php";
require_role("customer");

$uid     = (int)$_SESSION['user']['id'];
$success = "";
$error   = "";

// Submit / update review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $food_id = (int)$_POST['food_id'];
    $rating  = max(1, min(5, (int)$_POST['rating']));
    $comment = trim($_POST['comment']);

    if (!$food_id) {
        $error = "Please select a food item.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO reviews(customer_id,food_id,rating,review)
             VALUES(?,?,?,?)
             ON DUPLICATE KEY UPDATE rating=VALUES(rating), review=VALUES(review)"
        );
        $stmt->bind_param("iiis", $uid, $food_id, $rating, $comment);
        $stmt->execute();
        $success = "Review saved successfully!";
    }
}

// Delete review
if (isset($_GET['delete'])) {
    $rid  = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id=? AND customer_id=?");
    $stmt->bind_param("ii", $rid, $uid);
    $stmt->execute();
    header("Location: reviews.php?msg=deleted"); exit;
}

$msg = $_GET['msg'] ?? '';

// Only foods the customer has ordered (to keep it authentic)
$ordered_foods = $conn->query(
    "SELECT DISTINCT f.id, f.name, c.name AS category
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     JOIN foods f ON f.id = oi.food_id
     JOIN categories c ON c.id = f.category_id
     WHERE o.customer_id = $uid
     ORDER BY f.name"
);
$ordered = [];
while ($f = $ordered_foods->fetch_assoc()) $ordered[] = $f;

// My existing reviews
$my_reviews = $conn->query(
    "SELECT r.id, r.rating, r.review, r.created_at, f.name AS food_name, c.name AS category
     FROM reviews r
     JOIN foods f ON f.id = r.food_id
     JOIN categories c ON c.id = f.category_id
     WHERE r.customer_id = $uid
     ORDER BY r.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Food Reviews — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_customer.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>Food Rating &amp; Reviews</h1>
            <span class="badge-role">Customer</span>
        </div>
        <div class="content">

            <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($msg === 'deleted'): ?><div class="alert alert-info">ℹ️ Review deleted.</div><?php endif; ?>

            <div class="grid-2 mb-28" style="align-items:flex-start">
                <!-- Submit form -->
                <div class="card">
                    <h2>Submit a Review</h2>
                    <?php if (empty($ordered)): ?>
                    <div class="alert alert-info">You need to order food before you can leave a review.</div>
                    <a href="menu.php" class="btn btn-outline">Browse Menu</a>
                    <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="submit_review" value="1">
                        <div class="form-group">
                            <label>Select Food Item</label>
                            <select name="food_id" id="food_select" required>
                                <option value="">Choose a food item you ordered...</option>
                                <?php foreach ($ordered as $f): ?>
                                <option value="<?= $f['id'] ?>">
                                    <?= htmlspecialchars($f['name']) ?> (<?= htmlspecialchars($f['category']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Rating</label>
                            <div class="flex gap-8" id="starRating" style="margin-bottom:16px">
                                <?php for ($s = 5; $s >= 1; $s--): ?>
                                <label style="cursor:pointer;font-size:28px;text-transform:none;letter-spacing:0;margin-bottom:0;color:var(--border)" id="star_lbl_<?= $s ?>">
                                    <input type="radio" name="rating" value="<?= $s ?>" style="display:none" onchange="setStars(<?= $s ?>)" <?= $s===5?'checked':'' ?>>
                                    ★
                                </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Your Review</label>
                            <textarea name="comment" placeholder="Share your experience with this dish..."></textarea>
                        </div>
                        <button class="btn" style="width:100%;justify-content:center">Submit Review</button>
                    </form>
                    <?php endif; ?>
                </div>

                <!-- Overall stats -->
                <div class="card">
                    <h2>Your Review Stats</h2>
                    <?php
                    $stats = $conn->query("SELECT AVG(rating) avg, COUNT(*) total FROM reviews WHERE customer_id=$uid")->fetch_assoc();
                    $avg   = round($stats['avg'] ?? 0, 1);
                    $total = $stats['total'];
                    ?>
                    <div class="text-center" style="padding:20px 0">
                        <div style="font-size:56px;font-weight:700;color:var(--gold)"><?= $avg ?: '—' ?></div>
                        <div class="stars" style="font-size:28px">
                            <?php for ($s=1; $s<=5; $s++): ?>
                            <span style="color:<?= $s <= round($avg) ? 'var(--gold)' : 'var(--border)' ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <div style="color:var(--muted);margin-top:8px"><?= $total ?> review<?= $total!=1?'s':'' ?> submitted</div>
                    </div>
                    <div class="divider"></div>
                    <p style="color:var(--muted);font-size:14px;text-align:center">
                        Your reviews help us improve the quality of every dish we serve.
                    </p>
                </div>
            </div>

            <!-- My Reviews -->
            <div class="card">
                <div class="section-head"><h2>My Reviews</h2></div>
                <?php if ($my_reviews->num_rows === 0): ?>
                <p style="color:var(--muted);padding:16px 0">No reviews yet.</p>
                <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:12px">
                    <?php while ($r = $my_reviews->fetch_assoc()): ?>
                    <div style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px">
                        <div class="flex-between mb-8">
                            <div>
                                <strong><?= htmlspecialchars($r['food_name']) ?></strong>
                                <span style="font-size:12px;background:var(--bg2);border:1px solid var(--border);border-radius:20px;padding:2px 8px;margin-left:8px;color:var(--muted)">
                                    <?= htmlspecialchars($r['category']) ?>
                                </span>
                            </div>
                            <a href="reviews.php?delete=<?= $r['id'] ?>"
                               onclick="return confirm('Delete this review?')"
                               class="btn btn-sm btn-danger">🗑 Delete</a>
                        </div>
                        <div class="stars" style="font-size:20px;margin-bottom:8px">
                            <?php for ($s=1; $s<=5; $s++): ?>
                            <span style="color:<?= $s<=$r['rating'] ? 'var(--gold)' : 'var(--border)' ?>">★</span>
                            <?php endfor; ?>
                            <span style="color:var(--muted);font-size:13px;margin-left:6px"><?= $r['rating'] ?>/5</span>
                        </div>
                        <?php if ($r['review']): ?>
                        <p style="color:var(--muted);font-size:14px;line-height:1.6"><?= htmlspecialchars($r['review']) ?></p>
                        <?php endif; ?>
                        <div style="color:var(--muted);font-size:12px;margin-top:8px"><?= date('M d, Y', strtotime($r['created_at'])) ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
<script src="../assets/js/script.js"></script>
<script>
function setStars(n) {
    for (let i = 1; i <= 5; i++) {
        const lbl = document.getElementById('star_lbl_' + i);
        lbl.style.color = i <= n ? 'var(--gold)' : 'var(--border)';
    }
}
// Init with 5 selected
document.addEventListener('DOMContentLoaded', () => setStars(5));
document.querySelectorAll('#starRating input').forEach(inp => {
    inp.addEventListener('change', () => setStars(inp.value));
});
</script>
</body>
</html>
