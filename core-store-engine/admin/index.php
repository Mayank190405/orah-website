<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../engine/JsonStorage.php';
require_once __DIR__ . '/../engine/ThemeEngine.php';

use CoreStore\Engine\JsonStorage;

require_auth();

// Storage instances
$storage = new JsonStorage(__DIR__ . '/../data/catalog.json');
$catalog = $storage->read() ?: [];

$catStorage = new JsonStorage(__DIR__ . '/../data/categories.json');
$categoriesData = $catStorage->read() ?: [];

// Sort categories by order
usort($categoriesData, function($a, $b) {
    return ($a['order'] ?? 99) <=> ($b['order'] ?? 99);
});

$settingsStorage = new JsonStorage(__DIR__ . '/../data/settings.json');
$settings = $settingsStorage->read() ?: [];
$brand = $settings['brand'] ?? ['text' => 'Orah House'];

$message = null;
$messageType = 'success';

// Reusable Image Upload Handler
function saveUploadedImage($fileKey, $prefix = 'img') {
    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$fileKey];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($ext, $allowed)) {
            $uploadDir = __DIR__ . '/../public/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            $targetPath = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                return 'uploads/' . $filename;
            }
        }
    }
    return null;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // -------------------------------------------------------------------------
    // DISH / MENU ITEM ACTIONS
    // -------------------------------------------------------------------------
    if ($action === 'add' || $action === 'edit') {
        $id = ($action === 'edit') ? trim($_POST['id'] ?? '') : 'item_' . time() . '_' . bin2hex(random_bytes(3));
        $name = trim($_POST['name'] ?? '');
        $badge = trim($_POST['badge'] ?? 'General');
        $price = trim($_POST['price'] ?? '₹0');
        $description = trim($_POST['description'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $isVeg = isset($_POST['is_veg']);
        $featured = isset($_POST['featured']);
        $available = isset($_POST['available']);

        // Uploaded image
        $uploaded = saveUploadedImage('image_file', 'dish');
        if ($uploaded) {
            $imageUrl = $uploaded;
        }

        // Format price nicely
        if (!str_starts_with($price, '₹') && !str_starts_with($price, '$')) {
            $price = '₹' . $price;
        }

        // Parse Dynamic Custom Extra Fields
        $customFields = [];
        if (!empty($_POST['cf_name']) && is_array($_POST['cf_name'])) {
            foreach ($_POST['cf_name'] as $idx => $cfName) {
                $cfName = trim($cfName);
                $cfVal = trim($_POST['cf_value'][$idx] ?? '');
                if ($cfName !== '' || $cfVal !== '') {
                    $customFields[] = [
                        'name' => $cfName,
                        'value' => $cfVal
                    ];
                }
            }
        }

        if (empty($name)) {
            $message = 'Dish name is required.';
            $messageType = 'error';
        } else {
            $itemData = [
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'tagline' => $tagline,
                'price' => $price,
                'badge' => $badge,
                'is_veg' => $isVeg,
                'featured' => $featured,
                'available' => $available,
                'image' => $imageUrl ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=700&q=80',
                'custom_fields' => $customFields
            ];

            if ($action === 'add') {
                array_unshift($catalog, $itemData);
                $message = "Successfully added '{$name}' to the menu.";
            } else {
                foreach ($catalog as $i => $item) {
                    if (($item['id'] ?? '') === $id) {
                        if (empty($imageUrl) && !empty($item['image'])) {
                            $itemData['image'] = $item['image'];
                        }
                        $catalog[$i] = $itemData;
                        break;
                    }
                }
                $message = "Updated '{$name}' successfully.";
            }

            $storage->write($catalog);
        }
    } elseif ($action === 'delete') {
        $id = trim($_POST['id'] ?? '');
        $catalog = array_values(array_filter($catalog, function($item) use ($id) {
            return ($item['id'] ?? '') !== $id;
        }));
        $storage->write($catalog);
        $message = "Item removed from menu.";
    } elseif ($action === 'toggle_status') {
        $id = trim($_POST['id'] ?? '');
        $field = trim($_POST['field'] ?? '');
        foreach ($catalog as $i => $item) {
            if (($item['id'] ?? '') === $id) {
                if ($field === 'available') {
                    $catalog[$i]['available'] = !($item['available'] ?? true);
                } elseif ($field === 'featured') {
                    $catalog[$i]['featured'] = !($item['featured'] ?? false);
                }
                break;
            }
        }
        $storage->write($catalog);
        $message = "Status updated.";
    }

    // -------------------------------------------------------------------------
    // CATEGORY ACTIONS (CREATE, EDIT HEADERS/CURSIVE QUOTE, DELETE, TOGGLE)
    // -------------------------------------------------------------------------
    elseif ($action === 'category_save') {
        $catId = trim($_POST['cat_id'] ?? '');
        $catName = trim($_POST['cat_name'] ?? '');
        $oldCatName = trim($_POST['cat_old_name'] ?? '');
        $eyebrow = trim($_POST['cat_eyebrow'] ?? 'OUR SIGNATURES');
        $scriptQuote = trim($_POST['cat_script_quote'] ?? '');
        $subtitle = trim($_POST['cat_subtitle'] ?? '');
        $order = intval($_POST['cat_order'] ?? 99);
        $active = isset($_POST['cat_active']);
        $imageUrl = trim($_POST['cat_image_url'] ?? '');

        $uploaded = saveUploadedImage('cat_image_file', 'cat');
        if ($uploaded) {
            $imageUrl = $uploaded;
        }

        if (empty($catName)) {
            $message = 'Category name is required.';
            $messageType = 'error';
        } else {
            if (empty($catId)) {
                // New category
                $catId = 'cat_' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $catName)) . '_' . bin2hex(random_bytes(2));
                $newCat = [
                    'id' => $catId,
                    'name' => $catName,
                    'eyebrow' => $eyebrow,
                    'scriptQuote' => $scriptQuote,
                    'subtitle' => $subtitle,
                    'image' => $imageUrl ?: 'uploads/mozzarella_flatbread.jpg',
                    'order' => $order,
                    'active' => $active
                ];
                $categoriesData[] = $newCat;
                $message = "Created new category '{$catName}' successfully.";
            } else {
                // Edit existing category
                foreach ($categoriesData as $idx => $c) {
                    if (($c['id'] ?? '') === $catId) {
                        if (empty($imageUrl) && !empty($c['image'])) {
                            $imageUrl = $c['image'];
                        }
                        $categoriesData[$idx] = [
                            'id' => $catId,
                            'name' => $catName,
                            'eyebrow' => $eyebrow,
                            'scriptQuote' => $scriptQuote,
                            'subtitle' => $subtitle,
                            'image' => $imageUrl,
                            'order' => $order,
                            'active' => $active
                        ];
                        break;
                    }
                }

                // If renamed, update dishes with old badge
                if ($oldCatName && $oldCatName !== $catName) {
                    foreach ($catalog as $dIdx => $d) {
                        if (($d['badge'] ?? '') === $oldCatName) {
                            $catalog[$dIdx]['badge'] = $catName;
                        }
                    }
                    $storage->write($catalog);
                }

                $message = "Updated category '{$catName}' and its header text successfully.";
            }

            // Sort and save
            usort($categoriesData, function($a, $b) {
                return ($a['order'] ?? 99) <=> ($b['order'] ?? 99);
            });
            $catStorage->write($categoriesData);
        }
    } elseif ($action === 'category_delete') {
        $catId = trim($_POST['cat_id'] ?? '');
        $categoriesData = array_values(array_filter($categoriesData, function($c) use ($catId) {
            return ($c['id'] ?? '') !== $catId;
        }));
        $catStorage->write($categoriesData);
        $message = "Category removed successfully.";
    } elseif ($action === 'category_toggle') {
        $catId = trim($_POST['cat_id'] ?? '');
        foreach ($categoriesData as $idx => $c) {
            if (($c['id'] ?? '') === $catId) {
                $categoriesData[$idx]['active'] = !($c['active'] ?? true);
                break;
            }
        }
        $catStorage->write($categoriesData);
        $message = "Category visibility updated.";
    }
}

