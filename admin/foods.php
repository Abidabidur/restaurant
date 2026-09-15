<?php
require "../config/database.php";
require "../config/auth.php";
<<<<<<< HEAD
require "../includes/image_helper.php";
=======
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
require_role("admin");

$success = "";
$error   = "";

// Add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $cname = trim($_POST['cat_name']);
    if ($cname) {
        $s = $conn->prepare("INSERT INTO categories(name) VALUES(?)");
        $s->bind_param("s", $cname); $s->execute();
        header("Location: foods.php?msg=cat_added"); exit;
    }
}

// Delete category
if (isset($_GET['del_cat'])) {
    $cid  = (int)$_GET['del_cat'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    header("Location: foods.php?msg=cat_deleted"); exit;
}

// Add food
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food'])) {
    $cid   = (int)$_POST['category_id'];
    $name  = trim($_POST['name']);
    $desc  = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $avail = isset($_POST['available']) ? 1 : 0;
<<<<<<< HEAD

    [$image, $img_err] = save_food_image($_FILES['image'] ?? null);

    if (empty($name) || $price <= 0 || $cid <= 0) {
        $error = "Name, category and price are required.";
    } elseif ($img_err) {
        $error = $img_err;
    } else {
=======
    $image = null;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/foods/';
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $new_filename = uniqid('food_') . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image = $new_filename;
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Invalid image format. Allowed: JPG, JPEG, PNG, GIF, WEBP.";
        }
    }

    if (empty($name) || $price <= 0 || $cid <= 0) {
        $error = "Name, category and price are required.";
    } elseif (!$error) {
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
        $s = $conn->prepare("INSERT INTO foods(category_id,name,description,price,image,available) VALUES(?,?,?,?,?,?)");
        $s->bind_param("issdsi", $cid, $name, $desc, $price, $image, $avail);
        $s->execute();
        header("Location: foods.php?msg=food_added"); exit;
    }
}

// Toggle availability
if (isset($_GET['toggle_food'])) {
    $fid  = (int)$_GET['toggle_food'];
    $stmt = $conn->prepare("UPDATE foods SET available = 1 - available WHERE id=?");
    $stmt->bind_param("i", $fid);
    $stmt->execute();
    header("Location: foods.php?msg=updated"); exit;
}

// Delete food
if (isset($_GET['del_food'])) {
    $fid  = (int)$_GET['del_food'];
<<<<<<< HEAD

    $old = $conn->prepare("SELECT image FROM foods WHERE id=? LIMIT 1");
    $old->bind_param("i", $fid); $old->execute();
    if ($row = $old->get_result()->fetch_assoc()) {
        delete_food_image($row['image']);
    }

=======
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
    $stmt = $conn->prepare("DELETE FROM foods WHERE id=?");
    $stmt->bind_param("i", $fid);
    $stmt->execute();
    header("Location: foods.php?msg=food_deleted"); exit;
}

// Edit food (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_food'])) {
    $fid   = (int)$_POST['fid'];
    $cid   = (int)$_POST['category_id'];
    $name  = trim($_POST['name']);
    $desc  = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $avail = isset($_POST['available']) ? 1 : 0;
<<<<<<< HEAD
=======
    $image = null;
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68

    // Get current image
    $current = $conn->prepare("SELECT image FROM foods WHERE id=?");
    $current->bind_param("i", $fid);
    $current->execute();
    $current_img = $current->get_result()->fetch_assoc()['image'];

<<<<<<< HEAD
    [$new_image, $img_err] = save_food_image($_FILES['image'] ?? null);

    if ($img_err) {
        $error = $img_err;
    } else {
        if ($new_image !== "") {
            delete_food_image($current_img);   // replace: remove the old file
            $image = $new_image;
        } else {
            $image = $current_img;             // no new upload — keep existing
        }
=======
    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/foods/';
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $new_filename = uniqid('food_') . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image = $new_filename;
                // Delete old image if exists
                if ($current_img && file_exists($upload_dir . $current_img)) {
                    unlink($upload_dir . $current_img);
                }
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Invalid image format. Allowed: JPG, JPEG, PNG, GIF, WEBP.";
        }
    } else {
        // Keep existing image if no new upload
        $image = $current_img;
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
    }

    if (!$error) {
        $s = $conn->prepare("UPDATE foods SET category_id=?,name=?,description=?,price=?,image=?,available=? WHERE id=?");
        $s->bind_param("issdsii", $cid, $name, $desc, $price, $image, $avail, $fid);
        $s->execute();
        header("Location: foods.php?msg=updated"); exit;
    }
}

