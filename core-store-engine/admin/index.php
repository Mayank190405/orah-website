<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../engine/JsonStorage.php';
require_once __DIR__ . '/../engine/ThemeEngine.php';

use CoreStore\Engine\JsonStorage;

require_auth();

// Storage instances
$catalogStorage = new JsonStorage(__DIR__ . '/../data/catalog.json');
$catalog = $catalogStorage->read() ?: [];

$catStorage = new JsonStorage(__DIR__ . '/../data/categories.json');
$categoriesData = $catStorage->read() ?: [];
usort($categoriesData, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));

$popupsStorage = new JsonStorage(__DIR__ . '/../data/popups.json');
$popupsData = $popupsStorage->read() ?: [];

$leadsStorage = new JsonStorage(__DIR__ . '/../data/leads.json');
$leadsData = $leadsStorage->read() ?: [];

$pagesStorage = new JsonStorage(__DIR__ . '/../data/pages.json');
$pagesData = $pagesStorage->read() ?: [
    'menu.php' => ['title' => 'Menu Page', 'status' => 'live', 'down_message' => 'Our menu is undergoing seasonal curation.', 'down_action' => 'maintenance', 'redirect_to' => 'index.php'],
    'index.php' => ['title' => 'Storefront Home', 'status' => 'live', 'down_message' => 'Storefront is temporarily offline for a private event.', 'down_action' => 'maintenance', 'redirect_to' => 'menu.php']
];

$settingsStorage = new JsonStorage(__DIR__ . '/../data/settings.json');
$settings = $settingsStorage->read() ?: [];

$message = null;
$messageType = 'success';

// Reusable Image Upload Helper
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

// =============================================================================
// POST ACTION ROUTER
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. DISH ACTIONS
    if ($action === 'dish_save') {
        $id = trim($_POST['id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $badge = trim($_POST['badge'] ?? 'General');
        $price = trim($_POST['price'] ?? '₹0');
        $description = trim($_POST['description'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $isVeg = isset($_POST['is_veg']);
        $featured = isset($_POST['featured']);
        $available = isset($_POST['available']);

        $uploaded = saveUploadedImage('image_file', 'dish');
        if ($uploaded) $imageUrl = $uploaded;

        if (!str_starts_with($price, '₹') && !str_starts_with($price, '$')) {
            $price = '₹' . $price;
        }

        $customFields = [];
        if (!empty($_POST['cf_name']) && is_array($_POST['cf_name'])) {
            foreach ($_POST['cf_name'] as $idx => $cfName) {
                $cfName = trim($cfName);
                $cfVal = trim($_POST['cf_value'][$idx] ?? '');
                if ($cfName !== '' || $cfVal !== '') {
                    $customFields[] = ['name' => $cfName, 'value' => $cfVal];
                }
            }
        }

        $isChefSpecial = !empty($_POST['is_chef_special']);
        $chefSpecialNote = trim($_POST['chef_special_note'] ?? '');
        $order = isset($_POST['order']) && is_numeric($_POST['order']) ? (int)$_POST['order'] : 99;
        $cardBg = trim($_POST['card_bg'] ?? '');
        $cardBorder = trim($_POST['card_border'] ?? '');
        $cardAnimation = trim($_POST['card_animation'] ?? 'none');

        if (empty($name)) {
            $message = 'Dish name is required.';
            $messageType = 'error';
        } else {
            $isNew = empty($id);
            if ($isNew) {
                $id = 'item_' . time() . '_' . bin2hex(random_bytes(3));
            }

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
                'custom_fields' => $customFields,
                'order' => $order,
                'is_chef_special' => $isChefSpecial,
                'chef_special_note' => $chefSpecialNote,
                'card_style' => [
                    'bg_color' => $cardBg,
                    'border_color' => $cardBorder,
                    'accent_color' => '',
                    'animation' => $cardAnimation
                ]
            ];

            if ($isNew) {
                array_unshift($catalog, $itemData);
                $message = "Added '{$name}' to the menu.";
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
            $catalogStorage->write($catalog);
        }
    } elseif ($action === 'dish_delete') {
        $id = trim($_POST['id'] ?? '');
        $catalog = array_values(array_filter($catalog, fn($item) => ($item['id'] ?? '') !== $id));
        $catalogStorage->write($catalog);
        $message = "Menu item deleted.";
    } elseif ($action === 'dish_toggle') {
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
        $catalogStorage->write($catalog);
        $message = "Status updated.";
    } elseif ($action === 'dish_reorder') {
        $id = trim($_POST['id'] ?? '');
        $direction = trim($_POST['direction'] ?? ''); // 'up' or 'down'

        $targetDish = null;
        foreach ($catalog as $item) {
            if (($item['id'] ?? '') === $id) {
                $targetDish = $item;
                break;
            }
        }

        if ($targetDish) {
            $cat = $targetDish['badge'] ?? 'General';
            $catIndices = [];
            foreach ($catalog as $i => $item) {
                if (($item['badge'] ?? '') === $cat) {
                    $catIndices[] = $i;
                }
            }
            usort($catIndices, function($a, $b) use ($catalog) {
                $ordA = isset($catalog[$a]['order']) && is_numeric($catalog[$a]['order']) ? (int)$catalog[$a]['order'] : 999;
                $ordB = isset($catalog[$b]['order']) && is_numeric($catalog[$b]['order']) ? (int)$catalog[$b]['order'] : 999;
                return $ordA <=> $ordB;
            });

            $pos = -1;
            foreach ($catIndices as $p => $idx) {
                if (($catalog[$idx]['id'] ?? '') === $id) {
                    $pos = $p;
                    break;
                }
            }

            if ($pos !== -1) {
                $swapPos = ($direction === 'up') ? ($pos - 1) : ($pos + 1);
                if ($swapPos >= 0 && $swapPos < count($catIndices)) {
                    $currGlobal = $catIndices[$pos];
                    $swapGlobal = $catIndices[$swapPos];

                    $currOrd = (int)($catalog[$currGlobal]['order'] ?? ($pos + 1));
                    $swapOrd = (int)($catalog[$swapGlobal]['order'] ?? ($swapPos + 1));

                    if ($currOrd === $swapOrd) {
                        $currOrd = $pos + 1;
                        $swapOrd = $swapPos + 1;
                    }

                    $catalog[$currGlobal]['order'] = $swapOrd;
                    $catalog[$swapGlobal]['order'] = $currOrd;

                    $catalogStorage->write($catalog);
                    $message = "Shuffled '{$targetDish['name']}' {$direction}!";
                }
            }
        }
    } elseif ($action === 'dish_renumber_category') {
        $catName = trim($_POST['category'] ?? '');
        $catIndices = [];
        foreach ($catalog as $i => $item) {
            if (($item['badge'] ?? '') === $catName) {
                $catIndices[] = $i;
            }
        }
        usort($catIndices, function($a, $b) use ($catalog) {
            $ordA = isset($catalog[$a]['order']) && is_numeric($catalog[$a]['order']) ? (int)$catalog[$a]['order'] : 999;
            $ordB = isset($catalog[$b]['order']) && is_numeric($catalog[$b]['order']) ? (int)$catalog[$b]['order'] : 999;
            return $ordA <=> $ordB;
        });
        $seq = 1;
        foreach ($catIndices as $idx) {
            $catalog[$idx]['order'] = $seq++;
        }
        $catalogStorage->write($catalog);
        $message = "Renumbered dishes in '{$catName}' sequentially from 1 to " . count($catIndices) . ".";
    }

    // 2. CATEGORY ACTIONS
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
        $catAnim = trim($_POST['cat_animation'] ?? 'none');
        $catBg = trim($_POST['cat_card_bg'] ?? '');
        $catBorder = trim($_POST['cat_border_color'] ?? '');

        $uploaded = saveUploadedImage('cat_image_file', 'cat');
        if ($uploaded) $imageUrl = $uploaded;

        if (empty($catName)) {
            $message = 'Category name is required.';
            $messageType = 'error';
        } else {
            if (empty($catId)) {
                $catId = 'cat_' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $catName)) . '_' . bin2hex(random_bytes(2));
                $newCat = [
                    'id' => $catId,
                    'name' => $catName,
                    'eyebrow' => $eyebrow,
                    'scriptQuote' => $scriptQuote,
                    'subtitle' => $subtitle,
                    'image' => $imageUrl ?: 'uploads/mozzarella_flatbread.jpg',
                    'order' => $order,
                    'active' => $active,
                    'animation' => $catAnim,
                    'card_bg' => $catBg,
                    'border_color' => $catBorder
                ];
                $categoriesData[] = $newCat;
                $message = "Created category '{$catName}'.";
            } else {
                foreach ($categoriesData as $idx => $c) {
                    if (($c['id'] ?? '') === $catId) {
                        if (empty($imageUrl) && !empty($c['image'])) $imageUrl = $c['image'];
                        $categoriesData[$idx] = [
                            'id' => $catId,
                            'name' => $catName,
                            'eyebrow' => $eyebrow,
                            'scriptQuote' => $scriptQuote,
                            'subtitle' => $subtitle,
                            'image' => $imageUrl,
                            'order' => $order,
                            'active' => $active,
                            'animation' => $catAnim,
                            'card_bg' => $catBg,
                            'border_color' => $catBorder
                        ];
                        break;
                    }
                }
                if ($oldCatName && $oldCatName !== $catName) {
                    foreach ($catalog as $dIdx => $d) {
                        if (($d['badge'] ?? '') === $oldCatName) {
                            $catalog[$dIdx]['badge'] = $catName;
                        }
                    }
                    $catalogStorage->write($catalog);
                }
                $message = "Updated category '{$catName}'.";
            }
            usort($categoriesData, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));
            $catStorage->write($categoriesData);
        }
    } elseif ($action === 'category_delete') {
        $catId = trim($_POST['cat_id'] ?? '');
        $categoriesData = array_values(array_filter($categoriesData, fn($c) => ($c['id'] ?? '') !== $catId));
        $catStorage->write($categoriesData);
        $message = "Category deleted.";
    } elseif ($action === 'category_toggle') {
        $catId = trim($_POST['cat_id'] ?? '');
        foreach ($categoriesData as $idx => $c) {
            if (($c['id'] ?? '') === $catId) {
                $categoriesData[$idx]['active'] = !($c['active'] ?? true);
                break;
            }
        }
        $catStorage->write($categoriesData);
        $message = "Category visibility toggled.";
    }

    // 3. POP-UP & FORM STUDIO ACTIONS
    elseif ($action === 'popup_save') {
        $popupId = trim($_POST['popup_id'] ?? '');
        $popupType = trim($_POST['popup_type'] ?? 'reservation');
        $popupName = trim($_POST['popup_name'] ?? 'Table Reservation');
        $targetPage = trim($_POST['target_page'] ?? 'menu.php');
        $layout = trim($_POST['popup_layout'] ?? 'center_modal');
        $triggerType = trim($_POST['trigger_type'] ?? 'both');
        $delaySec = intval($_POST['trigger_delay_sec'] ?? 6);
        $floatingBtnText = trim($_POST['floating_btn_text'] ?? ($popupType === 'offer' ? '🎁 Special Offer' : '✦ Reserve Table'));
        $badgeText = trim($_POST['badge_text'] ?? ($popupType === 'offer' ? 'EXCLUSIVE OFFER' : 'EXCLUSIVE DINING'));
        $title = trim($_POST['title'] ?? 'Reserve Your Dining Experience');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $buttonText = trim($_POST['button_text'] ?? ($popupType === 'offer' ? 'Claim Offer On WhatsApp ↗' : 'Confirm Reservation Request'));
        $active = isset($_POST['popup_active']);
        $imageUrl = trim($_POST['image_url'] ?? '');

        // Offer Specific Fields
        $discountBadge = trim($_POST['discount_badge'] ?? '');
        $promoCode = trim($_POST['promo_code'] ?? '');
        $offerExpiry = trim($_POST['offer_expiry'] ?? '');
        $offerCtaType = trim($_POST['offer_cta_type'] ?? 'whatsapp');
        $offerCtaLink = trim($_POST['offer_cta_link'] ?? '');
        $terms = trim($_POST['terms'] ?? '');

        $uploaded = saveUploadedImage('image_file', 'popup');
        if ($uploaded) $imageUrl = $uploaded;

        // Element Visibility Toggles (Option to remove/hide ANY element)
        $elements = [
            'show_badge' => isset($_POST['el_badge']),
            'show_title' => isset($_POST['el_title']),
            'show_subtitle' => isset($_POST['el_subtitle']),
            'show_image' => isset($_POST['el_image']),
            'show_guests' => isset($_POST['el_guests']),
            'show_datetime' => isset($_POST['el_datetime']),
            'show_notes' => isset($_POST['el_notes']),
            'show_discount_badge' => isset($_POST['el_discount_badge']),
            'show_promo_code' => isset($_POST['el_promo_code']),
            'show_expiry' => isset($_POST['el_expiry']),
            'show_terms' => isset($_POST['el_terms']),
            'show_close' => isset($_POST['el_close'])
        ];

        $isNew = empty($popupId);
        if ($isNew) {
            $popupId = 'popup_' . time() . '_' . bin2hex(random_bytes(2));
        }

        $popupItem = [
            'id' => $popupId,
            'type' => $popupType,
            'name' => $popupName,
            'target_page' => $targetPage,
            'active' => $active,
            'layout' => $layout,
            'trigger_type' => $triggerType,
            'trigger_delay_sec' => $delaySec,
            'floating_btn_text' => $floatingBtnText,
            'badge_text' => $badgeText,
            'title' => $title,
            'subtitle' => $subtitle,
            'button_text' => $buttonText,
            'discount_badge' => $discountBadge,
            'promo_code' => $promoCode,
            'offer_expiry' => $offerExpiry,
            'offer_cta_type' => $offerCtaType,
            'offer_cta_link' => $offerCtaLink,
            'terms' => $terms,
            'image' => $imageUrl ?: 'uploads/mozzarella_flatbread.jpg',
            'elements' => $elements
        ];

        if ($isNew) {
            $popupsData[] = $popupItem;
            $message = "Created new pop-up form '{$popupName}'.";
        } else {
            foreach ($popupsData as $idx => $p) {
                if (($p['id'] ?? '') === $popupId) {
                    if (empty($imageUrl) && !empty($p['image'])) $popupItem['image'] = $p['image'];
                    $popupsData[$idx] = $popupItem;
                    break;
                }
            }
            $message = "Updated pop-up form '{$popupName}'.";
        }
        $popupsStorage->write($popupsData);
    } elseif ($action === 'popup_delete') {
        $popupId = trim($_POST['popup_id'] ?? '');
        $popupsData = array_values(array_filter($popupsData, fn($p) => ($p['id'] ?? '') !== $popupId));
        $popupsStorage->write($popupsData);
        $message = "Pop-up removed.";
    } elseif ($action === 'popup_toggle') {
        $popupId = trim($_POST['popup_id'] ?? '');
        foreach ($popupsData as $idx => $p) {
            if (($p['id'] ?? '') === $popupId) {
                $popupsData[$idx]['active'] = !($p['active'] ?? true);
                break;
            }
        }
        $popupsStorage->write($popupsData);
        $message = "Pop-up status updated.";
    }

    // 4. LEADS & RESERVATION ACTIONS
    elseif ($action === 'lead_status') {
        $leadId = trim($_POST['lead_id'] ?? '');
        $status = trim($_POST['status'] ?? 'pending');
        foreach ($leadsData as $idx => $l) {
            if (($l['id'] ?? '') === $leadId) {
                $leadsData[$idx]['status'] = $status;
                break;
            }
        }
        $leadsStorage->write($leadsData);
        $message = "Reservation status updated to " . strtoupper($status) . ".";
    } elseif ($action === 'lead_delete') {
        $leadId = trim($_POST['lead_id'] ?? '');
        $leadsData = array_values(array_filter($leadsData, fn($l) => ($l['id'] ?? '') !== $leadId));
        $leadsStorage->write($leadsData);
        $message = "Lead entry removed.";
    }

    // 5. PAGE CONTROLLER ACTIONS (LIVE / DOWN TOGGLES)
    elseif ($action === 'page_controller_save') {
        foreach (['menu.php', 'index.php'] as $pageKey) {
            $slug = str_replace('.', '_', $pageKey);

            // Look up status in page_status array or fallbacks
            $statusVal = $_POST['page_status'][$pageKey] 
                ?? $_POST["status_{$slug}"] 
                ?? $_POST["status_{$pageKey}"] 
                ?? null;

            if ($statusVal !== null) {
                $pagesData[$pageKey]['status'] = ($statusVal === 'live') ? 'live' : 'down';
            }

            $downMsg = $_POST['page_down_msg'][$pageKey] 
                ?? $_POST["down_msg_{$slug}"] 
                ?? $_POST["down_msg_{$pageKey}"] 
                ?? null;
            if ($downMsg !== null) {
                $pagesData[$pageKey]['down_message'] = trim($downMsg);
            }

            $downAction = $_POST['page_down_action'][$pageKey] 
                ?? $_POST["down_action_{$slug}"] 
                ?? $_POST["down_action_{$pageKey}"] 
                ?? null;
            if ($downAction !== null) {
                $pagesData[$pageKey]['down_action'] = trim($downAction);
            }

            $redirect = $_POST['page_redirect'][$pageKey] 
                ?? $_POST["redirect_{$slug}"] 
                ?? $_POST["redirect_{$pageKey}"] 
                ?? null;
            if ($redirect !== null) {
                $pagesData[$pageKey]['redirect_to'] = trim($redirect);
            }
        }
        $pagesStorage->write($pagesData);
        $message = "Page Controller status updated successfully.";
    }

    // 5B. INSTANT AJAX / QUICK TOGGLE (LIVE / DOWN)
    elseif ($action === 'page_toggle_quick') {
        $targetPage = trim($_POST['page'] ?? '');
        $forcedStatus = trim($_POST['status'] ?? '');

        if (isset($pagesData[$targetPage])) {
            if ($forcedStatus === 'live' || $forcedStatus === 'down') {
                $newStatus = $forcedStatus;
            } else {
                $curr = $pagesData[$targetPage]['status'] ?? 'live';
                $newStatus = ($curr === 'live') ? 'down' : 'live';
            }

            $pagesData[$targetPage]['status'] = $newStatus;
            $pagesStorage->write($pagesData);

            $pageTitle = $pagesData[$targetPage]['title'] ?? $targetPage;
            $statusText = strtoupper($newStatus);

            if (!empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'page' => $targetPage,
                    'status' => $newStatus,
                    'label' => $statusText,
                    'message' => "{$pageTitle} is now {$statusText}" . ($newStatus === 'down' ? ' (Maintenance Page Active)' : ' (Live for Visitors)')
                ]);
                exit;
            }

            $message = "{$pageTitle} is now {$statusText}.";
        }
    }
}

