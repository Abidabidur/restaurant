<?php
/**
 * Resolves a food row to its picture in assets/images/foods/.
 *
 * $prefix is the path back to the project root from the calling page:
 *   ""     for index.php (project root)
 *   "../"  for pages inside admin/, customer/, manager/, kitchen/
 *
 * Order of preference:
 *   1. the filename stored in the foods.image column
 *   2. a file named after the dish, e.g. "Beef Pizza" -> beef-pizza.jpg
 *   3. placeholder.svg
 */
function food_image($food, $prefix = '')
{
    $dir = __DIR__ . '/../assets/images/foods/';

    // 1. Whatever the database says (uploaded by admin or seeded)
    if (!empty($food['image'])) {
        $file = basename($food['image']);
        if (is_file($dir . $file)) {
            return $prefix . 'assets/images/foods/' . rawurlencode($file);
        }
    }

    // 2. Fall back to a file named after the dish
    $slug = food_slug($food['name'] ?? '');
    if ($slug !== '') {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            if (is_file($dir . $slug . '.' . $ext)) {
                return $prefix . 'assets/images/foods/' . $slug . '.' . $ext;
            }
        }
    }

    // 3. Nothing matched
    return $prefix . 'assets/images/foods/placeholder.svg';
}

/** "Spaghetti Carbonara" -> "spaghetti-carbonara" */
function food_slug($name)
{
    $s = strtolower(trim($name));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

/**
 * Validate and store an uploaded food picture in assets/images/foods/.
 * Returns [filename, error]. $filename is "" when nothing was uploaded
 * or validation failed; check $error to tell those two cases apart.
 */
function save_food_image($file)
{
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ["", ""];                       // nothing uploaded — not an error
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ["", "Image upload failed. Please try again."];
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return ["", "Image is too large (max 2 MB)."];
    }

    // Check the real content of the file, not just the name the browser sent —
    // a file can be renamed "photo.jpg" without actually being a JPEG.
    $info = @getimagesize($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    if (!$info || !isset($allowed[$info['mime']])) {
        return ["", "Only JPG, PNG, GIF or WEBP images are allowed."];
    }

    $dir = __DIR__ . '/../assets/images/foods/';
    $filename = 'food_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$info['mime']];
    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        return ["", "Could not save the uploaded image."];
    }
    return [$filename, ""];
}

/**
 * Delete a food picture from disk — but only ones this app generated itself
 * (food_<timestamp>_<random>.ext). The bundled starter photos are named
 * after the dish (e.g. beef-pizza.jpg) and are left alone, since other
 * food items may still fall back to them.
 */
function delete_food_image($filename)
{
    if (!$filename || !preg_match('/^food_\d+_[0-9a-f]+\.(jpg|jpeg|png|gif|webp)$/i', $filename)) {
        return;
    }
    $path = __DIR__ . '/../assets/images/foods/' . basename($filename);
    if (is_file($path)) @unlink($path);
}