if (isset($_GET['msg'])) {
    $msgs = ['cat_added'=>'Category added.','cat_deleted'=>'Category deleted.','food_added'=>'Food item added.','food_deleted'=>'Food item deleted.','updated'=>'Updated successfully.'];
    $success = $msgs[$_GET['msg']] ?? '';
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$cat_list   = [];
$tmp = $conn->query("SELECT * FROM categories ORDER BY name");
while ($c = $tmp->fetch_assoc()) $cat_list[] = $c;

$filter_cat = (int)($_GET['cat'] ?? 0);
if ($filter_cat) {
    $fs = $conn->prepare("SELECT f.*,c.name AS category FROM foods f JOIN categories c ON c.id=f.category_id WHERE f.category_id=? ORDER BY f.id DESC");
    $fs->bind_param("i", $filter_cat); $fs->execute();
    $foods = $fs->get_result();
} else {
    $foods = $conn->query("SELECT f.*,c.name AS category FROM foods f JOIN categories c ON c.id=f.category_id ORDER BY f.id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Food Menu — Aura Bistro</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrap">
    <?php require "../includes/sidebar_admin.php"; ?>
    <div class="main">
        <div class="top-bar">
            <h1>Food Menu Management</h1>
            <span class="badge-role">Administrator</span>
        </div>
        <div class="content">

            <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="grid-2 mb-20">
                <!-- Add Category -->
                <div class="card">
                    <h2>Manage Categories</h2>
                    <form method="POST" class="flex gap-8" style="align-items:flex-end">
                        <input type="hidden" name="add_category" value="1">
                        <div class="form-group" style="flex:1;margin-bottom:0">
                            <label>Category Name</label>
                            <input name="cat_name" placeholder="e.g. Pasta" required style="margin-bottom:0">
                        </div>
                        <button class="btn" style="flex-shrink:0">Add</button>
                    </form>
                    <div class="divider"></div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px">
                        <?php foreach ($cat_list as $c): ?>
                        <div style="display:flex;align-items:center;gap:6px;background:var(--bg3);border:1px solid var(--border);border-radius:20px;padding:5px 12px;font-size:13px">
                            <?= htmlspecialchars($c['name']) ?>
                            <a href="foods.php?del_cat=<?= $c['id'] ?>"
                               onclick="return confirm('Delete category and all its food items?')"
                               style="color:var(--danger);font-weight:700;font-size:16px;line-height:1">×</a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Add Food -->
                <div class="card">
                    <h2>Add Food Item</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="add_food" value="1">
                        <div class="form-group"><label>Food Name</label><input name="name" placeholder="e.g. Chicken Pizza" required></div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category_id" required>
                                    <option value="">Select category</option>
                                    <?php foreach ($cat_list as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Price (৳)</label>
                                <input type="number" name="price" step="0.01" min="0" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" placeholder="Short description..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Food Image</label>
                            <input type="file" name="image" accept="image/*" style="padding:8px">
                            <small style="color:var(--muted);font-size:12px">Optional. JPG, PNG, GIF, WEBP. Max 5MB.</small>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
                            <input type="checkbox" name="available" id="avail" checked style="width:auto;margin:0">
                            <label for="avail" style="text-transform:none;letter-spacing:0;color:var(--text)">Available on menu</label>
                        </div>
                        <button class="btn">➕ Add Food Item</button>
                    </form>
                </div>
            </div>

            <!-- Category filter -->
            <div class="cat-tabs mb-20">
                <a href="foods.php" class="cat-tab <?= !$filter_cat ? 'active':'' ?>">All</a>
                <?php foreach ($cat_list as $c): ?>
                <a href="foods.php?cat=<?= $c['id'] ?>" class="cat-tab <?= $filter_cat==$c['id'] ? 'active':'' ?>">
                    <?= htmlspecialchars($c['name']) ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Foods Table -->
            <div class="card">
                <div class="section-head"><h2>All Food Items</h2></div>
                <div class="tbl-wrap">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php while ($f = $foods->fetch_assoc()): ?>
                            <tr>
                                <td style="color:var(--muted)">#<?= $f['id'] ?></td>
                                <td>
<<<<<<< HEAD
                                    <img src="<?= htmlspecialchars(food_image($f, '../')) ?>" alt="<?= htmlspecialchars($f['name']) ?>"
                                         style="width:50px;height:50px;object-fit:cover;border-radius:6px;border:1px solid var(--border)">
=======
                                    <?php 
                                    $img_path = $f['image'] ? '../assets/images/foods/' . htmlspecialchars($f['image']) : null;
                                    if ($img_path && file_exists($img_path)):
                                    ?>
                                        <img src="<?= $img_path ?>" alt="<?= htmlspecialchars($f['name']) ?>" style="width:50px;height:50px;object-fit:cover;border-radius:6px">
                                    <?php else: ?>
                                        <div style="width:50px;height:50px;background:var(--bg3);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:24px">🍽️</div>
                                    <?php endif; ?>
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($f['name']) ?></strong>
                                    <?php if ($f['description']): ?>
                                    <div style="font-size:12px;color:var(--muted)"><?= htmlspecialchars(substr($f['description'],0,60)) ?>...</div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-info" style="font-size:11px"><?= htmlspecialchars($f['category']) ?></span></td>
                                <td class="text-gold">৳<?= number_format($f['price'],2) ?></td>
                                <td>
                                    <?php if ($f['available']): ?>
                                        <span class="badge badge-available">Available</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Unavailable</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex gap-8">
                                        <button class="btn btn-sm btn-outline"
<<<<<<< HEAD
                                            onclick="openFoodEdit(<?= $f['id'] ?>, <?= $f['category_id'] ?>, '<?= htmlspecialchars(addslashes($f['name'])) ?>', '<?= htmlspecialchars(addslashes($f['description'])) ?>', <?= $f['price'] ?>, <?= $f['available'] ?>, '<?= htmlspecialchars(addslashes(food_image($f, '../'))) ?>')">
=======
                                            onclick="openFoodEdit(<?= $f['id'] ?>, <?= $f['category_id'] ?>, '<?= htmlspecialchars(addslashes($f['name'])) ?>', '<?= htmlspecialchars(addslashes($f['description'])) ?>', <?= $f['price'] ?>, <?= $f['available'] ?>, '<?= htmlspecialchars(addslashes($f['image'] ?? '')) ?>')">
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
                                            ✏️ Edit
                                        </button>
                                        <a class="btn btn-sm btn-dark" href="foods.php?toggle_food=<?= $f['id'] ?>">
                                            <?= $f['available'] ? '🚫 Hide' : '✅ Show' ?>
                                        </a>
                                        <a class="btn btn-sm btn-danger"
                                           href="foods.php?del_food=<?= $f['id'] ?>"
                                           onclick="return confirm('Delete this food item?')">🗑 Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Edit Food Modal -->
<div id="foodEditModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center">
    <div class="card" style="width:480px;max-width:95vw">
        <div class="section-head"><h2>Edit Food Item</h2>
            <button onclick="document.getElementById('foodEditModal').style.display='none'"
                    style="background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer">✕</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="edit_food" value="1">
            <input type="hidden" name="fid" id="fe_id">
            <div class="form-group"><label>Food Name</label><input name="name" id="fe_name" required></div>
            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" id="fe_cat">
                        <?php foreach ($cat_list as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Price (৳)</label>
                    <input type="number" name="price" id="fe_price" step="0.01" min="0" required>
                </div>
            </div>
            <div class="form-group"><label>Description</label><textarea name="description" id="fe_desc"></textarea></div>
            <div class="form-group">
                <label>Current Image</label>
                <div id="fe_current_img" style="margin-bottom:10px"></div>
                <label>Upload New Image (optional)</label>
                <input type="file" name="image" accept="image/*" style="padding:8px">
                <small style="color:var(--muted);font-size:12px">JPG, PNG, GIF, WEBP. Max 5MB. Leave empty to keep current image.</small>
            </div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
                <input type="checkbox" name="available" id="fe_avail" style="width:auto;margin:0">
                <label for="fe_avail" style="text-transform:none;letter-spacing:0;color:var(--text)">Available on menu</label>
            </div>
            <div class="flex gap-8">
                <button class="btn">Save Changes</button>
                <button type="button" class="btn btn-dark"
                    onclick="document.getElementById('foodEditModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/script.js"></script>
<script>
<<<<<<< HEAD
function openFoodEdit(id, catId, name, desc, price, avail, imageUrl) {
=======
function openFoodEdit(id, catId, name, desc, price, avail, image) {
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
    document.getElementById('fe_id').value    = id;
    document.getElementById('fe_cat').value   = catId;
    document.getElementById('fe_name').value  = name;
    document.getElementById('fe_desc').value  = desc;
    document.getElementById('fe_price').value = price;
    document.getElementById('fe_avail').checked = avail == 1;
    
    // Display current image
    const imgContainer = document.getElementById('fe_current_img');
<<<<<<< HEAD
    imgContainer.innerHTML = '<img src="' + imageUrl + '" alt="Current picture" style="width:100px;height:100px;object-fit:cover;border-radius:8px;border:2px solid var(--border)">';
=======
    if (image) {
        const imgPath = '../assets/images/foods/' + image;
        imgContainer.innerHTML = '<img src="' + imgPath + '" alt="Current image" style="width:100px;height:100px;object-fit:cover;border-radius:8px;border:2px solid var(--border)">';
    } else {
        imgContainer.innerHTML = '<div style="width:100px;height:100px;background:var(--bg3);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:40px;border:2px solid var(--border)">🍽️</div>';
    }
>>>>>>> 21c6659bf87fc235934203ec109b34ccacceed68
    
    document.getElementById('foodEditModal').style.display = 'flex';
}
</script>
</body>
</html>