// Compute Statistics
$totalDishes = count($catalog);
$totalCategories = count($categoriesData);
$totalPopups = count($popupsData);
$totalLeads = count($leadsData);
$pendingLeads = count(array_filter($leadsData, fn($l) => ($l['status'] ?? '') === 'pending'));

$menuLive = ($pagesData['menu.php']['status'] ?? 'live') === 'live';
$indexLive = ($pagesData['index.php']['status'] ?? 'live') === 'live';
$allLive = $menuLive && $indexLive;

$distinctBadges = array_values(array_unique(array_filter(array_column($catalog, 'badge'))));
sort($distinctBadges);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Studio &bull; Orah House Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Cormorant+Garamond:wght@400;600&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<div class="dashboard-layout">

    <!-- =======================================================================
         SIDEBAR NAVIGATION
         ======================================================================= -->
    <aside class="dashboard-sidebar" id="dashboardSidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-brand">
                <img src="../public/assets/images/swans_only.png" alt="Orah Swans" class="sidebar-logo">
                <span class="sidebar-brand-text">ORAH HOUSE</span>
            </a>
            <span class="sidebar-badge">Studio</span>
        </div>

        <nav class="sidebar-nav">
            <span class="nav-section-label">Core Console</span>

            <button type="button" class="sidebar-link active" data-workspace="workspace-dashboard">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Dashboard</span>
            </button>

            <button type="button" class="sidebar-link" data-workspace="workspace-dishes">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8zM6 1v3M10 1v3M14 1v3"/></svg>
                <span>Menu Dishes</span>
                <span class="sidebar-link-badge"><?= $totalDishes ?></span>
            </button>

            <button type="button" class="sidebar-link" data-workspace="workspace-categories">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <span>Categories & Sections</span>
                <span class="sidebar-link-badge"><?= $totalCategories ?></span>
            </button>

            <span class="nav-section-label">Engagement & Control</span>

            <button type="button" class="sidebar-link" data-workspace="workspace-popups">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="14" y1="9" x2="19" y2="9"/><line x1="14" y1="14" x2="19" y2="14"/></svg>
                <span>Pop-Up & Form Studio</span>
                <span class="sidebar-link-badge"><?= $totalPopups ?></span>
            </button>

            <button type="button" class="sidebar-link" data-workspace="workspace-leads">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                <span>Leads & Reservations</span>
                <?php if ($pendingLeads > 0): ?>
                    <span class="sidebar-link-badge" style="background:#ef4444; color:#fff;"><?= $pendingLeads ?> new</span>
                <?php else: ?>
                    <span class="sidebar-link-badge"><?= $totalLeads ?></span>
                <?php endif; ?>
            </button>

            <button type="button" class="sidebar-link" data-workspace="workspace-pages">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>Page Controller</span>
                <span class="sidebar-link-badge" style="background:<?= $allLive ? '#15803d' : '#b45309' ?>; color:#fff;">
                    <?= $allLive ? 'All Live' : 'Notice' ?>
                </span>
            </button>
        </nav>

        <div class="sidebar-footer">
            <a href="../public/menu.php" target="_blank" class="sidebar-ext-link">
                <span>View Public Menu</span>
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
            </a>
            <a href="../public/index.php" target="_blank" class="sidebar-ext-link">
                <span>View Storefront</span>
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
            </a>
        </div>
    </aside>

    <!-- =======================================================================
         MAIN CONTENT AREA
         ======================================================================= -->
    <div class="dashboard-main">

        <!-- TOP BAR -->
        <header class="dashboard-topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" aria-label="Toggle Navigation">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="topbar-title-wrap">
                    <h1 class="topbar-title" id="currentWorkspaceTitle">Executive Dashboard</h1>
                    <span class="topbar-breadcrumb" id="currentWorkspaceSub">System Overview &bull; Orah House Nashik</span>
                </div>
            </div>

            <div class="topbar-right">
                <span class="system-status-indicator <?= $allLive ? 'all-live' : 'has-down' ?>">
                    <span class="status-dot"></span>
                    <span><?= $allLive ? 'All Pages Live' : 'Page Notice Active' ?></span>
                </span>

                <button type="button" class="btn-primary" id="topbarActionBtn" onclick="openDishModal(false)">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>+ Add New Dish</span>
                </button>
            </div>
        </header>

        <!-- WORKSPACE CONTENT -->
        <main class="workspace-content">

            <!-- Toast notification -->
            <?php if ($message): ?>
                <div class="toast-banner <?= $messageType === 'error' ? 'error' : '' ?>">
                    <span><?= htmlspecialchars($message) ?></span>
                    <button type="button" onclick="this.parentElement.style.display='none'" style="background:none;border:none;cursor:pointer;font-weight:700;">&times;</button>
                </div>
            <?php endif; ?>

            <!-- ===============================================================
                 WORKSPACE 1: DASHBOARD OVERVIEW
                 =============================================================== -->
            <div class="workspace-panel active" id="workspace-dashboard">
                <!-- 4 KPI Metrics -->
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div>
                            <div class="metric-label">Menu Dishes</div>
                            <div class="metric-value"><?= $totalDishes ?></div>
                            <div class="metric-sub"><?= count(array_filter($catalog, fn($i) => ($i['available'] ?? true))) ?> Active on Menu</div>
                        </div>
                        <div class="metric-icon-wrap">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8zM6 1v3M10 1v3M14 1v3"/></svg>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div>
                            <div class="metric-label">Categories</div>
                            <div class="metric-value"><?= $totalCategories ?></div>
                            <div class="metric-sub">Architectural Sections</div>
                        </div>
                        <div class="metric-icon-wrap">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div>
                            <div class="metric-label">Table Reservations</div>
                            <div class="metric-value"><?= $totalLeads ?></div>
                            <div class="metric-sub"><?= $pendingLeads ?> Pending Confirmation</div>
                        </div>
                        <div class="metric-icon-wrap">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                    </div>

                    <div class="metric-card" style="cursor:pointer;" onclick="switchWorkspace('workspace-pages')" title="Click to open Page Controller">
                        <div>
                            <div class="metric-label">Page Status &bull; Manage</div>
                            <div class="metric-value" style="font-size:1.35rem; font-weight:800; color:<?= $allLive ? 'var(--success)' : 'var(--danger)' ?>;">
                                <?= $allLive ? '100% LIVE' : 'PAGE DOWN' ?>
                            </div>
                            <div class="metric-sub" style="display:flex; gap:6px; align-items:center; margin-top:5px; flex-wrap:wrap;">
                                <span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; font-weight:700; font-size:0.72rem; background:<?= $menuLive ? 'rgba(34,197,94,0.15)' : 'rgba(239,68,68,0.18)' ?>; color:<?= $menuLive ? '#15803d' : '#b91c1c' ?>;">
                                    Menu: <?= $menuLive ? '● LIVE' : '■ DOWN' ?>
                                </span>
                                <span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; font-weight:700; font-size:0.72rem; background:<?= $indexLive ? 'rgba(34,197,94,0.15)' : 'rgba(239,68,68,0.18)' ?>; color:<?= $indexLive ? '#15803d' : '#b91c1c' ?>;">
                                    Home: <?= $indexLive ? '● LIVE' : '■ DOWN' ?>
                                </span>
                            </div>
                        </div>
                        <div class="metric-icon-wrap">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                        </div>
                    </div>
                </div>

                <!-- Recent Reservations Card -->
                <div class="items-card">
                    <div style="padding: 18px 22px; border-bottom: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <h3 style="font-family:var(--font-heading); font-size:1.25rem; color:var(--primary-accent); margin:0;">Recent Table Reservations & Inquiries</h3>
                            <p style="font-size:0.78rem; color:var(--text-secondary); margin:2px 0 0;">Customer requests captured via the live menu pop-up form</p>
                        </div>
                        <button type="button" class="btn-secondary" onclick="switchWorkspace('workspace-leads')">View All Leads &rarr;</button>
                    </div>

                    <?php if (empty($leadsData)): ?>
                        <div style="padding:36px; text-align:center; color:var(--text-secondary);">
                            No table inquiries collected yet. The pop-up form is active on the menu page.
                        </div>
                    <?php else: ?>
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Guest Name</th>
                                    <th>Contact</th>
                                    <th>Party Size</th>
                                    <th>Preferred Date & Slot</th>
                                    <th>Status</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($leadsData, 0, 5) as $lead): ?>
                                    <?php 
                                        $cleanPhone = preg_replace('/[^0-9]/', '', $lead['phone'] ?? '');
                                        $statusClass = strtolower($lead['status'] ?? 'pending');
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($lead['name'] ?? 'Guest') ?></strong></td>
                                        <td>
                                            <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" style="color:var(--primary-accent); font-weight:700; text-decoration:none;" title="Click to chat on WhatsApp">
                                                <?= htmlspecialchars($lead['phone'] ?? '') ?> ↗
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($lead['guests'] ?? '2 Guests') ?></td>
                                        <td><?= htmlspecialchars($lead['date'] ?? '') ?> &bull; <?= htmlspecialchars($lead['time'] ?? '') ?></td>
                                        <td><span class="status-badge <?= $statusClass ?>"><?= ucfirst($statusClass) ?></span></td>
                                        <td style="text-align:right;">
                                            <button type="button" class="btn-secondary" style="padding:4px 10px; font-size:0.75rem;" onclick="switchWorkspace('workspace-leads')">Manage</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Page Controller Quick Summary Card -->
                <div class="items-card" style="padding:22px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
                        <div>
                            <h3 style="font-family:var(--font-heading); font-size:1.25rem; color:var(--primary-accent); margin:0 0 4px;">Live Page Controller Quick Status</h3>
                            <p style="font-size:0.82rem; color:var(--text-secondary); margin:0;">Toggle public page accessibility or show luxury maintenance screen when curating menus.</p>
                        </div>
                        <button type="button" class="btn-gold" onclick="switchWorkspace('workspace-pages')">Configure Page Controller &rarr;</button>
                    </div>
                </div>
            </div>

            <!-- ===============================================================
                 WORKSPACE 2: MENU DISHES
                 =============================================================== -->
            <div class="workspace-panel" id="workspace-dishes">
                <section class="control-bar">
                    <div class="search-box">
                        <svg class="search-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" id="dishSearchInput" class="search-input" placeholder="Search dishes, descriptions, prices...">
                    </div>

                    <div class="filter-pills" id="dishFilterPillContainer">
                        <button type="button" class="pill-btn active" data-category="ALL">All (<?= $totalDishes ?>)</button>
                        <?php foreach ($distinctBadges as $cat): ?>
                            <?php $count = count(array_filter($catalog, fn($i) => ($i['badge'] ?? '') === $cat)); ?>
                            <button type="button" class="pill-btn" data-category="<?= htmlspecialchars($cat) ?>">
                                <?= htmlspecialchars($cat) ?> (<?= $count ?>)
                            </button>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php
                // Group dishes by category according to category definitions sequence
                $categoryBuckets = [];
                foreach ($categoriesData as $c) {
                    $cName = $c['name'];
                    $categoryBuckets[$cName] = [
                        'meta' => $c,
                        'items' => []
                    ];
                }
                foreach ($catalog as $item) {
                    $b = $item['badge'] ?? 'General';
                    if (!isset($categoryBuckets[$b])) {
                        $categoryBuckets[$b] = [
                            'meta' => ['name' => $b, 'image' => '', 'animation' => 'none'],
                            'items' => []
                        ];
                    }
                    $categoryBuckets[$b]['items'][] = $item;
                }

                // Sort dishes inside each category by explicit order sequence
                foreach ($categoryBuckets as $cName => &$bucket) {
                    usort($bucket['items'], function($a, $b) {
                        $ordA = isset($a['order']) && is_numeric($a['order']) ? (int)$a['order'] : 999;
                        $ordB = isset($b['order']) && is_numeric($b['order']) ? (int)$b['order'] : 999;
                        if ($ordA === $ordB) return 0;
                        return ($ordA < $ordB) ? -1 : 1;
                    });
                }
                unset($bucket);
                ?>

                <div class="category-wrappers-container" id="categoryWrappersContainer">
                    <?php foreach ($categoryBuckets as $catName => $bucket): ?>
                        <?php 
                            $catItems = $bucket['items'];
                            $catCount = count($catItems);
                            $catMeta = $bucket['meta'];
                            $catImg = $catMeta['image'] ?? '';
                            $resolvedCatImg = '';
                            if ($catImg) {
                                $resolvedCatImg = str_starts_with($catImg, 'http') ? $catImg : '../public/' . ltrim($catImg, '/');
                            }
                            $catAnim = $catMeta['animation'] ?? 'none';
                            $safeCatId = 'cat_wrap_' . md5($catName);
                        ?>
                        <div class="category-block-wrap" data-category-name="<?= htmlspecialchars(strtolower($catName)) ?>" id="<?= $safeCatId ?>">
                            <div class="cat-block-header" onclick="toggleCategoryWrap('<?= $safeCatId ?>')">
                                <div class="cat-block-left">
                                    <span class="cat-toggle-arrow">▼</span>
                                    <?php if (!empty($resolvedCatImg)): ?>
                                        <img src="<?= htmlspecialchars($resolvedCatImg) ?>" class="cat-block-thumb" alt="" onerror="this.style.display='none'">
                                    <?php endif; ?>
                                    <div>
                                        <div class="cat-block-title-row">
                                            <span class="cat-block-name"><?= htmlspecialchars($catName) ?></span>
                                            <span class="cat-block-count-badge"><?= $catCount ?> dishes</span>
                                            <?php if ($catAnim !== 'none'): ?>
                                                <span class="cat-block-anim-badge">✨ <?= htmlspecialchars(ucwords(str_replace('_', ' ', $catAnim))) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($catMeta['subtitle'])): ?>
                                            <div class="cat-block-desc"><?= htmlspecialchars($catMeta['subtitle']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="cat-block-right" onclick="event.stopPropagation();">
                                    <button type="button" class="btn-primary" style="padding: 5px 12px; font-size: 0.78rem;" onclick="openDishModalForCategory('<?= htmlspecialchars(addslashes($catName)) ?>')">
                                        + Add Dish
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Renumber all dishes in <?= htmlspecialchars(addslashes($catName)) ?> sequentially from 1 to <?= $catCount ?>?');">
                                        <input type="hidden" name="action" value="dish_renumber_category">
                                        <input type="hidden" name="category" value="<?= htmlspecialchars($catName) ?>">
                                        <button type="submit" class="btn-secondary" style="padding: 5px 10px; font-size: 0.75rem;" title="Reset sequence 1..N">
                                            🔢 1..N
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="cat-block-body">
                                <?php if (empty($catItems)): ?>
                                    <div style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 0.88rem;">
                                        No dishes added to this category yet.
                                        <button type="button" class="btn-secondary" style="margin-left: 8px; font-size: 0.78rem;" onclick="openDishModalForCategory('<?= htmlspecialchars(addslashes($catName)) ?>')">+ Add First Dish</button>
                                    </div>
                                <?php else: ?>
                                    <div class="items-card" style="margin:0; border:none; border-radius:0 0 12px 12px; overflow-x:auto;">
                                        <table class="items-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 90px; text-align: center;">Order / Shuffle</th>
                                                    <th>Dish / Item Details</th>
                                                    <th>Card Styling & Animation</th>
                                                    <th>Price</th>
                                                    <th>Dietary</th>
                                                    <th>Status</th>
                                                    <th style="text-align: right;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($catItems as $pos => $item): ?>
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
                                                        $isChefSpecial = !empty($item['is_chef_special']);
                                                        $chefSpecialNote = $item['chef_special_note'] ?? '';
                                                        $cardStyle = $item['card_style'] ?? [];
                                                        $cardAnim = $cardStyle['animation'] ?? 'none';
                                                        $cardBg = $cardStyle['bg_color'] ?? '';
                                                        $cardBorder = $cardStyle['border_color'] ?? '';
                                                        $orderNum = (int)($item['order'] ?? ($pos + 1));
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
                                                        
                                                        <!-- Sequence & Shuffle Buttons -->
                                                        <td style="text-align: center; vertical-align: middle;">
                                                            <div class="order-shuffle-controls">
                                                                <span class="order-seq-badge">#<?= $orderNum ?></span>
                                                                <div class="order-shuffle-btns">
                                                                    <form method="POST" style="display:inline;">
                                                                        <input type="hidden" name="action" value="dish_reorder">
                                                                        <input type="hidden" name="id" value="<?= htmlspecialchars($itemId) ?>">
                                                                        <input type="hidden" name="direction" value="up">
                                                                        <button type="submit" class="order-btn" title="Move Up" <?= ($pos === 0) ? 'disabled style="opacity:0.3;cursor:not-allowed;"' : '' ?>>▲</button>
                                                                    </form>
                                                                    <form method="POST" style="display:inline;">
                                                                        <input type="hidden" name="action" value="dish_reorder">
                                                                        <input type="hidden" name="id" value="<?= htmlspecialchars($itemId) ?>">
                                                                        <input type="hidden" name="direction" value="down">
                                                                        <button type="submit" class="order-btn" title="Move Down" <?= ($pos === $catCount - 1) ? 'disabled style="opacity:0.3;cursor:not-allowed;"' : '' ?>>▼</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        <!-- Dish Details -->
                                                        <td>
                                                            <div class="dish-cell">
                                                                <img src="<?= htmlspecialchars($resolvedImg) ?>" alt="<?= htmlspecialchars($itemName) ?>" class="dish-thumb" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=120&q=80'">
                                                                <div class="dish-meta">
                                                                    <div class="dish-name">
                                                                        <?= htmlspecialchars($itemName) ?>
                                                                        <?php if ($isChefSpecial): ?>
                                                                            <span class="badge-chef-special" title="Chef's Special with 3D Metallic Paper Clip Tag">
                                                                                📎 Chef's Special
                                                                            </span>
                                                                        <?php endif; ?>
                                                                        <?php if ($isFeatured): ?>
                                                                            <span title="Chef's Featured Item" style="color: var(--secondary-accent); font-size: 1rem; margin-left: 4px;">★</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <?php if ($isChefSpecial && !empty($chefSpecialNote)): ?>
                                                                        <div style="font-family:'Caveat',cursive; color:#875723; font-size:0.85rem; font-weight:600;">
                                                                            Parchment Note: &ldquo;<?= htmlspecialchars($chefSpecialNote) ?>&rdquo;
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <?php if ($itemTagline): ?>
                                                                        <div style="font-family:'Caveat',cursive; color:#a67c52; font-size:0.92rem;">&ldquo;<?= htmlspecialchars($itemTagline) ?>&rdquo;</div>
                                                                    <?php endif; ?>
                                                                    <div class="dish-desc"><?= htmlspecialchars($itemDesc) ?></div>
                                                                    <?php if (!empty($cFields)): ?>
                                                                        <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:4px;">
                                                                            <?php foreach ($cFields as $cf): ?>
                                                                                <?php if (!empty($cf['name'])): ?>
                                                                                    <span style="font-size:0.68rem; background:#f0eae1; color:#5c4736; padding:2px 6px; border-radius:4px; font-weight:700;">
                                                                                        <?= htmlspecialchars($cf['name']) ?>: <?= htmlspecialchars($cf['value'] ?? '') ?>
                                                                                    </span>
                                                                                <?php endif; ?>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        <!-- Styling & Animation column -->
                                                        <td>
                                                            <div class="admin-card-style-cell">
                                                                <?php if ($cardAnim !== 'none'): ?>
                                                                    <span class="anim-pill anim-pill-<?= htmlspecialchars($cardAnim) ?>">
                                                                        ✨ <?= htmlspecialchars(ucwords(str_replace('_', ' ', $cardAnim))) ?>
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="anim-pill-muted">Static</span>
                                                                <?php endif; ?>

                                                                <?php if (!empty($cardBg) || !empty($cardBorder)): ?>
                                                                    <div class="color-dot-indicator" title="Custom Colors: BG <?= htmlspecialchars($cardBg ?: 'default') ?> | Border <?= htmlspecialchars($cardBorder ?: 'default') ?>">
                                                                        <span class="color-dot-sample" style="background:<?= htmlspecialchars($cardBg ?: '#FAF5EB') ?>; border:1.5px solid <?= htmlspecialchars($cardBorder ?: 'rgba(104,20,24,0.3)') ?>;"></span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>

                                                        <!-- Price -->
                                                        <td><span class="price-tag"><?= htmlspecialchars($itemPrice) ?></span></td>

                                                        <!-- Dietary -->
                                                        <td>
                                                            <span class="diet-badge <?= $isVeg ? 'veg' : 'non-veg' ?>">
                                                                <span><?= $isVeg ? '●' : '▲' ?></span>
                                                                <span><?= $isVeg ? 'Vegetarian' : 'Non-Veg' ?></span>
                                                            </span>
                                                        </td>

                                                        <!-- Status -->
                                                        <td>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="action" value="dish_toggle">
                                                                <input type="hidden" name="field" value="available">
                                                                <input type="hidden" name="id" value="<?= htmlspecialchars($itemId) ?>">
                                                                <button type="submit" class="status-badge <?= $isAvailable ? 'available' : 'unavailable' ?>" style="border:none; cursor:pointer;" title="Click to toggle availability">
                                                                    <span class="status-dot"></span>
                                                                    <span><?= $isAvailable ? 'Active' : 'Hidden' ?></span>
                                                                </button>
                                                            </form>
                                                        </td>

                                                        <!-- Actions -->
                                                        <td style="text-align: right;">
                                                            <div class="row-actions" style="justify-content: flex-end;">
                                                                <form method="POST" style="display:inline;" title="Toggle Featured">
                                                                    <input type="hidden" name="action" value="dish_toggle">
                                                                    <input type="hidden" name="field" value="featured">
                                                                    <input type="hidden" name="id" value="<?= htmlspecialchars($itemId) ?>">
                                                                    <button type="submit" class="action-icon-btn" style="color: <?= $isFeatured ? 'var(--secondary-accent)' : '#9c9890' ?>;">★</button>
                                                                </form>

                                                                <button type="button" class="action-icon-btn edit-dish-btn" title="Edit Item & Card Styling">
                                                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                                </button>

                                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete \'<?= htmlspecialchars(addslashes($itemName)) ?>\'?');" style="display:inline;">
                                                                    <input type="hidden" name="action" value="dish_delete">
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
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ===============================================================
                 WORKSPACE 3: CATEGORIES & SECTIONS
                 =============================================================== -->
            <div class="workspace-panel" id="workspace-categories">
                <div style="background:#ffffff; border:1px solid var(--border-color); border-radius:var(--radius-md); padding:18px 22px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                    <div>
                        <h2 style="font-family:var(--font-heading); font-size:1.45rem; color:var(--primary-accent); margin:0 0 4px;">Menu Categories & Section Presentations</h2>
                        <p style="font-size:0.85rem; color:var(--text-secondary); margin:0;">
                            Customize section titles, eyebrow headers (e.g. <em>"OUR SIGNATURES"</em>), handwritten cursive quotes (e.g. <em>"More than just Bread"</em>), cover images, and sort ordering.
                        </p>
                    </div>
                    <button type="button" class="btn-primary" onclick="openCategoryModal(false)">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>+ Create Category</span>
                    </button>
                </div>

                <div class="category-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:20px;">
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
                        <div class="category-admin-card" style="background:#fff; border:1px solid var(--border-color); border-radius:var(--radius-md); overflow:hidden; box-shadow:var(--shadow-subtle); display:flex; flex-direction:column;" data-raw='<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>'>
                            <div style="position:relative; height:140px; background:#1a0a0c; overflow:hidden;">
                                <img src="<?= htmlspecialchars($resolvedCImg) ?>" alt="<?= htmlspecialchars($cName) ?>" style="width:100%; height:100%; object-fit:cover; opacity:0.85;" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80'">
                                <div style="position:absolute; inset:0; background:linear-gradient(180deg, rgba(0,0,0,0.1) 0%, rgba(104,20,24,0.7) 100%);"></div>
                                <span style="position:absolute; top:12px; left:12px; background:rgba(104,20,24,0.9); color:#fff; font-size:0.65rem; font-weight:800; letter-spacing:1.5px; text-transform:uppercase; padding:4px 10px; border-radius:12px; backdrop-filter:blur(4px);">
                                    <?= htmlspecialchars($cEyebrow) ?>
                                </span>
                                <span style="position:absolute; top:12px; right:12px; background:rgba(0,0,0,0.6); color:#fff; font-size:0.70rem; font-weight:700; padding:3px 8px; border-radius:6px;">
                                    #<?= $cOrder ?>
                                </span>
                            </div>

                            <div style="padding:16px 18px; display:flex; flex-direction:column; flex:1;">
                                <h3 style="font-family:var(--font-heading); font-size:1.35rem; color:var(--primary-accent); margin:0 0 4px; display:flex; justify-content:space-between; align-items:center;">
                                    <span><?= htmlspecialchars($cName) ?></span>
                                    <span style="font-size:0.75rem; font-family:var(--font-body); font-weight:700; color:#8a7153;"><?= $itemCount ?> dishes</span>
                                </h3>
                                <?php if ($cScript): ?>
                                    <div style="font-family:'Caveat',cursive; font-size:1.15rem; color:#a67c52; margin-bottom:8px; font-style:italic;">&ldquo;<?= htmlspecialchars($cScript) ?>&rdquo;</div>
                                <?php endif; ?>
                                <p style="font-size:0.82rem; color:var(--text-secondary); line-height:1.45; margin-bottom:14px;"><?= htmlspecialchars($cSub) ?></p>

                                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:auto; padding-top:12px; border-top:1px solid var(--border-color);">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="category_toggle">
                                        <input type="hidden" name="cat_id" value="<?= htmlspecialchars($cId) ?>">
                                        <button type="submit" class="status-badge <?= $cActive ? 'available' : 'unavailable' ?>" style="border:none; cursor:pointer;" title="Toggle category visibility">
                                            <span class="status-dot"></span>
                                            <span><?= $cActive ? 'Visible' : 'Hidden' ?></span>
                                        </button>
                                    </form>

                                    <div class="row-actions">
                                        <button type="button" class="action-icon-btn edit-category-btn" title="Edit Category">
                                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>

                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete category \'<?= htmlspecialchars(addslashes($cName)) ?>\'?');" style="display:inline;">
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

            <!-- ===============================================================
                 WORKSPACE 4: POP-UP & FORM STUDIO (4 LAYOUTS + ELEMENT TOGGLES)
                 =============================================================== -->
            <div class="workspace-panel" id="workspace-popups">
                <div style="background:#ffffff; border:1px solid var(--border-color); border-radius:var(--radius-md); padding:18px 22px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                    <div>
                        <h2 style="font-family:var(--font-heading); font-size:1.45rem; color:var(--primary-accent); margin:0 0 4px;">Pop-Up & Form Studio</h2>
                        <p style="font-size:0.85rem; color:var(--text-secondary); margin:0;">
                            Create & customize lead collection forms (table reservations, private tastings) page-wise with 4 layout options and full element toggle control.
                        </p>
                    </div>
                    <button type="button" class="btn-primary" onclick="openPopupModal(false)">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>+ Create Pop-Up Form</span>
                    </button>
                </div>

                <div class="items-card">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Form Name & Copy</th>
                                <th>Target Page</th>
                                <th>Layout Style</th>
                                <th>Trigger Type</th>
                                <th>Status</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($popupsData as $pop): ?>
                                <?php 
                                    $pId = $pop['id'] ?? '';
                                    $pType = $pop['type'] ?? 'reservation';
                                    $pName = $pop['name'] ?? 'Untitled Form';
                                    $pTarget = $pop['target_page'] ?? 'menu.php';
                                    $pLayout = $pop['layout'] ?? 'center_modal';
                                    $pTrigger = $pop['trigger_type'] ?? 'both';
                                    $pActive = !empty($pop['active']);
                                    $pTitle = $pop['title'] ?? '';
                                    $pPromo = $pop['promo_code'] ?? '';
                                    $pDisc = $pop['discount_badge'] ?? '';

                                    $layoutNames = [
                                        'center_modal' => 'Center Floating Modal',
                                        'bottom_sheet' => 'Slide-up Bottom Sheet',
                                        'side_drawer' => 'Side Fly-in Panel',
                                        'fullscreen' => 'Fullscreen Takeover'
                                    ];
                                ?>
                                <tr data-raw='<?= htmlspecialchars(json_encode($pop), ENT_QUOTES, 'UTF-8') ?>'>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:3px;">
                                            <span style="display:inline-block; font-size:0.68rem; font-weight:800; padding:2px 8px; border-radius:10px; text-transform:uppercase; letter-spacing:0.5px; background:<?= $pType === 'offer' ? 'rgba(201,154,104,0.2)' : 'rgba(104,20,24,0.1)' ?>; color:<?= $pType === 'offer' ? '#8c5d26' : '#681418' ?>;">
                                                <?= $pType === 'offer' ? '🎁 Offer Voucher' : '🍽 Table Reservation' ?>
                                            </span>
                                            <?php if ($pType === 'offer' && !empty($pPromo)): ?>
                                                <span style="font-family:monospace; font-size:0.75rem; font-weight:800; background:#FFF8EB; border:1px dashed #c99a68; color:#681418; padding:1px 6px; border-radius:4px;">
                                                    <?= htmlspecialchars($pPromo) ?> (<?= htmlspecialchars($pDisc ?: 'Promo') ?>)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-weight:700; color:var(--text-primary); font-size:0.92rem;"><?= htmlspecialchars($pName) ?></div>
                                        <div style="font-size:0.78rem; color:var(--text-secondary);">&ldquo;<?= htmlspecialchars($pTitle) ?>&rdquo;</div>
                                    </td>
                                    <td>
                                        <span class="category-tag" style="background:#FAF7F2; border:1px solid var(--border-color); color:#4a3e35;">
                                            📄 <?= htmlspecialchars($pTarget) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-size:0.80rem; font-weight:700; color:var(--primary-accent); background:rgba(104,20,24,0.06); padding:4px 10px; border-radius:12px;">
                                            <?= htmlspecialchars($layoutNames[$pLayout] ?? $pLayout) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-size:0.75rem; color:#6e6b66; text-transform:uppercase; font-weight:600;">
                                            <?= htmlspecialchars($pTrigger) ?> (<?= intval($pop['trigger_delay_sec'] ?? 6) ?>s)
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="popup_toggle">
                                            <input type="hidden" name="popup_id" value="<?= htmlspecialchars($pId) ?>">
                                            <button type="submit" class="status-badge <?= $pActive ? 'available' : 'unavailable' ?>" style="border:none; cursor:pointer;">
                                                <span class="status-dot"></span>
                                                <span><?= $pActive ? 'Active' : 'Disabled' ?></span>
                                            </button>
                                        </form>
                                    </td>
                                    <td style="text-align:right;">
                                        <div class="row-actions" style="justify-content:flex-end;">
                                            <button type="button" class="action-icon-btn edit-popup-btn" title="Edit Pop-Up & Elements">
                                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>

                                            <form method="POST" onsubmit="return confirm('Delete pop-up form \'<?= htmlspecialchars(addslashes($pName)) ?>\'?');" style="display:inline;">
                                                <input type="hidden" name="action" value="popup_delete">
                                                <input type="hidden" name="popup_id" value="<?= htmlspecialchars($pId) ?>">
                                                <button type="submit" class="action-icon-btn delete-btn" title="Delete Pop-up">
                                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ===============================================================
                 WORKSPACE 5: LEADS & RESERVATIONS INBOX
                 =============================================================== -->
            <div class="workspace-panel" id="workspace-leads">
                <div style="background:#ffffff; border:1px solid var(--border-color); border-radius:var(--radius-md); padding:18px 22px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                    <div>
                        <h2 style="font-family:var(--font-heading); font-size:1.45rem; color:var(--primary-accent); margin:0 0 4px;">Table Reservations & Guest Inquiries</h2>
                        <p style="font-size:0.85rem; color:var(--text-secondary); margin:0;">
                            Live stream of table requests. Connect with guests directly on WhatsApp with one click.
                        </p>
                    </div>
                </div>

                <div class="items-card">
                    <?php if (empty($leadsData)): ?>
                        <div style="padding:48px; text-align:center; color:var(--text-secondary);">
                            No inquiries recorded yet. Once guests submit the reservation pop-up, their requests will appear here in real time.
                        </div>
                    <?php else: ?>
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Guest & Contact</th>
                                    <th>Party Size</th>
                                    <th>Booking Date & Slot</th>
                                    <th>Special Requests / Notes</th>
                                    <th>Source Page</th>
                                    <th>Status</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leadsData as $l): ?>
                                    <?php 
                                        $lId = $l['id'] ?? '';
                                        $lName = $l['name'] ?? 'Guest';
                                        $lPhone = $l['phone'] ?? '';
                                        $lCleanPhone = preg_replace('/[^0-9]/', '', $lPhone);
                                        $lGuests = $l['guests'] ?? '2 Guests';
                                        $lDate = $l['date'] ?? '';
                                        $lTime = $l['time'] ?? '';
                                        $lNotes = $l['notes'] ?? '';
                                        $lPage = $l['page'] ?? 'menu.php';
                                        $lStatus = strtolower($l['status'] ?? 'pending');
                                        $waReplyText = "Hello {$lName}, thank you for choosing Orah House! We have received your table reservation request for {$lGuests} on {$lDate} ({$lTime}).";
                                        $waLink = "https://wa.me/{$lCleanPhone}?text=" . urlencode($waReplyText);
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:700; color:var(--text-primary); font-size:0.92rem;"><?= htmlspecialchars($lName) ?></div>
                                            <a href="<?= htmlspecialchars($waLink) ?>" target="_blank" rel="noopener" style="font-size:0.80rem; color:var(--primary-accent); font-weight:700; text-decoration:none;" title="Direct WhatsApp Chat">
                                                💬 <?= htmlspecialchars($lPhone) ?> ↗
                                            </a>
                                        </td>
                                        <td><strong><?= htmlspecialchars($lGuests) ?></strong></td>
                                        <td>
                                            <div><strong><?= htmlspecialchars($lDate) ?></strong></div>
                                            <div style="font-size:0.75rem; color:var(--text-secondary);"><?= htmlspecialchars($lTime) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($lNotes): ?>
                                                <span style="font-size:0.80rem; color:#4a3e35; background:#FAF7F2; padding:4px 8px; border-radius:6px; display:inline-block; max-width:240px;">
                                                    &ldquo;<?= htmlspecialchars($lNotes) ?>&rdquo;
                                                </span>
                                            <?php else: ?>
                                                <span style="color:#a89f91; font-size:0.75rem;">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-size:0.72rem; font-family:monospace; color:#6e6b66;"><?= htmlspecialchars($lPage) ?></span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="lead_status">
                                                <input type="hidden" name="lead_id" value="<?= htmlspecialchars($lId) ?>">
                                                <select name="status" onchange="this.form.submit()" style="padding:4px 8px; font-size:0.75rem; font-weight:700; border-radius:8px; border:1px solid var(--border-color); background:#fff; cursor:pointer;">
                                                    <option value="pending" <?= $lStatus === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
                                                    <option value="confirmed" <?= $lStatus === 'confirmed' ? 'selected' : '' ?>>✓ Confirmed</option>
                                                    <option value="completed" <?= $lStatus === 'completed' ? 'selected' : '' ?>>★ Completed</option>
                                                    <option value="cancelled" <?= $lStatus === 'cancelled' ? 'selected' : '' ?>>✕ Cancelled</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td style="text-align:right;">
                                            <form method="POST" onsubmit="return confirm('Delete this inquiry record?');" style="display:inline;">
                                                <input type="hidden" name="action" value="lead_delete">
                                                <input type="hidden" name="lead_id" value="<?= htmlspecialchars($lId) ?>">
                                                <button type="submit" class="action-icon-btn delete-btn" title="Delete Inquiry">
                                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===============================================================
                 WORKSPACE 6: PAGE CONTROLLER (LIVE / DOWN STATUS)
                 =============================================================== -->
            <div class="workspace-panel" id="workspace-pages">
                <form method="POST">
                    <input type="hidden" name="action" value="page_controller_save">

                    <div style="background:#ffffff; border:1px solid var(--border-color); border-radius:var(--radius-md); padding:18px 22px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                        <div>
                            <h2 style="font-family:var(--font-heading); font-size:1.45rem; color:var(--primary-accent); margin:0 0 4px;">Page Accessibility Controller</h2>
                            <p style="font-size:0.85rem; color:var(--text-secondary); margin:0;">
                                Instantly toggle public pages between <strong>LIVE</strong> and <strong>DOWN</strong> (maintenance/private tasting mode).
                            </p>
                        </div>
                        <button type="submit" class="btn-primary">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            <span>Save Page Settings</span>
                        </button>
                    </div>

                    <div class="page-controller-grid">
                        <!-- Card 1: menu.php -->
                        <?php 
                            $mConfig = $pagesData['menu.php'] ?? [];
                            $mLive = ($mConfig['status'] ?? 'live') === 'live';
                        ?>
                        <div class="page-ctrl-card <?= $mLive ? 'is-live' : 'is-down' ?>" id="pageCard_menu_php">
                            <div class="page-ctrl-header">
                                <div>
                                    <h3 class="page-ctrl-title">Menu Page</h3>
                                    <div class="page-ctrl-url">public/menu.php</div>
                                </div>
                                <div class="switch-wrap">
                                    <span class="status-badge-text" id="statusBadge_menu_php" style="font-size:0.75rem; font-weight:800; letter-spacing:0.5px; padding:4px 10px; border-radius:12px; background:<?= $mLive ? 'rgba(34,197,94,0.12)' : 'rgba(239,68,68,0.15)' ?>; color:<?= $mLive ? '#15803d' : '#b91c1c' ?>;">
                                        <?= $mLive ? '● LIVE' : '■ DOWN' ?>
                                    </span>
                                    <label class="switch" title="Toggle Live or Down">
                                        <input type="hidden" name="page_status[menu.php]" value="down">
                                        <input type="checkbox" name="page_status[menu.php]" value="live" id="pageToggle_menu_php" data-page="menu.php" class="instant-page-switch" <?= $mLive ? 'checked' : '' ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>

                            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; padding-bottom:8px; border-bottom:1px solid var(--border-color);">
                                <button type="button" class="btn-quick-toggle <?= $mLive ? 'btn-set-down' : 'btn-set-live' ?>" onclick="quickTogglePage('menu.php', '<?= $mLive ? 'down' : 'live' ?>')">
                                    <?= $mLive ? '⚡ Put Menu DOWN (Maintenance)' : '✓ Put Menu LIVE' ?>
                                </button>
                                <a href="../public/menu.php" target="_blank" class="btn-preview-link" title="Open public/menu.php in new tab">
                                    <span>👁 View Menu Page ↗</span>
                                </a>
                            </div>

                            <div class="form-group">
                                <label class="form-label">When Down: Action to Perform</label>
                                <select name="page_down_action[menu.php]" class="form-select">
                                    <option value="maintenance" <?= ($mConfig['down_action'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Show Luxury Maintenance Page (Under Curation)</option>
                                    <option value="redirect" <?= ($mConfig['down_action'] ?? '') === 'redirect' ? 'selected' : '' ?>>Redirect to Alternate Live Page</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Redirect Target (if redirect selected)</label>
                                <input type="text" name="page_redirect[menu.php]" class="form-input" value="<?= htmlspecialchars($mConfig['redirect_to'] ?? 'index.php') ?>" placeholder="e.g. index.php">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Maintenance Notice Text</label>
                                <textarea name="page_down_msg[menu.php]" class="form-textarea" rows="3"><?= htmlspecialchars($mConfig['down_message'] ?? 'Our architectural menu is currently undergoing seasonal curation.') ?></textarea>
                            </div>
                        </div>

                        <!-- Card 2: index.php -->
                        <?php 
                            $iConfig = $pagesData['index.php'] ?? [];
                            $iLive = ($iConfig['status'] ?? 'live') === 'live';
                        ?>
                        <div class="page-ctrl-card <?= $iLive ? 'is-live' : 'is-down' ?>" id="pageCard_index_php">
                            <div class="page-ctrl-header">
                                <div>
                                    <h3 class="page-ctrl-title">Storefront Home</h3>
                                    <div class="page-ctrl-url">public/index.php</div>
                                </div>
                                <div class="switch-wrap">
                                    <span class="status-badge-text" id="statusBadge_index_php" style="font-size:0.75rem; font-weight:800; letter-spacing:0.5px; padding:4px 10px; border-radius:12px; background:<?= $iLive ? 'rgba(34,197,94,0.12)' : 'rgba(239,68,68,0.15)' ?>; color:<?= $iLive ? '#15803d' : '#b91c1c' ?>;">
                                        <?= $iLive ? '● LIVE' : '■ DOWN' ?>
                                    </span>
                                    <label class="switch" title="Toggle Live or Down">
                                        <input type="hidden" name="page_status[index.php]" value="down">
                                        <input type="checkbox" name="page_status[index.php]" value="live" id="pageToggle_index_php" data-page="index.php" class="instant-page-switch" <?= $iLive ? 'checked' : '' ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>

                            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; padding-bottom:8px; border-bottom:1px solid var(--border-color);">
                                <button type="button" class="btn-quick-toggle <?= $iLive ? 'btn-set-down' : 'btn-set-live' ?>" onclick="quickTogglePage('index.php', '<?= $iLive ? 'down' : 'live' ?>')">
                                    <?= $iLive ? '⚡ Put Home DOWN (Maintenance)' : '✓ Put Home LIVE' ?>
                                </button>
                                <a href="../public/index.php" target="_blank" class="btn-preview-link" title="Open public/index.php in new tab">
                                    <span>👁 View Storefront ↗</span>
                                </a>
                            </div>

                            <div class="form-group">
                                <label class="form-label">When Down: Action to Perform</label>
                                <select name="page_down_action[index.php]" class="form-select">
                                    <option value="maintenance" <?= ($iConfig['down_action'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Show Luxury Maintenance Page (Under Curation)</option>
                                    <option value="redirect" <?= ($iConfig['down_action'] ?? '') === 'redirect' ? 'selected' : '' ?>>Redirect to Alternate Live Page</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Redirect Target (if redirect selected)</label>
                                <input type="text" name="page_redirect[index.php]" class="form-input" value="<?= htmlspecialchars($iConfig['redirect_to'] ?? 'menu.php') ?>" placeholder="e.g. menu.php">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Maintenance Notice Text</label>
                                <textarea name="page_down_msg[index.php]" class="form-textarea" rows="3"><?= htmlspecialchars($iConfig['down_message'] ?? 'Storefront is temporarily offline for a private event.') ?></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

        </main>
    </div>
</div>

<!-- ===========================================================================
     MODAL 1: DISH FORM (ADD / EDIT)
     =========================================================================== -->
<div class="modal-backdrop" id="dishModal">
    <div class="modal-card">
        <form method="POST" enctype="multipart/form-data" id="dishForm">
            <input type="hidden" name="action" value="dish_save">
            <input type="hidden" name="id" id="dishFormId" value="">

            <div class="modal-header">
                <h3 class="modal-title" id="dishModalTitle">Add New Dish</h3>
                <button type="button" class="modal-close-btn" onclick="closeModal('dishModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Dish Name *</label>
                    <input type="text" name="name" id="dishNameInput" class="form-input" placeholder="e.g. Artisanal Mozzarella Flatbread" required>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <input list="categoryDatalist" name="badge" id="dishBadgeInput" class="form-input" placeholder="e.g. Flat Breads" required>
                        <datalist id="categoryDatalist">
                            <?php foreach ($categoriesData as $c): ?>
                                <option value="<?= htmlspecialchars($c['name']) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Price (INR) *</label>
                        <input type="text" name="price" id="dishPriceInput" class="form-input" placeholder="e.g. ₹380" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Dish Tagline (Cursive script on card)</label>
                    <input type="text" name="tagline" id="dishTaglineInput" class="form-input" placeholder="e.g. Crispy edges, Endless flavour">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="dishDescInput" class="form-textarea" rows="2" placeholder="Flavor craft, slow baking, pantry notes..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Dish Photograph (Upload File or Enter URL)</label>
                    <input type="file" name="image_file" class="form-input" accept="image/*" style="margin-bottom: 8px;">
                    <input type="text" name="image_url" id="dishUrlInput" class="form-input" placeholder="Or enter image URL (e.g. uploads/dish.jpg or Unsplash)">
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

                <!-- CHEF'S SPECIAL & 3D BRASS PAPER CLIP CONTROLS -->
                <div style="background:#FFFDF8; border:1.5px solid #d4af37; border-radius:var(--radius-md); padding:14px; margin-top:12px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                        <label class="custom-checkbox" style="margin:0;">
                            <input type="checkbox" name="is_chef_special" id="dishChefSpecialCheck">
                            <span style="font-weight:700; color:#581116;">✦ Mark as Chef's Special (Held with 3D Brass Paper Clip)</span>
                        </label>
                        <span style="font-size:1.15rem;">📎</span>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:0.78rem;">Parchment Note Subtitle / Artisan Recommendation</label>
                        <input type="text" name="chef_special_note" id="dishChefSpecialNoteInput" class="form-input" placeholder="e.g. Signature Artisan Craft or Head Chef's Pick">
                    </div>
                </div>

                <!-- ORDER / SHUFFLE SEQUENCE & ANIMATION SELECTOR -->
                <div class="form-row-2" style="margin-top:12px;">
                    <div class="form-group">
                        <label class="form-label">Category Display Sequence / Order # *</label>
                        <input type="number" name="order" id="dishOrderInput" class="form-input" min="1" value="1" placeholder="e.g. 1, 2, 3..." required>
                        <small style="font-size:0.72rem; color:#8c8277;">Determines display order (01, 02...) within category.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Card Dynamic Animation</label>
                        <select name="card_animation" id="dishCardAnimSelect" class="form-select">
                            <option value="none">Standard Luxury Static</option>
                            <option value="gradient_shimmer">✨ Gradient Shimmer (Flowing Gold & Burgundy)</option>
                            <option value="gold_aura">✨ Golden Aura (Ambient Breathing Glow)</option>
                            <option value="burgundy_pulse">✨ Burgundy Pulse (Rhythmic Soft Glow)</option>
                            <option value="color_shift">✨ Color Shift (Rotating Border Spectrum)</option>
                            <option value="floating_tilt">✨ Floating Tilt (3D Elevation on Hover)</option>
                        </select>
                    </div>
                </div>

                <!-- CARD CUSTOM COLOR STYLING OVERRIDES -->
                <div style="background:#FAF7F2; border:1px solid var(--border-color); border-radius:var(--radius-md); padding:14px; margin-top:12px;">
                    <span style="font-size:0.82rem; font-weight:700; color:var(--primary-accent); display:block; margin-bottom:10px;">
                        🎨 Card Custom Color Styling (Optional Overrides)
                    </span>
                    <div class="form-row-2" style="margin-bottom:0;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" style="font-size:0.75rem;">Card Background Color</label>
                            <div style="display:flex; gap:8px;">
                                <input type="color" id="dishCardBgPicker" value="#FAF5EB" style="width:38px; height:38px; padding:0; border:none; border-radius:6px; cursor:pointer;" oninput="document.getElementById('dishCardBgInput').value = this.value">
                                <input type="text" name="card_bg" id="dishCardBgInput" class="form-input" placeholder="Default or #hex" oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)) document.getElementById('dishCardBgPicker').value = this.value">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" style="font-size:0.75rem;">Card Border Color</label>
                            <div style="display:flex; gap:8px;">
                                <input type="color" id="dishCardBorderPicker" value="#c99a68" style="width:38px; height:38px; padding:0; border:none; border-radius:6px; cursor:pointer;" oninput="document.getElementById('dishCardBorderInput').value = this.value">
                                <input type="text" name="card_border" id="dishCardBorderInput" class="form-input" placeholder="Default or #hex" oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)) document.getElementById('dishCardBorderPicker').value = this.value">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DYNAMIC CUSTOM EXTRA FIELDS BUILDER -->
                <div style="background:#FAF7F2; border:1px solid var(--border-color); border-radius:var(--radius-md); padding:16px; margin-top:12px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <span style="font-size:0.85rem; font-weight:700; color:var(--primary-accent);">
                            Custom Extra Fields (Allergens, Spice Level, Chef Notes...)
                        </span>
                        <button type="button" class="btn-secondary" style="padding:4px 10px; font-size:0.75rem;" id="btnAddCfRow">+ Add Extra Field</button>
                    </div>
                    <div id="cfRowsContainer" style="display:flex; flex-direction:column; gap:8px;"></div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('dishModal')">Cancel</button>
                <button type="submit" class="btn-primary">Save Dish</button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================================
     MODAL 2: CATEGORY FORM (ADD / EDIT)
     =========================================================================== -->