// Compute Statistics
$totalItems = count($catalog);
$distinctCategories = array_values(array_unique(array_filter(array_column($catalog, 'badge'))));
sort($distinctCategories);

// Category names from categories.json
$categoryNames = array_column($categoriesData, 'name');
foreach ($distinctCategories as $dc) {
    if (!in_array($dc, $categoryNames)) {
        $categoryNames[] = $dc;
    }
}

$featuredCount = count(array_filter($catalog, fn($i) => !empty($i['featured'])));
$availableCount = count(array_filter($catalog, fn($i) => ($i['available'] ?? true)));
$totalCategoriesCount = count($categoriesData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu & Category Console &bull; Orah House Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;600;700&family=Cormorant+Garamond:wght@400;600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- Header Navigation -->
    <header class="admin-header">
        <div class="admin-nav">
            <a href="index.php" class="brand-section">
                <img src="../public/assets/images/swans_only.png" alt="Orah Swans" class="admin-logo">
                <span class="brand-title">ORAH HOUSE</span>
                <span class="admin-badge">Admin Studio</span>
            </a>

            <div class="nav-actions">
                <a href="../public/menu.php" target="_blank" class="nav-link-btn" title="Open public live menu">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>View Public Menu</span>
                </a>
                <a href="../public/index.php" target="_blank" class="nav-link-btn">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
                    <span>Storefront</span>
                </a>
            </div>
        </div>
    </header>

    <main class="admin-container">

        <!-- Notification Toast -->
        <?php if ($message): ?>
            <div class="toast-banner <?= $messageType === 'error' ? 'error' : '' ?>">
                <span><?= htmlspecialchars($message) ?></span>
                <button type="button" onclick="this.parentElement.style.display='none'" style="background:none;border:none;cursor:pointer;font-weight:700;">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Console Section Switcher Tabs -->
        <div class="admin-tabs-bar">
            <div class="admin-tabs">
                <button type="button" class="admin-tab-btn active" id="tabBtnDishes" data-tab="dishesTabPanel">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8zM6 1v3M10 1v3M14 1v3"/></svg>
                    <span>Menu Dishes</span>
                    <span class="tab-badge"><?= $totalItems ?></span>
                </button>

                <button type="button" class="admin-tab-btn" id="tabBtnCategories" data-tab="categoriesTabPanel">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <span>Categories & Section Headers</span>
                    <span class="tab-badge"><?= $totalCategoriesCount ?></span>
                </button>
            </div>

            <div class="tab-action-wrap">
                <button type="button" class="btn-primary" id="openAddDishBtn">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>+ Add New Dish</span>
                </button>
                <button type="button" class="btn-primary" id="openAddCategoryBtn" style="display: none; background: #2b241e;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>+ Create New Category</span>
                </button>
            </div>
        </div>

        <!-- ===================================================================
             TAB 1: DISHES & MENU ITEMS
             =================================================================== -->
        <div class="admin-tab-panel active" id="dishesTabPanel">
            <!-- KPI Metrics -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-label">Total Menu Items</div>
                        <div class="stat-value"><?= $totalItems ?></div>
                    </div>
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-label">Categories</div>
                        <div class="stat-value"><?= count($distinctCategories) ?></div>
                    </div>
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-label">Active on Menu</div>
                        <div class="stat-value"><?= $availableCount ?></div>
                    </div>
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-label">Chef's Featured</div>
                        <div class="stat-value"><?= $featuredCount ?></div>
                    </div>
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                </div>
            </section>

            <!-- Search & Filter Controls -->
            <section class="control-bar">
                <div class="search-box">
                    <svg class="search-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="adminSearchInput" class="search-input" placeholder="Search dishes, descriptions, or prices...">
                </div>

                <div class="filter-pills" id="categoryPillContainer">
                    <button type="button" class="pill-btn active" data-category="ALL">All (<?= $totalItems ?>)</button>
                    <?php foreach ($distinctCategories as $cat): ?>
                        <?php $count = count(array_filter($catalog, fn($i) => ($i['badge'] ?? '') === $cat)); ?>
                        <button type="button" class="pill-btn" data-category="<?= htmlspecialchars($cat) ?>">
                            <?= htmlspecialchars($cat) ?> (<?= $count ?>)
                        </button>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Dishes Table -->
            <section class="items-container dishes-table-container">
                <table class="dishes-table" id="dishesTable">
                    <thead>
                        <tr>
                            <th>Dish / Item Details</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Dietary</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($catalog as $item): ?>
                            <?php 
                                $itemId = $item['id'] ?? '';
                                $itemName = $item['name'] ?? 'Untitled';
                                $itemBadge = $item['badge'] ?? 'General';
                                $itemPrice = $item['price'] ?? '₹0';
                                $itemDesc = $item['description'] ?? '';
                                $itemTagline = $item['tagline'] ?? '';
                                $itemImg = $item['image'] ?? 'assets/placeholder.jpg';
                                $isVeg = !empty($item['is_veg']);
                                $isFeatured = !empty($item['featured']);
                                $isAvailable = $item['available'] ?? true;
                                $cFields = $item['custom_fields'] ?? [];

                                $resolvedImg = str_starts_with($itemImg, 'http') ? $itemImg : '../public/' . ltrim($itemImg, '/');
                            ?>
                            <tr class="dish-row" 
                                data-id="<?= htmlspecialchars($itemId) ?>"
                                data-name="<?= htmlspecialchars(strtolower($itemName)) ?>"
                                data-desc="<?= htmlspecialchars(strtolower($itemDesc)) ?>"
                                data-category="<?= htmlspecialchars($itemBadge) ?>"
                                data-price="<?= htmlspecialchars(strtolower($itemPrice)) ?>"
                                data-raw='<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>'>
                                <td>
                                    <div class="dish-cell">
                                        <img src="<?= htmlspecialchars($resolvedImg) ?>" alt="<?= htmlspecialchars($itemName) ?>" class="dish-thumb" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=120&q=80'">
                                        <div class="dish-meta">
                                            <div class="dish-name">
                                                <?= htmlspecialchars($itemName) ?>
                                                <?php if ($isFeatured): ?>
                                                    <span title="Featured Item" style="color: var(--secondary-accent); font-size: 1rem; margin-left: 4px;">★</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($itemTagline): ?>
                                                <div style="font-family:'Caveat',cursive; color:#a67c52; font-size:0.92rem;"><?= htmlspecialchars($itemTagline) ?></div>
                                            <?php endif; ?>
                                            <div class="dish-desc"><?= htmlspecialchars($itemDesc) ?></div>
                                            <?php if (!empty($cFields)): ?>
                                                <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:4px;">
                                                    <?php foreach ($cFields as $cf): ?>
                                                        <span style="font-size:0.68rem; background:#f0eae1; color:#5c4736; padding:2px 6px; border-radius:4px; font-weight:600;">
                                                            <?= htmlspecialchars($cf['name'] ?? '') ?>: <?= htmlspecialchars($cf['value'] ?? '') ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="category-tag"><?= htmlspecialchars($itemBadge) ?></span>
                                </td>
                                <td>
                                    <span class="price-tag"><?= htmlspecialchars($itemPrice) ?></span>
                                </td>
                                <td>
                                    <span class="diet-badge <?= $isVeg ? 'veg' : 'non-veg' ?>">
                                        <span style="font-size: 12px;"><?= $isVeg ? '●' : '▲' ?></span>
                                        <span><?= $isVeg ? 'Vegetarian' : 'Non-Veg' ?></span>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="field" value="available">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($itemId) ?>">
                                        <button type="submit" class="status-badge <?= $isAvailable ? 'available' : 'unavailable' ?>" style="border:none; cursor:pointer;" title="Click to toggle availability">
                                            <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:currentColor;"></span>
                                            <span><?= $isAvailable ? 'Active' : 'Hidden' ?></span>
                                        </button>
                                    </form>
                                </td>
                                <td style="text-align: right;">
                                    <div class="row-actions" style="justify-content: flex-end;">
                                        <form method="POST" style="display:inline;" title="Toggle Featured">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="field" value="featured">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($itemId) ?>">
                                            <button type="submit" class="action-icon-btn" style="color: <?= $isFeatured ? 'var(--secondary-accent)' : '#9c9890' ?>;">
                                                ★
                                            </button>
                                        </form>

                                        <button type="button" class="action-icon-btn edit-dish-btn" title="Edit Item">
                                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>

                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete \'<?= htmlspecialchars(addslashes($itemName)) ?>\'?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($itemId) ?>">
                                            <button type="submit" class="action-icon-btn delete-btn" title="Delete Item">
                                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </div>

        <!-- ===================================================================
             TAB 2: CATEGORIES & SECTION HEADERS MANAGEMENT
             =================================================================== -->
        <div class="admin-tab-panel" id="categoriesTabPanel">
            <div style="background:#ffffff; border:1px solid var(--border-color); border-radius:var(--radius-md); padding:18px 22px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div>
                    <h2 style="font-family:var(--font-heading); font-size:1.45rem; color:var(--primary-accent); margin:0 0 4px;">Menu Categories & Section Presentations</h2>
                    <p style="font-size:0.85rem; color:var(--text-secondary); margin:0;">
                        Customize section titles, eyebrow headers (e.g. <em>"OUR SIGNATURES"</em>), handwritten cursive quotes (e.g. <em>"More than just Bread"</em>), cover images, and sort ordering.
                    </p>
                </div>
                <button type="button" class="btn-primary" onclick="openCategoryModal(false)">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Create Category</span>
                </button>
            </div>

            <!-- Categories Card Grid -->
            <div class="category-grid">
                <?php foreach ($categoriesData as $c): ?>
                    <?php 
                        $cId = $c['id'] ?? '';
                        $cName = $c['name'] ?? 'Untitled';
                        $cEyebrow = $c['eyebrow'] ?? 'OUR SIGNATURES';
                        $cScript = $c['scriptQuote'] ?? '';
                        $cSub = $c['subtitle'] ?? '';
                        $cImg = $c['image'] ?? 'assets/placeholder.jpg';
                        $cOrder = $c['order'] ?? 99;
                        $cActive = $c['active'] ?? true;
                        $resolvedCImg = str_starts_with($cImg, 'http') ? $cImg : '../public/' . ltrim($cImg, '/');
                        $itemCount = count(array_filter($catalog, fn($i) => ($i['badge'] ?? '') === $cName));
                    ?>
                    <div class="category-admin-card" data-raw='<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>'>
                        <div class="category-card-cover">
                            <img src="<?= htmlspecialchars($resolvedCImg) ?>" alt="<?= htmlspecialchars($cName) ?>" class="category-card-img" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80'">
                            <div class="category-card-overlay"></div>
                            <span class="category-card-eyebrow-badge"><?= htmlspecialchars($cEyebrow) ?></span>
                            <span class="category-card-order-badge">#<?= $cOrder ?></span>
                        </div>
                        <div class="category-card-body">
                            <h3 class="category-card-title">
                                <span><?= htmlspecialchars($cName) ?></span>
                                <span style="font-size:0.75rem; font-family:var(--font-body); font-weight:700; color:#8a7153;"><?= $itemCount ?> dishes</span>
                            </h3>
                            <?php if ($cScript): ?>
                                <div class="category-card-script">&ldquo;<?= htmlspecialchars($cScript) ?>&rdquo;</div>
                            <?php endif; ?>
                            <p class="category-card-desc"><?= htmlspecialchars($cSub) ?></p>

                            <div class="category-card-footer">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="category_toggle">
                                    <input type="hidden" name="cat_id" value="<?= htmlspecialchars($cId) ?>">
                                    <button type="submit" class="status-badge <?= $cActive ? 'available' : 'unavailable' ?>" style="border:none; cursor:pointer;" title="Toggle category visibility">
                                        <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:currentColor;"></span>
                                        <span><?= $cActive ? 'Visible' : 'Hidden' ?></span>
                                    </button>
                                </form>

                                <div class="row-actions">
                                    <button type="button" class="action-icon-btn edit-category-btn" title="Edit Category & Headers">
                                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>

                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete category \'<?= htmlspecialchars(addslashes($cName)) ?>\'? Dishes will remain in database.');" style="display:inline;">
                                        <input type="hidden" name="action" value="category_delete">
                                        <input type="hidden" name="cat_id" value="<?= htmlspecialchars($cId) ?>">
                                        <button type="submit" class="action-icon-btn delete-btn" title="Delete Category">
                                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </main>

    <!-- =======================================================================
         MODAL 1: DISH FORM (ADD / EDIT) WITH DYNAMIC EXTRA FIELDS
         ======================================================================= -->
    <div class="modal-backdrop" id="dishModal">
        <div class="modal-card">
            <form method="POST" enctype="multipart/form-data" id="dishForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formDishId" value="">

                <div class="modal-header">
                    <h3 class="modal-title" id="dishModalTitleText">Add New Dish</h3>
                    <button type="button" class="modal-close-btn" id="closeDishModalBtn">&times;</button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Dish Name *</label>
                        <input type="text" name="name" id="dishNameInput" class="form-input" placeholder="e.g. Artisanal Mozzarella Flatbread" required>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Category *</label>
                            <input list="categoriesList" name="badge" id="dishBadgeInput" class="form-input" placeholder="e.g. Flat Breads" required>
                            <datalist id="categoriesList">
                                <?php foreach ($categoryNames as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Price (INR) *</label>
                            <input type="text" name="price" id="dishPriceInput" class="form-input" placeholder="e.g. ₹380" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Evocative Dish Tagline (Cursive Script on Split Card)</label>
                        <input type="text" name="tagline" id="dishTaglineInput" class="form-input" placeholder="e.g. Crispy edges, Endless flavour (leave empty for auto-quote)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="dishDescInput" class="form-textarea" rows="2" placeholder="Describe the ingredients, craft, and flavor notes..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Dish Photograph (Upload File or Enter URL)</label>
                        <input type="file" name="image_file" id="dishFileInput" class="form-input" accept="image/*" style="margin-bottom: 8px;">
                        <input type="text" name="image_url" id="dishUrlInput" class="form-input" placeholder="Or paste image URL (e.g. uploads/my_photo.jpg or Unsplash URL)">
                    </div>

                    <div class="checkbox-group">
                        <label class="custom-checkbox">
                            <input type="checkbox" name="is_veg" id="dishVegCheck" checked>
                            <span>Vegetarian</span>
                        </label>

                        <label class="custom-checkbox">
                            <input type="checkbox" name="featured" id="dishFeaturedCheck">
                            <span>Chef's Featured Dish</span>
                        </label>

                        <label class="custom-checkbox">
                            <input type="checkbox" name="available" id="dishAvailableCheck" checked>
                            <span>Active on Menu</span>
                        </label>
                    </div>

                    <!-- DYNAMIC CUSTOM EXTRA FIELDS BUILDER -->
                    <div class="custom-fields-section">
                        <div class="custom-fields-header">
                            <div class="custom-fields-title">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                <span>Custom Extra Fields (Allergens, Spice Level, Chef Notes...)</span>
                            </div>
                            <button type="button" class="btn-add-cf" id="btnAddCustomField">+ Add Extra Field</button>
                        </div>
                        <div class="custom-fields-list" id="customFieldsList">
                            <!-- Dynamic rows will be inserted here -->
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" id="cancelDishModalBtn">Cancel</button>
                    <button type="submit" class="btn-primary" id="saveDishBtn">Save Dish</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =======================================================================
         MODAL 2: CATEGORY & SECTION HEADERS FORM (ADD / EDIT)
         ======================================================================= -->
    <div class="modal-backdrop" id="categoryModal">
        <div class="modal-card">
            <form method="POST" enctype="multipart/form-data" id="categoryForm">
                <input type="hidden" name="action" value="category_save">
                <input type="hidden" name="cat_id" id="catFormId" value="">
                <input type="hidden" name="cat_old_name" id="catFormOldName" value="">

                <div class="modal-header">
                    <h3 class="modal-title" id="catModalTitleText">Create New Category</h3>
                    <button type="button" class="modal-close-btn" id="closeCatModalBtn">&times;</button>
                </div>

                <div class="modal-body">
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Category Name *</label>
                            <input type="text" name="cat_name" id="catNameInput" class="form-input" placeholder="e.g. Flat Breads" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Display Order Sequence</label>
                            <input type="number" name="cat_order" id="catOrderInput" class="form-input" placeholder="e.g. 1" value="1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Eyebrow Header Text (All Uppercase) *</label>
                        <input type="text" name="cat_eyebrow" id="catEyebrowInput" class="form-input" placeholder="e.g. OUR SIGNATURES, WOODFIRED ARTISAN..." value="OUR SIGNATURES" required>
                        <small style="color:#8a7153; font-size:0.75rem;">This appears right above the category name on the live menu.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Handwritten Cursive Quote *</label>
                        <input type="text" name="cat_script_quote" id="catScriptInput" class="form-input" placeholder="e.g. More than just Bread, Blistered & Melted..." required>
                        <small style="color:#8a7153; font-size:0.75rem;">Rendered in cursive script beside the title and with underline flourish.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Category Subtitle / Description</label>
                        <textarea name="cat_subtitle" id="catSubtitleInput" class="form-textarea" rows="2" placeholder="e.g. Hand-stretched sourdough flatbreads baked with artisanal melts & toppings..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Category Showcase Photograph (Upload or URL)</label>
                        <input type="file" name="cat_image_file" id="catFileInput" class="form-input" accept="image/*" style="margin-bottom: 8px;">
                        <input type="text" name="cat_image_url" id="catUrlInput" class="form-input" placeholder="Or enter image URL (e.g. uploads/mozzarella_flatbread.jpg)">
                    </div>

                    <div class="checkbox-group">
                        <label class="custom-checkbox">
                            <input type="checkbox" name="cat_active" id="catActiveCheck" checked>
                            <span>Visible on Menu</span>
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" id="cancelCatModalBtn">Cancel</button>
                    <button type="submit" class="btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Admin Interactive Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Tab Switching
            const tabBtnDishes = document.getElementById('tabBtnDishes');
            const tabBtnCategories = document.getElementById('tabBtnCategories');
            const dishesPanel = document.getElementById('dishesTabPanel');
            const categoriesPanel = document.getElementById('categoriesTabPanel');
            const openAddDishBtn = document.getElementById('openAddDishBtn');
            const openAddCategoryBtn = document.getElementById('openAddCategoryBtn');

            function switchTab(tab) {
                if (tab === 'categories') {
                    tabBtnDishes.classList.remove('active');
                    tabBtnCategories.classList.add('active');
                    dishesPanel.classList.remove('active');
                    categoriesPanel.classList.add('active');
                    openAddDishBtn.style.display = 'none';
                    openAddCategoryBtn.style.display = 'inline-flex';
                } else {
                    tabBtnCategories.classList.remove('active');
                    tabBtnDishes.classList.add('active');
                    categoriesPanel.classList.remove('active');
                    dishesPanel.classList.add('active');
                    openAddCategoryBtn.style.display = 'none';
                    openAddDishBtn.style.display = 'inline-flex';
                }
            }

            tabBtnDishes?.addEventListener('click', () => switchTab('dishes'));
            tabBtnCategories?.addEventListener('click', () => switchTab('categories'));

            // =================================================================
            // DISH MODAL & CUSTOM FIELDS BUILDER
            // =================================================================
            const dishModal = document.getElementById('dishModal');
            const closeDishModalBtn = document.getElementById('closeDishModalBtn');
            const cancelDishModalBtn = document.getElementById('cancelDishModalBtn');
            const dishForm = document.getElementById('dishForm');
            const dishModalTitle = document.getElementById('dishModalTitleText');

            const formAction = document.getElementById('formAction');
            const formDishId = document.getElementById('formDishId');
            const dishName = document.getElementById('dishNameInput');
            const dishBadge = document.getElementById('dishBadgeInput');
            const dishPrice = document.getElementById('dishPriceInput');
            const dishTagline = document.getElementById('dishTaglineInput');
            const dishDesc = document.getElementById('dishDescInput');
            const dishUrl = document.getElementById('dishUrlInput');
            const dishVeg = document.getElementById('dishVegCheck');
            const dishFeatured = document.getElementById('dishFeaturedCheck');
            const dishAvailable = document.getElementById('dishAvailableCheck');
            const customFieldsList = document.getElementById('customFieldsList');
            const btnAddCustomField = document.getElementById('btnAddCustomField');

            function addCustomFieldRow(name = '', value = '') {
                const row = document.createElement('div');
                row.className = 'custom-field-row';
                row.innerHTML = `
                    <input type="text" name="cf_name[]" placeholder="Field Name (e.g. Spice Level)" value="${escapeHtml(name)}">
                    <input type="text" name="cf_value[]" placeholder="Value (e.g. Medium Spicy)" value="${escapeHtml(value)}">
                    <button type="button" class="btn-del-cf" title="Remove Field">&times;</button>
                `;
                row.querySelector('.btn-del-cf').addEventListener('click', () => {
                    row.remove();
                });
                customFieldsList.appendChild(row);
            }

            function escapeHtml(text) {
                if (!text) return '';
                return String(text).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            btnAddCustomField?.addEventListener('click', () => {
                addCustomFieldRow('', '');
            });

            function openDishModal(isEdit = false, data = null) {
                customFieldsList.innerHTML = '';
                if (isEdit && data) {
                    dishModalTitle.textContent = 'Edit Menu Item';
                    formAction.value = 'edit';
                    formDishId.value = data.id || '';
                    dishName.value = data.name || '';
                    dishBadge.value = data.badge || '';
                    dishPrice.value = data.price || '';
                    dishTagline.value = data.tagline || '';
                    dishDesc.value = data.description || '';
                    dishUrl.value = data.image || '';
                    dishVeg.checked = !!data.is_veg;
                    dishFeatured.checked = !!data.featured;
                    dishAvailable.checked = data.available !== false;

                    if (Array.isArray(data.custom_fields)) {
                        data.custom_fields.forEach(cf => {
                            addCustomFieldRow(cf.name, cf.value);
                        });
                    }
                } else {
                    dishModalTitle.textContent = 'Add New Dish';
                    formAction.value = 'add';
                    formDishId.value = '';
                    dishForm.reset();
                    dishVeg.checked = true;
                    dishAvailable.checked = true;
                }
                dishModal.classList.add('open');
            }

            function closeDishModal() {
                dishModal.classList.remove('open');
            }

            openAddDishBtn?.addEventListener('click', () => openDishModal(false));
            closeDishModalBtn?.addEventListener('click', closeDishModal);
            cancelDishModalBtn?.addEventListener('click', closeDishModal);
            dishModal?.addEventListener('click', (e) => {
                if (e.target === dishModal) closeDishModal();
            });

            document.querySelectorAll('.edit-dish-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const row = btn.closest('.dish-row');
                    const raw = row.getAttribute('data-raw');
                    try {
                        const data = JSON.parse(raw);
                        openDishModal(true, data);
                    } catch (e) {
                        console.error('Failed to parse dish json', e);
                    }
                });
            });

            // =================================================================
            // CATEGORY MODAL
            // =================================================================
            const categoryModal = document.getElementById('categoryModal');
            const closeCatModalBtn = document.getElementById('closeCatModalBtn');
            const cancelCatModalBtn = document.getElementById('cancelCatModalBtn');
            const categoryForm = document.getElementById('categoryForm');
            const catModalTitle = document.getElementById('catModalTitleText');

            const catFormId = document.getElementById('catFormId');
            const catFormOldName = document.getElementById('catFormOldName');
            const catNameInput = document.getElementById('catNameInput');
            const catOrderInput = document.getElementById('catOrderInput');
            const catEyebrowInput = document.getElementById('catEyebrowInput');
            const catScriptInput = document.getElementById('catScriptInput');
            const catSubtitleInput = document.getElementById('catSubtitleInput');
            const catUrlInput = document.getElementById('catUrlInput');
            const catActiveCheck = document.getElementById('catActiveCheck');

            window.openCategoryModal = function(isEdit = false, data = null) {
                if (isEdit && data) {
                    catModalTitle.textContent = 'Edit Category & Section Headers';
                    catFormId.value = data.id || '';
                    catFormOldName.value = data.name || '';
                    catNameInput.value = data.name || '';
                    catOrderInput.value = data.order ?? 99;
                    catEyebrowInput.value = data.eyebrow || 'OUR SIGNATURES';
                    catScriptInput.value = data.scriptQuote || '';
                    catSubtitleInput.value = data.subtitle || '';
                    catUrlInput.value = data.image || '';
                    catActiveCheck.checked = data.active !== false;
                } else {
                    catModalTitle.textContent = 'Create New Category';
                    catFormId.value = '';
                    catFormOldName.value = '';
                    categoryForm.reset();
                    catOrderInput.value = (document.querySelectorAll('.category-admin-card').length + 1);
                    catEyebrowInput.value = 'OUR SIGNATURES';
                    catActiveCheck.checked = true;
                }
                categoryModal.classList.add('open');
            };

            function closeCategoryModal() {
                categoryModal.classList.remove('open');
            }

            openAddCategoryBtn?.addEventListener('click', () => openCategoryModal(false));
            closeCatModalBtn?.addEventListener('click', closeCategoryModal);
            cancelCatModalBtn?.addEventListener('click', closeCategoryModal);
            categoryModal?.addEventListener('click', (e) => {
                if (e.target === categoryModal) closeCategoryModal();
            });

            document.querySelectorAll('.edit-category-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const card = btn.closest('.category-admin-card');
                    const raw = card.getAttribute('data-raw');
                    try {
                        const data = JSON.parse(raw);
                        openCategoryModal(true, data);
                    } catch (e) {
                        console.error('Failed to parse category data', e);
                    }
                });
            });

            // =================================================================
            // CLIENT SEARCH & FILTER (DISHES TAB)
            // =================================================================
            const searchInput = document.getElementById('adminSearchInput');
            const categoryPills = document.querySelectorAll('#categoryPillContainer .pill-btn');
            const dishRows = document.querySelectorAll('#dishesTable .dish-row');

            let activeCategory = 'ALL';
            let searchQuery = '';

            function filterRows() {
                dishRows.forEach(row => {
                    const rowName = row.getAttribute('data-name') || '';
                    const rowDesc = row.getAttribute('data-desc') || '';
                    const rowPrice = row.getAttribute('data-price') || '';
                    const rowCat = row.getAttribute('data-category') || '';

                    const matchesCategory = (activeCategory === 'ALL') || (rowCat === activeCategory);
                    const matchesSearch = !searchQuery || 
                        rowName.includes(searchQuery) || 
                        rowDesc.includes(searchQuery) || 
                        rowPrice.includes(searchQuery) || 
                        rowCat.toLowerCase().includes(searchQuery);

                    row.style.display = (matchesCategory && matchesSearch) ? '' : 'none';
                });
            }

            searchInput?.addEventListener('input', (e) => {
                searchQuery = e.target.value.toLowerCase().trim();
                filterRows();
            });

            categoryPills.forEach(pill => {
                pill.addEventListener('click', () => {
                    categoryPills.forEach(p => p.classList.remove('active'));
                    pill.classList.add('active');
                    activeCategory = pill.getAttribute('data-category');
                    filterRows();
                });
            });
        });
    </script>
</body>
</html>
