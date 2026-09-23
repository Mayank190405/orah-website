<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../engine/JsonStorage.php';
require_once __DIR__ . '/../engine/ThemeEngine.php';

use CoreStore\Engine\JsonStorage;

require_auth();

$storage = new JsonStorage(__DIR__ . '/../data/catalog.json');
$catalog = $storage->read();

$settingsStorage = new JsonStorage(__DIR__ . '/../data/settings.json');
$settings = $settingsStorage->read();
$brand = $settings['brand'] ?? ['text' => 'Orah House'];

$message = null;
$messageType = 'success';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id = ($action === 'edit') ? trim($_POST['id'] ?? '') : 'item_' . time() . '_' . bin2hex(random_bytes(3));
        $name = trim($_POST['name'] ?? '');
        $badge = trim($_POST['badge'] ?? 'General');
        $price = trim($_POST['price'] ?? '₹0');
        $description = trim($_POST['description'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $isVeg = isset($_POST['is_veg']);
        $featured = isset($_POST['featured']);
        $available = isset($_POST['available']);

        // Format price nicely with currency symbol if missing
        if (!str_starts_with($price, '₹') && !str_starts_with($price, '$')) {
            $price = '₹' . $price;
        }

        // Handle Image File Upload if provided
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($ext, $allowed)) {
                $uploadDir = __DIR__ . '/../public/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $filename = 'dish_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetPath = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $imageUrl = 'uploads/' . $filename;
                } else {
                    $message = 'File upload failed to save to disk.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Invalid image type. Please upload a JPG, PNG, or WebP file.';
                $messageType = 'error';
            }
        }

        if (empty($name)) {
            $message = 'Dish name is required.';
            $messageType = 'error';
        }

        if (!$message || $messageType !== 'error') {
            $itemData = [
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'badge' => $badge,
                'is_veg' => $isVeg,
                'featured' => $featured,
                'available' => $available,
                'image' => $imageUrl ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=700&q=80'
            ];

            if ($action === 'add') {
                array_unshift($catalog, $itemData);
                $message = "Successfully added '{$name}' to the menu.";
            } else {
                foreach ($catalog as $i => $item) {
                    if (($item['id'] ?? '') === $id) {
                        // Preserve existing image if no new one was specified
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
}

// Compute Statistics
$totalItems = count($catalog);
$categories = array_values(array_unique(array_filter(array_column($catalog, 'badge'))));
sort($categories);
$featuredCount = count(array_filter($catalog, fn($i) => !empty($i['featured'])));
$availableCount = count(array_filter($catalog, fn($i) => ($i['available'] ?? true)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management | Orah House Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- Header Navigation -->
    <header class="admin-header">
        <div class="admin-nav">
            <a href="index.php" class="brand-section">
                <img src="../public/assets/images/swans_only.png" alt="Orah Swans" class="admin-logo">
                <span class="brand-title">ORAH HOUSE</span>
                <span class="admin-badge">Menu Console</span>
            </a>

            <div class="nav-actions">
                <a href="../public/menu.php" target="_blank" class="nav-link-btn">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>View Menu Page</span>
                </a>
                <a href="../public/index.php" target="_blank" class="nav-link-btn">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
                    <span>Storefront</span>
                </a>
                <button type="button" class="btn-primary" id="openAddModalBtn">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Add New Dish</span>
                </button>
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
                    <div class="stat-value"><?= count($categories) ?></div>
                </div>
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Active / Available</div>
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

        <!-- Search & Category Filter Toolbar -->
        <section class="control-bar">
            <div class="search-box">
                <svg class="search-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="adminSearchInput" class="search-input" placeholder="Search dishes, descriptions, or prices...">
            </div>

            <div class="filter-pills" id="categoryPillContainer">
                <button type="button" class="pill-btn active" data-category="ALL">All (<?= $totalItems ?>)</button>
                <?php foreach ($categories as $cat): ?>
                    <?php 
                        $count = count(array_filter($catalog, fn($i) => ($i['badge'] ?? '') === $cat));
                    ?>
                    <button type="button" class="pill-btn" data-category="<?= htmlspecialchars($cat) ?>">
                        <?= htmlspecialchars($cat) ?> (<?= $count ?>)
                    </button>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Menu Table -->
        <section class="items-container">
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
                            $itemImg = $item['image'] ?? 'assets/placeholder.jpg';
                            $isVeg = !empty($item['is_veg']);
                            $isFeatured = !empty($item['featured']);
                            $isAvailable = $item['available'] ?? true;

                            // Adjust image path if relative to public
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
                                        <div class="dish-desc"><?= htmlspecialchars($itemDesc) ?></div>
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

    </main>

    <!-- Dish Modal Form (Add / Edit) -->
    <div class="modal-backdrop" id="dishModal">
        <div class="modal-card">
            <form method="POST" enctype="multipart/form-data" id="dishForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formDishId" value="">

                <div class="modal-header">
                    <h3 class="modal-title" id="modalTitleText">Add New Dish</h3>
                    <button type="button" class="modal-close-btn" id="closeModalBtn">&times;</button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Dish Name *</label>
                        <input type="text" name="name" id="dishNameInput" class="form-input" placeholder="e.g. Artisanal Mozzarella Flatbread" required>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Category *</label>
                            <input list="categoriesList" name="badge" id="dishBadgeInput" class="form-input" placeholder="e.g. Flatbreads" required>
                            <datalist id="categoriesList">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"></option>
                                <?php endforeach; ?>
                                <option value="Coffee"></option>
                                <option value="Pizzas"></option>
                                <option value="Flatbreads"></option>
                                <option value="Pastas"></option>
                                <option value="Starters"></option>
                                <option value="Bowls & Salads"></option>
                                <option value="Desserts"></option>
                            </datalist>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Price (INR) *</label>
                            <input type="text" name="price" id="dishPriceInput" class="form-input" placeholder="e.g. ₹380" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="dishDescInput" class="form-textarea" rows="3" placeholder="Describe the ingredients, craft, and flavor notes..."></textarea>
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
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" id="cancelModalBtn">Cancel</button>
                    <button type="submit" class="btn-primary" id="saveDishBtn">Save Dish</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Admin Interactive Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('dishModal');
            const openAddBtn = document.getElementById('openAddModalBtn');
            const closeBtn = document.getElementById('closeModalBtn');
            const cancelBtn = document.getElementById('cancelModalBtn');
            const form = document.getElementById('dishForm');
            const modalTitle = document.getElementById('modalTitleText');

            // Form inputs
            const formAction = document.getElementById('formAction');
            const formDishId = document.getElementById('formDishId');
            const dishName = document.getElementById('dishNameInput');
            const dishBadge = document.getElementById('dishBadgeInput');
            const dishPrice = document.getElementById('dishPriceInput');
            const dishDesc = document.getElementById('dishDescInput');
            const dishUrl = document.getElementById('dishUrlInput');
            const dishVeg = document.getElementById('dishVegCheck');
            const dishFeatured = document.getElementById('dishFeaturedCheck');
            const dishAvailable = document.getElementById('dishAvailableCheck');

            function openModal(isEdit = false, data = null) {
                if (isEdit && data) {
                    modalTitle.textContent = 'Edit Menu Item';
                    formAction.value = 'edit';
                    formDishId.value = data.id || '';
                    dishName.value = data.name || '';
                    dishBadge.value = data.badge || '';
                    dishPrice.value = data.price || '';
                    dishDesc.value = data.description || '';
                    dishUrl.value = data.image || '';
                    dishVeg.checked = !!data.is_veg;
                    dishFeatured.checked = !!data.featured;
                    dishAvailable.checked = data.available !== false;
                } else {
                    modalTitle.textContent = 'Add New Dish';
                    formAction.value = 'add';
                    formDishId.value = '';
                    form.reset();
                    dishVeg.checked = true;
                    dishAvailable.checked = true;
                }
                modal.classList.add('open');
            }

            function closeModal() {
                modal.classList.remove('open');
            }

            openAddBtn?.addEventListener('click', () => openModal(false));
            closeBtn?.addEventListener('click', closeModal);
            cancelBtn?.addEventListener('click', closeModal);

            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });

            // Edit button handlers
            document.querySelectorAll('.edit-dish-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const row = btn.closest('.dish-row');
                    const raw = row.getAttribute('data-raw');
                    try {
                        const data = JSON.parse(raw);
                        openModal(true, data);
                    } catch (e) {
                        console.error('Failed to parse dish json', e);
                    }
                });
            });

            // Client-side Search & Category Filter
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

                    if (matchesCategory && matchesSearch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
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