<div class="modal-backdrop" id="categoryModal">
    <div class="modal-card">
        <form method="POST" enctype="multipart/form-data" id="categoryForm">
            <input type="hidden" name="action" value="category_save">
            <input type="hidden" name="cat_id" id="catFormId" value="">
            <input type="hidden" name="cat_old_name" id="catFormOldName" value="">

            <div class="modal-header">
                <h3 class="modal-title" id="catModalTitle">Create Category</h3>
                <button type="button" class="modal-close-btn" onclick="closeModal('categoryModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="cat_name" id="catNameInput" class="form-input" placeholder="e.g. Flat Breads" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Display Order Sequence</label>
                        <input type="number" name="cat_order" id="catOrderInput" class="form-input" value="1">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Eyebrow Header Text (All Caps) *</label>
                    <input type="text" name="cat_eyebrow" id="catEyebrowInput" class="form-input" value="OUR SIGNATURES" placeholder="e.g. OUR SIGNATURES, WOODFIRED ARTISAN..." required>
                    <small style="font-size:0.72rem; color:#8c8277;">Displays right above the category title on the public menu.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Handwritten Cursive Script Quote *</label>
                    <input type="text" name="cat_script_quote" id="catScriptInput" class="form-input" placeholder="e.g. More than just Bread, Blistered & Melted..." required>
                    <small style="font-size:0.72rem; color:#8c8277;">Rendered in cursive script with decorative underline flourish.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Category Description / Subtitle</label>
                    <textarea name="cat_subtitle" id="catSubtitleInput" class="form-textarea" rows="2" placeholder="Hand-stretched sourdough flatbreads baked with artisanal melts..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Cover Photograph (Upload or Enter URL)</label>
                    <input type="file" name="cat_image_file" class="form-input" accept="image/*" style="margin-bottom:8px;">
                    <input type="text" name="cat_image_url" id="catUrlInput" class="form-input" placeholder="Or enter URL (e.g. uploads/flatbread.jpg)">
                </div>

                <!-- CATEGORY CARD ANIMATION & STYLING -->
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Category Card Animation</label>
                        <select name="cat_animation" id="catAnimSelect" class="form-select">
                            <option value="none">Standard Luxury Static</option>
                            <option value="gradient_shimmer">✨ Gradient Shimmer (Flowing Gold & Burgundy)</option>
                            <option value="gold_aura">✨ Golden Aura (Ambient Breathing Glow)</option>
                            <option value="burgundy_pulse">✨ Burgundy Pulse (Rhythmic Soft Glow)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Custom Background / Border (Optional)</label>
                        <div style="display:flex; gap:8px;">
                            <input type="text" name="cat_card_bg" id="catCardBgInput" class="form-input" placeholder="BG #hex">
                            <input type="text" name="cat_border_color" id="catBorderInput" class="form-input" placeholder="Border #hex">
                        </div>
                    </div>
                </div>

                <div class="checkbox-group">
                    <label class="custom-checkbox">
                        <input type="checkbox" name="cat_active" id="catActiveCheck" checked>
                        <span>Visible on Menu</span>
                    </label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('categoryModal')">Cancel</button>
                <button type="submit" class="btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================================
     MODAL 3: POP-UP CREATOR & LAYOUT CUSTOMIZER (4 LAYOUTS + ELEMENT TOGGLES)
     =========================================================================== -->
<div class="modal-backdrop" id="popupModal">
    <div class="modal-card" style="max-width:680px;">
        <form method="POST" enctype="multipart/form-data" id="popupForm">
            <input type="hidden" name="action" value="popup_save">
            <input type="hidden" name="popup_id" id="popFormId" value="">

            <div class="modal-header">
                <h3 class="modal-title" id="popModalTitle">Pop-Up Form Creator</h3>
                <button type="button" class="modal-close-btn" onclick="closeModal('popupModal')">&times;</button>
            </div>

            <div class="modal-body">
                <!-- POP-UP TYPE SELECTOR (RESERVATION VS OFFER) -->
                <div class="form-group" style="background:#FAF7F2; border:1px solid var(--border-color); border-radius:14px; padding:14px 16px; margin-bottom:18px;">
                    <label class="form-label" style="margin-bottom:8px; display:flex; justify-content:space-between;">
                        <span>Pop-Up Campaign Type *</span>
                        <span style="font-size:0.75rem; color:#8c7355; font-weight:600;">Choose objective</span>
                    </label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                        <label class="type-choice-card selected" id="typeCard_reservation" style="display:flex; align-items:center; gap:10px; padding:12px 14px; border:2px solid #681418; border-radius:10px; background:#fff; cursor:pointer; transition:all 0.2s;">
                            <input type="radio" name="popup_type" value="reservation" id="typeRadio_reservation" checked onchange="switchPopupType('reservation')">
                            <div>
                                <div style="font-weight:700; font-size:0.88rem; color:#681418;">🍽 Table Reservation</div>
                                <div style="font-size:0.75rem; color:var(--text-secondary);">Guest inquiries & table booking form</div>
                            </div>
                        </label>
                        <label class="type-choice-card" id="typeCard_offer" style="display:flex; align-items:center; gap:10px; padding:12px 14px; border:1px solid var(--border-color); border-radius:10px; background:#fff; cursor:pointer; transition:all 0.2s;">
                            <input type="radio" name="popup_type" value="offer" id="typeRadio_offer" onchange="switchPopupType('offer')">
                            <div>
                                <div style="font-weight:700; font-size:0.88rem; color:#681418;">🎁 Special Offer / Voucher</div>
                                <div style="font-size:0.75rem; color:var(--text-secondary);">Discounts, coupon code & WhatsApp claim</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Form Campaign Name *</label>
                        <input type="text" name="popup_name" id="popNameInput" class="form-input" placeholder="e.g. Welcome Tasting 15% OFF" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Target Page *</label>
                        <select name="target_page" id="popTargetPageSelect" class="form-select">
                            <option value="menu.php" selected>Menu Page (menu.php)</option>
                            <option value="index.php">Storefront Home (index.php)</option>
                            <option value="all">Global (All Pages)</option>
                            <option value="none">Disabled (No Page)</option>
                        </select>
                    </div>
                </div>

                <!-- 4 LAYOUT OPTIONS SELECTOR -->
                <div class="form-group">
                    <label class="form-label">1. Choose Presentation Layout (4 Options)</label>
                    <div class="layout-selector-grid">
                        <label class="layout-choice-card selected" id="layoutCard_center_modal">
                            <input type="radio" name="popup_layout" value="center_modal" checked onchange="selectLayoutChoice('center_modal')">
                            <div class="layout-preview-box">
                                <div style="width:40px; height:32px; background:#681418; border-radius:4px;"></div>
                            </div>
                            <div class="layout-choice-title">Center Modal</div>
                            <div class="layout-choice-desc">Floating luxury card with hero image</div>
                        </label>

                        <label class="layout-choice-card" id="layoutCard_bottom_sheet">
                            <input type="radio" name="popup_layout" value="bottom_sheet" onchange="selectLayoutChoice('bottom_sheet')">
                            <div class="layout-preview-box" style="align-items:flex-end;">
                                <div style="width:60px; height:24px; background:#681418; border-radius:4px 4px 0 0;"></div>
                            </div>
                            <div class="layout-choice-title">Bottom Sheet</div>
                            <div class="layout-choice-desc">Mobile-native slide-up drawer</div>
                        </label>

                        <label class="layout-choice-card" id="layoutCard_side_drawer">
                            <input type="radio" name="popup_layout" value="side_drawer" onchange="selectLayoutChoice('side_drawer')">
                            <div class="layout-preview-box" style="justify-content:flex-end;">
                                <div style="width:28px; height:60px; background:#681418; border-radius:4px 0 0 4px;"></div>
                            </div>
                            <div class="layout-choice-title">Side Drawer</div>
                            <div class="layout-choice-desc">Modern slide-in panel from edge</div>
                        </label>

                        <label class="layout-choice-card" id="layoutCard_fullscreen">
                            <input type="radio" name="popup_layout" value="fullscreen" onchange="selectLayoutChoice('fullscreen')">
                            <div class="layout-preview-box">
                                <div style="width:68px; height:60px; background:#FAF6EE; border:1.5px solid #681418; border-radius:4px;"></div>
                            </div>
                            <div class="layout-choice-title">Fullscreen</div>
                            <div class="layout-choice-desc">Magazine-style editorial takeover</div>
                        </label>
                    </div>
                </div>

                <!-- ELEMENT VISIBILITY TOGGLES (OPTION TO REMOVE ANY ELEMENT) -->
                <div class="form-group">
                    <label class="form-label">2. Element Customization (Toggle or Remove Elements)</label>
                    <div class="element-toggles-grid">
                        <label class="toggle-label">
                            <input type="checkbox" name="el_badge" id="elBadgeCheck" checked>
                            <span>Eyebrow Badge</span>
                        </label>
                        <label class="toggle-label">
                            <input type="checkbox" name="el_title" id="elTitleCheck" checked>
                            <span>Main Title</span>
                        </label>
                        <label class="toggle-label">
                            <input type="checkbox" name="el_subtitle" id="elSubtitleCheck" checked>
                            <span>Subtitle Text</span>
                        </label>
                        <label class="toggle-label">
                            <input type="checkbox" name="el_image" id="elImageCheck" checked>
                            <span>Cover Image</span>
                        </label>
                        <label class="toggle-label">
                            <input type="checkbox" name="el_close" id="elCloseCheck" checked>
                            <span>Close Button</span>
                        </label>

                        <!-- Reservation-specific elements -->
                        <label class="toggle-label toggle-reservation-only">
                            <input type="checkbox" name="el_guests" id="elGuestsCheck" checked>
                            <span>Guests Selector</span>
                        </label>
                        <label class="toggle-label toggle-reservation-only">
                            <input type="checkbox" name="el_datetime" id="elDateTimeCheck" checked>
                            <span>Date & Time</span>
                        </label>
                        <label class="toggle-label toggle-reservation-only">
                            <input type="checkbox" name="el_notes" id="elNotesCheck" checked>
                            <span>Special Notes</span>
                        </label>

                        <!-- Offer-specific elements -->
                        <label class="toggle-label toggle-offer-only" style="display:none;">
                            <input type="checkbox" name="el_discount_badge" id="elDiscountBadgeCheck" checked>
                            <span>Discount Callout Pill</span>
                        </label>
                        <label class="toggle-label toggle-offer-only" style="display:none;">
                            <input type="checkbox" name="el_promo_code" id="elPromoCodeCheck" checked>
                            <span>Promo Code Ticket</span>
                        </label>
                        <label class="toggle-label toggle-offer-only" style="display:none;">
                            <input type="checkbox" name="el_expiry" id="elExpiryCheck" checked>
                            <span>Validity / Expiry</span>
                        </label>
                        <label class="toggle-label toggle-offer-only" style="display:none;">
                            <input type="checkbox" name="el_terms" id="elTermsCheck" checked>
                            <span>Terms & Conditions</span>
                        </label>
                    </div>
                </div>

                <!-- COPY & TEXT CONTENT -->
                <div class="form-group">
                    <label class="form-label">3. Headline & Messaging</label>
                    <div class="form-row-2">
                        <input type="text" name="badge_text" id="popBadgeTextInput" class="form-input" placeholder="Eyebrow Badge (e.g. EXCLUSIVE OFFER)" value="EXCLUSIVE OFFER">
                        <input type="text" name="title" id="popTitleInput" class="form-input" placeholder="Main Heading" value="Special Tasting Experience" required>
                    </div>
                    <textarea name="subtitle" id="popSubtitleInput" class="form-textarea" rows="2" style="margin-top:8px;" placeholder="Subtitle / invitation message...">Experience our slow-fermented hearth kitchen and architectural ambiance at Orah House.</textarea>
                    <div class="form-row-2" style="margin-top:8px;">
                        <input type="text" name="button_text" id="popBtnTextInput" class="form-input" placeholder="Action Button Text" value="Claim Offer On WhatsApp ↗">
                        <input type="text" name="floating_btn_text" id="popFloatingBtnTextInput" class="form-input" placeholder="Floating Button Text" value="🎁 Special Offer">
                    </div>
                </div>

                <!-- OFFER SPECIFIC FIELDS SECTION (Visible when Offer selected) -->
                <div id="offerConfigSection" style="display:none; background:#FAF7F0; border:1px solid rgba(201,154,104,0.4); border-radius:12px; padding:16px; margin-bottom:16px;">
                    <div style="font-weight:800; font-size:0.85rem; color:#681418; margin-bottom:10px; display:flex; align-items:center; gap:6px;">
                        <span>🎁 Special Offer & Voucher Settings</span>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label">Discount Badge / Tag</label>
                            <input type="text" name="discount_badge" id="popDiscountBadgeInput" class="form-input" placeholder="e.g. FLAT 15% OFF, BUY 1 GET 1" value="FLAT 15% OFF">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Coupon / Promo Code</label>
                            <input type="text" name="promo_code" id="popPromoCodeInput" class="form-input" placeholder="e.g. ORAH15, TASTING20" value="ORAH15">
                        </div>
                    </div>

                    <div class="form-row-2" style="margin-top:8px;">
                        <div class="form-group">
                            <label class="form-label">Offer Action (Call-to-Action)</label>
                            <select name="offer_cta_type" id="popOfferCtaTypeSelect" class="form-select">
                                <option value="whatsapp" selected>One-Click Claim on WhatsApp</option>
                                <option value="copy_code">Click to Copy Code & Open Menu</option>
                                <option value="lead_capture">Guest Enters Phone to Unlock Code</option>
                                <option value="link">Custom URL / Page Link</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Validity / Expiry Text</label>
                            <input type="text" name="offer_expiry" id="popOfferExpiryInput" class="form-input" placeholder="e.g. Valid this week &bull; Dine-in & Takeaway" value="Valid this week only &bull; Dine-in & Takeaway">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:8px;">
                        <label class="form-label">Target Link / WhatsApp URL (Optional)</label>
                        <input type="text" name="offer_cta_link" id="popOfferCtaLinkInput" class="form-input" placeholder="e.g. https://wa.me/919429693199?text=Hello%20Orah... or menu.php" value="https://wa.me/919429693199?text=Hello%20Orah%20House%2C%20I%20would%20like%20to%20redeem%20the%2015%25%20OFF%20offer%20(Code%3A%20ORAH15).">
                    </div>

                    <div class="form-group" style="margin-top:8px;">
                        <label class="form-label">Terms & Conditions / Fine Print</label>
                        <input type="text" name="terms" id="popTermsInput" class="form-input" placeholder="e.g. *Valid for dine-in. Present code during ordering." value="*Valid for dine-in & takeaway. Present code during ordering.">
                    </div>
                </div>

                <!-- IMAGERY & TRIGGERS -->
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Form Showcase Image (Upload or URL)</label>
                        <input type="file" name="image_file" class="form-input" accept="image/*" style="margin-bottom:8px;">
                        <input type="text" name="image_url" id="popImageUrlInput" class="form-input" placeholder="Or enter URL (e.g. uploads/flatbread.jpg)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Trigger Behavior & Delay</label>
                        <select name="trigger_type" id="popTriggerTypeSelect" class="form-select" style="margin-bottom:8px;">
                            <option value="both" selected>Both (Timed Delay + Floating Button)</option>
                            <option value="delay">Timed Delay Only</option>
                            <option value="button">Floating Button Only</option>
                        </select>
                        <input type="number" name="trigger_delay_sec" id="popDelayInput" class="form-input" placeholder="Delay in seconds" value="6">
                    </div>
                </div>

                <div class="checkbox-group">
                    <label class="custom-checkbox">
                        <input type="checkbox" name="popup_active" id="popActiveCheck" checked>
                        <span>Active on Selected Page</span>
                    </label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('popupModal')">Cancel</button>
                <button type="submit" class="btn-primary">Save Pop-Up Form</button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================================
     DASHBOARD CLIENT INTERACTION SCRIPT
     =========================================================================== -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Sidebar Workspace Switcher
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    const workspacePanels = document.querySelectorAll('.workspace-panel');
    const topbarTitle = document.getElementById('currentWorkspaceTitle');
    const topbarSub = document.getElementById('currentWorkspaceSub');
    const topbarActionBtn = document.getElementById('topbarActionBtn');

    const workspaceMeta = {
        'workspace-dashboard': { title: 'Executive Dashboard', sub: 'System Overview &bull; Orah House Nashik', btn: '+ Add Dish', action: () => openDishModal(false) },
        'workspace-dishes': { title: 'Menu Dishes Management', sub: 'Culinary catalog, pricing, taglines, and custom fields', btn: '+ Add Dish', action: () => openDishModal(false) },
        'workspace-categories': { title: 'Categories & Section Presentation', sub: 'Architectural section titles, eyebrows, and cursive quotes', btn: '+ Create Category', action: () => openCategoryModal(false) },
        'workspace-popups': { title: 'Pop-Up & Form Studio', sub: 'Page-wise lead capture forms with 4 customizable layouts', btn: '+ Create Form', action: () => openPopupModal(false) },
        'workspace-leads': { title: 'Leads & Table Reservations', sub: 'Guest inquiries and booking requests', btn: '+ New Dish', action: () => openDishModal(false) },
        'workspace-pages': { title: 'Live Page Controller', sub: 'Manage live and maintenance down states per page', btn: 'View Menu ↗', action: () => window.open('../public/menu.php', '_blank') }
    };

    window.switchWorkspace = function(workspaceId) {
        sidebarLinks.forEach(link => {
            if (link.getAttribute('data-workspace') === workspaceId) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        workspacePanels.forEach(panel => {
            if (panel.id === workspaceId) {
                panel.classList.add('active');
            } else {
                panel.classList.remove('active');
            }
        });

        const meta = workspaceMeta[workspaceId];
        if (meta) {
            topbarTitle.innerHTML = meta.title;
            topbarSub.innerHTML = meta.sub;
            topbarActionBtn.innerHTML = `<span>${meta.btn}</span>`;
            topbarActionBtn.onclick = meta.action;
        }

        // Close mobile sidebar if open
        document.getElementById('dashboardSidebar').classList.remove('mobile-open');
    };

    sidebarLinks.forEach(link => {
        link.addEventListener('click', () => {
            const ws = link.getAttribute('data-workspace');
            if (ws) switchWorkspace(ws);
        });
    });

    // Mobile Hamburger Toggle
    document.getElementById('sidebarToggleBtn')?.addEventListener('click', () => {
        document.getElementById('dashboardSidebar').classList.toggle('mobile-open');
    });

    // Modal Control Functions
    window.closeModal = function(modalId) {
        document.getElementById(modalId)?.classList.remove('open');
    };

    // Close on backdrop click
    document.querySelectorAll('.modal-backdrop').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.remove('open');
        });
    });

    // =========================================================================
    // 1. DISH MODAL & CUSTOM FIELDS BUILDER
    // =========================================================================
    const cfRowsContainer = document.getElementById('cfRowsContainer');
    document.getElementById('btnAddCfRow')?.addEventListener('click', () => {
        addCfRow('', '');
    });

    function addCfRow(name = '', val = '') {
        const row = document.createElement('div');
        row.style.cssText = 'display:grid; grid-template-columns: 1fr 1fr 34px; gap:8px; align-items:center;';
        row.innerHTML = `
            <input type="text" name="cf_name[]" placeholder="Field Name (e.g. Spice Level)" class="form-input" style="padding:7px 10px; font-size:0.82rem;" value="${escapeHtml(name)}">
            <input type="text" name="cf_value[]" placeholder="Value (e.g. Medium Spicy)" class="form-input" style="padding:7px 10px; font-size:0.82rem;" value="${escapeHtml(val)}">
            <button type="button" class="btn-secondary" style="height:32px; padding:0; display:flex; align-items:center; justify-content:center; color:#b91c1c; font-weight:700;" title="Remove field">&times;</button>
        `;
        row.querySelector('button').addEventListener('click', () => row.remove());
        cfRowsContainer.appendChild(row);
    }

    window.openDishModal = function(isEdit = false, data = null) {
        cfRowsContainer.innerHTML = '';
        const modal = document.getElementById('dishModal');
        const form = document.getElementById('dishForm');
        const titleEl = document.getElementById('dishModalTitle');

        if (isEdit && data) {
            titleEl.textContent = 'Edit Menu Item';
            document.getElementById('dishFormId').value = data.id || '';
            document.getElementById('dishNameInput').value = data.name || '';
            document.getElementById('dishBadgeInput').value = data.badge || '';
            document.getElementById('dishPriceInput').value = data.price || '';
            document.getElementById('dishTaglineInput').value = data.tagline || '';
            document.getElementById('dishDescInput').value = data.description || '';
            document.getElementById('dishUrlInput').value = data.image || '';
            document.getElementById('dishVegCheck').checked = !!data.is_veg;
            document.getElementById('dishFeaturedCheck').checked = !!data.featured;
            document.getElementById('dishAvailableCheck').checked = data.available !== false;

            // Chef Special & Paper clip
            document.getElementById('dishChefSpecialCheck').checked = !!data.is_chef_special;
            document.getElementById('dishChefSpecialNoteInput').value = data.chef_special_note || '';

            // Order sequence
            document.getElementById('dishOrderInput').value = data.order || 1;

            // Card Style & Animations
            const cs = data.card_style || {};
            document.getElementById('dishCardAnimSelect').value = cs.animation || 'none';
            document.getElementById('dishCardBgInput').value = cs.bg_color || '';
            if (cs.bg_color && /^#[0-9A-Fa-f]{6}$/.test(cs.bg_color)) {
                document.getElementById('dishCardBgPicker').value = cs.bg_color;
            } else {
                document.getElementById('dishCardBgPicker').value = '#FAF5EB';
            }
            document.getElementById('dishCardBorderInput').value = cs.border_color || '';
            if (cs.border_color && /^#[0-9A-Fa-f]{6}$/.test(cs.border_color)) {
                document.getElementById('dishCardBorderPicker').value = cs.border_color;
            } else {
                document.getElementById('dishCardBorderPicker').value = '#c99a68';
            }

            if (Array.isArray(data.custom_fields)) {
                data.custom_fields.forEach(cf => addCfRow(cf.name, cf.value));
            }
        } else {
            titleEl.textContent = 'Add New Dish';
            document.getElementById('dishFormId').value = '';
            form.reset();
            document.getElementById('dishVegCheck').checked = true;
            document.getElementById('dishAvailableCheck').checked = true;
            document.getElementById('dishChefSpecialCheck').checked = false;
            document.getElementById('dishChefSpecialNoteInput').value = '';
            document.getElementById('dishOrderInput').value = 1;
            document.getElementById('dishCardAnimSelect').value = 'none';
            document.getElementById('dishCardBgInput').value = '';
            document.getElementById('dishCardBorderInput').value = '';
        }
        modal.classList.add('open');
    };

    window.openDishModalForCategory = function(catName) {
        openDishModal(false);
        const badgeInput = document.getElementById('dishBadgeInput');
        if (badgeInput) badgeInput.value = catName;
        const catWrap = document.querySelector(`.category-block-wrap[data-category-name="${catName.toLowerCase()}"]`);
        if (catWrap) {
            const rows = catWrap.querySelectorAll('.dish-row');
            document.getElementById('dishOrderInput').value = rows.length + 1;
        }
    };

    window.toggleCategoryWrap = function(wrapId) {
        const wrap = document.getElementById(wrapId);
        if (!wrap) return;
        wrap.classList.toggle('collapsed');
    };

    document.querySelectorAll('.edit-dish-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('.dish-row');
            try {
                const data = JSON.parse(row.getAttribute('data-raw'));
                openDishModal(true, data);
            } catch (e) {}
        });
    });

    // =========================================================================
    // 2. CATEGORY MODAL
    // =========================================================================
    window.openCategoryModal = function(isEdit = false, data = null) {
        const modal = document.getElementById('categoryModal');
        const form = document.getElementById('categoryForm');
        const titleEl = document.getElementById('catModalTitle');

        if (isEdit && data) {
            titleEl.textContent = 'Edit Category & Section Headers';
            document.getElementById('catFormId').value = data.id || '';
            document.getElementById('catFormOldName').value = data.name || '';
            document.getElementById('catNameInput').value = data.name || '';
            document.getElementById('catOrderInput').value = data.order ?? 99;
            document.getElementById('catEyebrowInput').value = data.eyebrow || 'OUR SIGNATURES';
            document.getElementById('catScriptInput').value = data.scriptQuote || '';
            document.getElementById('catSubtitleInput').value = data.subtitle || '';
            document.getElementById('catUrlInput').value = data.image || '';
            document.getElementById('catActiveCheck').checked = data.active !== false;

            document.getElementById('catAnimSelect').value = data.animation || 'none';
            document.getElementById('catCardBgInput').value = data.card_bg || '';
            document.getElementById('catBorderInput').value = data.border_color || '';
        } else {
            titleEl.textContent = 'Create New Category';
            document.getElementById('catFormId').value = '';
            document.getElementById('catFormOldName').value = '';
            form.reset();
            document.getElementById('catOrderInput').value = (document.querySelectorAll('.category-admin-card').length + 1);
            document.getElementById('catEyebrowInput').value = 'OUR SIGNATURES';
            document.getElementById('catActiveCheck').checked = true;

            document.getElementById('catAnimSelect').value = 'none';
            document.getElementById('catCardBgInput').value = '';
            document.getElementById('catBorderInput').value = '';
        }
        modal.classList.add('open');
    };

    document.querySelectorAll('.edit-category-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const card = btn.closest('.category-admin-card');
            try {
                const data = JSON.parse(card.getAttribute('data-raw'));
                openCategoryModal(true, data);
            } catch (e) {}
        });
    });

    // =========================================================================
    // 3. POP-UP CREATOR & LAYOUT CUSTOMIZER
    // =========================================================================
    window.selectLayoutChoice = function(layoutName) {
        document.querySelectorAll('.layout-choice-card').forEach(card => card.classList.remove('selected'));
        const activeCard = document.getElementById('layoutCard_' + layoutName);
        if (activeCard) {
            activeCard.classList.add('selected');
            const radio = activeCard.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        }
    };

    window.switchPopupType = function(type) {
        const isOffer = (type === 'offer');

        const cardRes = document.getElementById('typeCard_reservation');
        const cardOff = document.getElementById('typeCard_offer');
        const radioRes = document.getElementById('typeRadio_reservation');
        const radioOff = document.getElementById('typeRadio_offer');

        if (isOffer) {
            if (cardRes) { cardRes.style.borderColor = 'var(--border-color)'; cardRes.classList.remove('selected'); }
            if (cardOff) { cardOff.style.borderColor = '#681418'; cardOff.classList.add('selected'); }
            if (radioOff) radioOff.checked = true;
        } else {
            if (cardRes) { cardRes.style.borderColor = '#681418'; cardRes.classList.add('selected'); }
            if (cardOff) { cardOff.style.borderColor = 'var(--border-color)'; cardOff.classList.remove('selected'); }
            if (radioRes) radioRes.checked = true;
        }

        const offerSection = document.getElementById('offerConfigSection');
        if (offerSection) {
            offerSection.style.display = isOffer ? 'block' : 'none';
        }

        document.querySelectorAll('.toggle-reservation-only').forEach(el => {
            el.style.display = isOffer ? 'none' : 'flex';
        });
        document.querySelectorAll('.toggle-offer-only').forEach(el => {
            el.style.display = isOffer ? 'flex' : 'none';
        });

        const formId = document.getElementById('popFormId')?.value;
        if (!formId) {
            if (isOffer) {
                document.getElementById('popNameInput').placeholder = 'e.g. Welcome Tasting 15% OFF';
                document.getElementById('popFloatingBtnTextInput').value = '🎁 15% OFF Special Offer';
                document.getElementById('popBadgeTextInput').value = 'EXCLUSIVE OFFER';
                document.getElementById('popTitleInput').value = 'Savor 15% Off Your First Visit';
                document.getElementById('popSubtitleInput').value = 'Enjoy hand-stretched sourdough flatbreads and specialty coffees with a welcome discount.';
                document.getElementById('popBtnTextInput').value = 'Claim Offer on WhatsApp ↗';
            } else {
                document.getElementById('popNameInput').placeholder = 'e.g. Table Reservation & VIP Tasting';
                document.getElementById('popFloatingBtnTextInput').value = '✦ Reserve Table';
                document.getElementById('popBadgeTextInput').value = 'ORAH HOUSE • EXCLUSIVE TABLE';
                document.getElementById('popTitleInput').value = 'Reserve Your Dining Experience';
                document.getElementById('popSubtitleInput').value = 'Immerse yourself in our architectural hearth dining, artisanal sourdough flatbreads, and specialty brews.';
                document.getElementById('popBtnTextInput').value = 'Confirm Reservation Request';
            }
        }
    };

    window.openPopupModal = function(isEdit = false, data = null) {
        const modal = document.getElementById('popupModal');
        const form = document.getElementById('popupForm');
        const titleEl = document.getElementById('popModalTitle');

        if (isEdit && data) {
            const pType = data.type || 'reservation';
            titleEl.textContent = isEdit ? (pType === 'offer' ? 'Edit Special Offer Pop-Up' : 'Edit Table Reservation Pop-Up') : 'Create Pop-Up Campaign';
            document.getElementById('popFormId').value = data.id || '';
            document.getElementById('popNameInput').value = data.name || '';
            document.getElementById('popTargetPageSelect').value = data.target_page || 'menu.php';
            switchPopupType(pType);
            selectLayoutChoice(data.layout || 'center_modal');

            document.getElementById('popTriggerTypeSelect').value = data.trigger_type || 'both';
            document.getElementById('popDelayInput').value = data.trigger_delay_sec ?? 6;
            document.getElementById('popFloatingBtnTextInput').value = data.floating_btn_text || (pType === 'offer' ? '🎁 Special Offer' : '✦ Reserve Table');
            document.getElementById('popBadgeTextInput').value = data.badge_text || (pType === 'offer' ? 'EXCLUSIVE OFFER' : 'EXCLUSIVE DINING');
            document.getElementById('popTitleInput').value = data.title || '';
            document.getElementById('popSubtitleInput').value = data.subtitle || '';
            document.getElementById('popBtnTextInput').value = data.button_text || (pType === 'offer' ? 'Claim Offer on WhatsApp ↗' : 'Confirm Reservation Request');
            document.getElementById('popImageUrlInput').value = data.image || '';
            document.getElementById('popActiveCheck').checked = data.active !== false;

            // Offer-specific values
            document.getElementById('popDiscountBadgeInput').value = data.discount_badge || 'FLAT 15% OFF';
            document.getElementById('popPromoCodeInput').value = data.promo_code || 'ORAH15';
            document.getElementById('popOfferExpiryInput').value = data.offer_expiry || 'Valid this week only';
            document.getElementById('popOfferCtaTypeSelect').value = data.offer_cta_type || 'whatsapp';
            document.getElementById('popOfferCtaLinkInput').value = data.offer_cta_link || '';
            document.getElementById('popTermsInput').value = data.terms || '';

            const el = data.elements || {};
            document.getElementById('elBadgeCheck').checked = el.show_badge !== false;
            document.getElementById('elTitleCheck').checked = el.show_title !== false;
            document.getElementById('elSubtitleCheck').checked = el.show_subtitle !== false;
            document.getElementById('elImageCheck').checked = el.show_image !== false;
            document.getElementById('elCloseCheck').checked = el.show_close !== false;

            document.getElementById('elGuestsCheck').checked = el.show_guests !== false;
            document.getElementById('elDateTimeCheck').checked = el.show_datetime !== false;
            document.getElementById('elNotesCheck').checked = el.show_notes !== false;

            document.getElementById('elDiscountBadgeCheck').checked = el.show_discount_badge !== false;
            document.getElementById('elPromoCodeCheck').checked = el.show_promo_code !== false;
            document.getElementById('elExpiryCheck').checked = el.show_expiry !== false;
            document.getElementById('elTermsCheck').checked = el.show_terms !== false;
        } else {
            titleEl.textContent = 'Create Pop-Up Campaign';
            document.getElementById('popFormId').value = '';
            form.reset();
            switchPopupType('reservation');
            selectLayoutChoice('center_modal');
            document.getElementById('popActiveCheck').checked = true;
            document.querySelectorAll('.element-toggles-grid input[type="checkbox"]').forEach(cb => cb.checked = true);
        }
        modal.classList.add('open');
    };

    document.querySelectorAll('.edit-popup-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('tr');
            try {
                const data = JSON.parse(row.getAttribute('data-raw'));
                openPopupModal(true, data);
            } catch (e) {}
        });
    });

    // =========================================================================
    // SEARCH & FILTER (DISHES IN CATEGORY WRAPPERS)
    // =========================================================================
    const searchInput = document.getElementById('dishSearchInput');
    const categoryPills = document.querySelectorAll('#dishFilterPillContainer .pill-btn');

    let activeCategory = 'ALL';
    let searchQuery = '';

    function filterDishes() {
        const wraps = document.querySelectorAll('.category-block-wrap');
        wraps.forEach(wrap => {
            const wrapCat = (wrap.getAttribute('data-category-name') || '').toLowerCase();
            const matchCategory = (activeCategory === 'ALL') || (wrapCat === activeCategory.toLowerCase());

            let visibleInWrap = 0;
            const rows = wrap.querySelectorAll('.dish-row');
            rows.forEach(row => {
                const rName = (row.getAttribute('data-name') || '').toLowerCase();
                const rDesc = (row.getAttribute('data-desc') || '').toLowerCase();
                const rPrice = (row.getAttribute('data-price') || '').toLowerCase();
                const rCat = (row.getAttribute('data-category') || '').toLowerCase();

                const textMatch = !searchQuery || 
                    rName.includes(searchQuery) || 
                    rDesc.includes(searchQuery) || 
                    rPrice.includes(searchQuery) || 
                    rCat.includes(searchQuery);

                if (textMatch && matchCategory) {
                    row.style.display = '';
                    visibleInWrap++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (matchCategory && (visibleInWrap > 0 || !searchQuery)) {
                wrap.style.display = '';
                if (searchQuery && visibleInWrap > 0) {
                    wrap.classList.remove('collapsed'); // Auto-expand when searching
                }
            } else {
                wrap.style.display = 'none';
            }
        });
    }

    searchInput?.addEventListener('input', (e) => {
        searchQuery = e.target.value.toLowerCase().trim();
        filterDishes();
    });

    categoryPills.forEach(pill => {
        pill.addEventListener('click', () => {
            categoryPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            activeCategory = pill.getAttribute('data-category');
            filterDishes();
        });
    });

    // =========================================================================
    // 4. PAGE CONTROLLER INSTANT TOGGLES & REAL-TIME FEEDBACK
    // =========================================================================
    window.showToastNotification = function(text, type = 'success') {
        let toast = document.getElementById('adminToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'adminToast';
            toast.style.cssText = 'position:fixed; bottom:28px; right:28px; z-index:99999; padding:12px 22px; border-radius:12px; font-weight:700; font-size:0.88rem; box-shadow:0 12px 30px rgba(0,0,0,0.25); display:flex; align-items:center; gap:10px; transition:all 0.3s cubic-bezier(0.16, 1, 0.3, 1); transform:translateY(100px); opacity:0; pointer-events:none; font-family:var(--font-sans);';
            document.body.appendChild(toast);
        }

        if (type === 'error') {
            toast.style.background = '#991b1b';
            toast.style.color = '#ffffff';
        } else if (type === 'warning') {
            toast.style.background = '#9a3412';
            toast.style.color = '#ffffff';
        } else {
            toast.style.background = '#15803d';
            toast.style.color = '#ffffff';
        }

        toast.textContent = text;
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';

        clearTimeout(window._toastTimer);
        window._toastTimer = setTimeout(() => {
            toast.style.transform = 'translateY(100px)';
            toast.style.opacity = '0';
        }, 4000);
    };

    window.applyPageStatusVisuals = function(pageName, status) {
        const slug = pageName.replace('.', '_');
        const badge = document.getElementById('statusBadge_' + slug);
        const card = document.getElementById('pageCard_' + slug);
        const checkbox = document.getElementById('pageToggle_' + slug);
        const isLive = (status === 'live');

        if (checkbox) {
            checkbox.checked = isLive;
        }

        if (badge) {
            badge.textContent = isLive ? '● LIVE' : '■ DOWN';
            badge.style.background = isLive ? 'rgba(34,197,94,0.12)' : 'rgba(239,68,68,0.15)';
            badge.style.color = isLive ? '#15803d' : '#b91c1c';
        }

        if (card) {
            card.classList.toggle('is-live', isLive);
            card.classList.toggle('is-down', !isLive);

            const toggleBtn = card.querySelector('.btn-quick-toggle');
            if (toggleBtn) {
                const pageLabel = (pageName === 'menu.php') ? 'Menu' : 'Home';
                if (isLive) {
                    toggleBtn.className = 'btn-quick-toggle btn-set-down';
                    toggleBtn.textContent = '⚡ Put ' + pageLabel + ' DOWN (Maintenance)';
                    toggleBtn.setAttribute('onclick', `quickTogglePage('${pageName}', 'down')`);
                } else {
                    toggleBtn.className = 'btn-quick-toggle btn-set-live';
                    toggleBtn.textContent = '✓ Put ' + pageLabel + ' LIVE';
                    toggleBtn.setAttribute('onclick', `quickTogglePage('${pageName}', 'live')`);
                }
            }
        }
    };

    window.quickTogglePage = function(pageName, targetStatus) {
        const slug = pageName.replace('.', '_');
        const checkbox = document.getElementById('pageToggle_' + slug);
        
        let newStatus = targetStatus;
        if (!newStatus) {
            newStatus = (checkbox && checkbox.checked) ? 'live' : 'down';
        }

        // Apply visual updates immediately
        applyPageStatusVisuals(pageName, newStatus);

        // Send AJAX request
        const formData = new FormData();
        formData.append('action', 'page_toggle_quick');
        formData.append('page', pageName);
        formData.append('status', newStatus);
        formData.append('ajax', '1');

        fetch('index.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToastNotification(data.message, newStatus === 'live' ? 'success' : 'warning');
            } else {
                showToastNotification('Failed to update page status.', 'error');
            }
        })
        .catch(err => {
            showToastNotification(pageName + ' is now ' + newStatus.toUpperCase(), newStatus === 'live' ? 'success' : 'warning');
        });
    };

    document.querySelectorAll('.instant-page-switch').forEach(sw => {
        sw.addEventListener('change', (e) => {
            const page = sw.getAttribute('data-page');
            const status = sw.checked ? 'live' : 'down';
            quickTogglePage(page, status);
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
});
</script>

</body>
</html>
