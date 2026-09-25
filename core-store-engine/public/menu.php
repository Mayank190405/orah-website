<?php
require_once __DIR__ . '/../engine/JsonStorage.php';
require_once __DIR__ . '/../engine/ThemeEngine.php';

use CoreStore\Engine\JsonStorage;
use CoreStore\Engine\ThemeEngine;

// Page Controller Gate Check (Live or Down)
$pagesStorage = new JsonStorage(__DIR__ . '/../data/pages.json');
$pagesConfig = $pagesStorage->read() ?: [];
if (isset($pagesConfig['menu.php']) && ($pagesConfig['menu.php']['status'] ?? 'live') === 'down') {
    if (($pagesConfig['menu.php']['down_action'] ?? '') === 'redirect' && !empty($pagesConfig['menu.php']['redirect_to'])) {
        header('Location: ' . $pagesConfig['menu.php']['redirect_to']);
        exit;
    }
    require_once __DIR__ . '/maintenance.php';
    exit;
}

// Read settings & catalog
$settingsStorage = new JsonStorage(__DIR__ . '/../data/settings.json');
$settings = $settingsStorage->read();
$themeEngine = new ThemeEngine($settings);

$catalogStorage = new JsonStorage(__DIR__ . '/../data/catalog.json');
$catalog = $catalogStorage->read();

$brand = $settings['brand'] ?? ['text' => 'Orah House'];

// Load Categories from data/categories.json (Editable via Admin Panel)
$catStorage = new JsonStorage(__DIR__ . '/../data/categories.json');
$categoriesList = $catStorage->read() ?: [];

// Sort categories by custom sequence order
usort($categoriesList, function($a, $b) {
    return ($a['order'] ?? 99) <=> ($b['order'] ?? 99);
});

// Build section metadata map from categories.json
$sectionMeta = [];
foreach ($categoriesList as $cat) {
    if (!($cat['active'] ?? true)) continue;
    $sectionMeta[$cat['name']] = [
        'eyebrow' => $cat['eyebrow'] ?? 'OUR SIGNATURES',
        'scriptQuote' => $cat['scriptQuote'] ?? ('More than just ' . $cat['name']),
        'subtitle' => $cat['subtitle'] ?? '',
        'image' => $cat['image'] ?? 'assets/placeholder.jpg'
    ];
}

// Helper to provide ingredient highlights with icons matching reference mockup
function getDishHighlights($dish) {
    $text = strtolower(($dish['name'] ?? '') . ' ' . ($dish['particular'] ?? '') . ' ' . ($dish['description'] ?? ''));

    if (strpos($text, 'mozzarella') !== false || strpos($text, 'golden melt') !== false) {
        return [
            ['icon' => '🧀', 'name' => 'Mozzarella'],
            ['icon' => '🫑', 'name' => 'Roasted Peppers'],
            ['icon' => '🫒', 'name' => 'Olives'],
            ['icon' => '🌿', 'name' => 'Fresh Herbs']
        ];
    }
    if (strpos($text, 'mushroom') !== false || strpos($text, 'truffle') !== false) {
        return [
            ['icon' => '🍄', 'name' => 'Wild Mushrooms'],
            ['icon' => '🫒', 'name' => 'Truffle Oil'],
            ['icon' => '🧀', 'name' => 'Mozzarella'],
            ['icon' => '🌿', 'name' => 'Fresh Herbs']
        ];
    }
    if (strpos($text, 'paneer') !== false || strpos($text, 'tikka') !== false || strpos($text, 'bhurji') !== false) {
        return [
            ['icon' => '🧀', 'name' => 'Artisan Paneer'],
            ['icon' => '🧅', 'name' => 'Roasted Onions'],
            ['icon' => '🌶️', 'name' => 'Tandoor Spices'],
            ['icon' => '🌿', 'name' => 'Fresh Herbs']
        ];
    }
    if (strpos($text, 'pizza') !== false || strpos($text, 'farmhouse') !== false) {
        return [
            ['icon' => '🍅', 'name' => 'San Marzano Sauce'],
            ['icon' => '🧀', 'name' => 'Fior Di Latte'],
            ['icon' => '🫑', 'name' => 'Sweet Peppers'],
            ['icon' => '🌿', 'name' => 'Fresh Basil']
        ];
    }
    if (strpos($text, 'pasta') !== false || strpos($text, 'spaghetti') !== false || strpos($text, 'mac') !== false) {
        return [
            ['icon' => '🍝', 'name' => 'Durum Wheat Pasta'],
            ['icon' => '🧀', 'name' => 'Parmigiano Reggiano'],
            ['icon' => '🧄', 'name' => 'Roasted Garlic'],
            ['icon' => '🌿', 'name' => 'Estate Herbs']
        ];
    }
    if (strpos($text, 'salad') !== false || strpos($text, 'harvest') !== false || strpos($text, 'greens') !== false) {
        return [
            ['icon' => '🥑', 'name' => 'Avocado Salsa'],
            ['icon' => '🥬', 'name' => 'Garden Greens'],
            ['icon' => '🌽', 'name' => 'Crisp Corn'],
            ['icon' => '🍋', 'name' => 'House Dressing']
        ];
    }
    if (strpos($text, 'dessert') !== false || strpos($text, 'chocolate') !== false) {
        return [
            ['icon' => '🍫', 'name' => 'Belgian Cacao'],
            ['icon' => '🍦', 'name' => 'Madagascar Vanilla'],
            ['icon' => '🍓', 'name' => 'Berry Coulis'],
            ['icon' => '🍯', 'name' => 'Caramel Crunch']
        ];
    }
    if (strpos($text, 'coffee') !== false || strpos($text, 'latte') !== false || strpos($text, 'brew') !== false) {
        return [
            ['icon' => '☕', 'name' => 'Single Origin Roast'],
            ['icon' => '🥛', 'name' => 'Textured Microfoam'],
            ['icon' => '✨', 'name' => 'Artisan Pour'],
            ['icon' => '🌿', 'name' => 'Estate Notes']
        ];
    }

    return [
        ['icon' => '🌿', 'name' => 'Artisanal Base'],
        ['icon' => '✨', 'name' => 'Chef Craft'],
        ['icon' => '🔥', 'name' => 'Slow Baked'],
        ['icon' => '🌱', 'name' => 'Fresh Herbs']
    ];
}

// Fallback helper for evocative handwritten script taglines
function getDishTagline($index, $dish) {
    if (!empty($dish['tagline'])) {
        return $dish['tagline'];
    }
    $pool = [
        'Crispy edges, Endless flavour',
        'Earthy Indulgence',
        'Sun-blessed Mediterranean Craft',
        'Golden Melts & Hearth Crust',
        'Slow Fermented Bliss',
        'Rich & Comforting',
        'Velvet Texture, Pure Harmony',
        'Smoky Oven Warmth'
    ];
    return $pool[$index % count($pool)];
}

// Group catalog items by section badge
$sections = [];
foreach ($catalog as $dish) {
    if (!($dish['available'] ?? true)) continue;
    $badge = $dish['badge'] ?? 'General';
    if (!isset($sections[$badge])) {
        $sections[$badge] = [];
    }
    $sections[$badge][] = $dish;
}

// Order sections dynamically according to categories.json sequence
$orderedSections = [];
foreach ($categoriesList as $cat) {
    if (!($cat['active'] ?? true)) continue;
    $catName = $cat['name'];
    if (isset($sections[$catName])) {
        $orderedSections[$catName] = $sections[$catName];
    }
}
// Add any remaining categories present in dishes
foreach ($sections as $k => $v) {
    if (!isset($orderedSections[$k])) {
        $orderedSections[$k] = $v;
    }
}
$sectionKeys = array_keys($orderedSections);

// Canonical slug helper for menu routing
function getCanonicalCategorySlug($catName) {
    $cleaned = strtolower(trim($catName));
    if ($cleaned === 'pizzas' || $cleaned === 'pizza') return 'pizza';
    if ($cleaned === 'flat breads' || $cleaned === 'flatbreads') return 'flat-breads';
    if ($cleaned === 'pasta' || $cleaned === 'pastas') return 'pasta';
    if ($cleaned === 'desserts' || $cleaned === 'dessert') return 'desserts';
    if ($cleaned === 'appetizers' || $cleaned === 'appetizer') return 'appetizers';
    if ($cleaned === 'beverages' || $cleaned === 'beverage') return 'beverages';
    return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $catName), '-'));
}

// Compute base URL for relative assets and routing
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/menu.php';
$baseDir = rtrim(dirname($scriptName), '/\\');
$baseUrl = ($baseDir === '' || $baseDir === '.') ? '/' : $baseDir . '/';

// Parse requested category slug from PATH_INFO or REQUEST_URI
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$pathInfo = $_SERVER['PATH_INFO'] ?? '';
if (empty($pathInfo) && strpos($requestUri, 'menu.php/') !== false) {
    $parts = explode('menu.php/', $requestUri, 2);
    $pathInfo = '/' . strtok($parts[1] ?? '', '?#');
}

$urlSlug = strtolower(trim($pathInfo, '/'));

$initialSectionKey = null;
if (!empty($urlSlug)) {
    foreach ($sectionKeys as $sKey) {
        $canonical = getCanonicalCategorySlug($sKey);
        $rawSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $sKey), '-'));
        if ($urlSlug === $canonical || 
            $urlSlug === $rawSlug || 
            rtrim($urlSlug, 's') === rtrim($canonical, 's') ||
            rtrim($urlSlug, 's') === rtrim($rawSlug, 's') ||
            str_replace('-', '', $urlSlug) === str_replace('-', '', $canonical)) {
            $initialSectionKey = $sKey;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= htmlspecialchars($baseUrl) ?>">
    <title>Curated Menu &bull; Orah House Nashik</title>
    <meta name="description" content="Explore the authentic architectural menu of Orah House Nashik. Hand-stretched sourdough flatbreads, Neapolitan pizzas, specialty coffees, and artisan pastas.">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400;1,600&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/engine.css">
    
    <style>
        <?= $themeEngine->renderCssCustomProperties() ?>

        :root {
            --cream-bg: #FBF6EE;
            --burgundy: #681418;
            --burgundy-dark: #500f12;
            --gold-accent: #c99a68;
            --card-surface: #ffffff;
            --border-light: rgba(104, 20, 24, 0.12);
        }

        html {
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
        }

        body {
            background-color: var(--cream-bg);
            color: var(--burgundy);
            font-family: var(--font-body);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
            -webkit-tap-highlight-color: transparent;
        }

        /* Minimal Editorial Top Masthead (Replaces bulky sticky header & old text area) */
        .menu-top-masthead {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 20px 24px;
            box-sizing: border-box;
        }

        @media (max-width: 768px) {
            .menu-top-masthead {
                padding: 16px 14px 18px;
            }
        }

        .masthead-utility-row {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 48px;
            margin-bottom: 22px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(104, 20, 24, 0.08);
        }

        @media (max-width: 768px) {
            .masthead-utility-row {
                min-height: 44px;
                margin-bottom: 16px;
                padding-bottom: 10px;
            }
        }

        .masthead-back-link {
            position: relative;
            z-index: 5;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--burgundy);
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 6px 14px;
            background: rgba(104, 20, 24, 0.05);
            border-radius: 20px;
            transition: all 0.2s ease;
        }

        .masthead-back-link:hover {
            background: var(--burgundy);
            color: #FBF6EE;
            transform: translateX(-2px);
        }

        .masthead-right-actions {
            position: relative;
            z-index: 5;
        }

        .masthead-brand-crest {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--burgundy);
            z-index: 2;
        }

        .masthead-swans-img {
            height: 48px;
            width: auto;
            object-fit: contain;
            display: block;
            transition: transform 0.2s ease;
        }

        .masthead-brand-crest:hover .masthead-swans-img {
            transform: scale(1.05);
        }

        @media (max-width: 600px) {
            .masthead-swans-img {
                height: 40px;
            }
        }

        .utility-spacer {
            width: 72px;
        }

        @media (max-width: 520px) {
            .utility-spacer {
                width: 40px;
            }
        }

        /* Editorial Header Center */
        .masthead-center-content {
            text-align: center;
            max-width: 680px;
            margin: 0 auto;
        }

        .masthead-title {
            font-family: 'Dream Avenue', 'Cormorant Garamond', Georgia, serif;
            font-size: clamp(2.6rem, 5.8vw, 4rem);
            color: var(--burgundy);
            line-height: 1.05;
            font-weight: 400;
            margin: 0 0 12px;
            letter-spacing: 0.5px;
        }

        .masthead-tagline {
            font-size: clamp(0.85rem, 1.4vw, 0.96rem);
            color: #5d564e;
            line-height: 1.5;
            margin: 0 auto;
        }

        /* Main View Container */
        .views-wrapper {
            position: relative;
            min-height: 100vh;
            width: 100%;
        }

        /* ==========================================================================
           PAGE 1: SECTIONS SHOWCASE (Overview Landing)
           ========================================================================== */
        #sectionsOverviewView {
            display: block;
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        /* Sections Grid */
        /* Sections Grid - 2 Columns Matching Editorial Showcase Reference */
        .sections-grid-container {
            max-width: 1200px;
            margin: 0 auto 70px;
            padding: 0 20px;
        }

        .sections-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        @media (min-width: 1100px) {
            .sections-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 28px;
            }
        }

        @media (max-width: 768px) {
            .sections-grid-container {
                padding: 0 12px;
                margin-bottom: 50px;
            }

            .sections-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
        }

        @media (max-width: 360px) {
            .sections-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }

        /* Editorial Section Card with Full-Bleed Imagery & Dark Vignette */
        .section-card {
            position: relative;
            min-height: 480px;
            height: 100%;
            border-radius: 26px;
            overflow: hidden;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 24px;
            box-sizing: border-box;
            background-color: #1a0a0c;
            box-shadow: 0 14px 38px rgba(28, 12, 10, 0.20);
            transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.4s ease;
        }

        @media (max-width: 768px) {
            .section-card {
                min-height: 390px;
                padding: 16px 14px;
                border-radius: 20px;
            }
        }

        .section-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 22px 50px rgba(28, 12, 10, 0.32);
        }

        /* Full Bleed Background Image */
        .section-card-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
        }

        .section-card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.7s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .section-card:hover .section-card-img {
            transform: scale(1.06);
        }

        /* Deep Warm Dark Gradient Overlay matching reference image */
        .section-card-overlay {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                180deg,
                rgba(20, 10, 8, 0.12) 0%,
                rgba(24, 11, 10, 0.30) 32%,
                rgba(22, 9, 8, 0.82) 64%,
                rgba(16, 6, 7, 0.98) 100%
            );
            pointer-events: none;
        }

        /* Top-Left Maroon Pill Badge */
        .section-card-badge {
            position: relative;
            z-index: 5;
            align-self: flex-start;
        }

        .section-card-badge span {
            display: inline-block;
            background: rgba(82, 15, 18, 0.92);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            color: #ffffff;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        @media (max-width: 768px) {
            .section-card-badge span {
                font-size: 0.58rem;
                padding: 4px 10px;
                letter-spacing: 1px;
            }
        }

        /* Bottom Body Content */
        .section-card-body {
            position: relative;
            z-index: 5;
            margin-top: auto;
            display: flex;
            flex-direction: column;
        }

        .section-card-title {
            font-family: 'Dream Avenue', 'Cormorant Garamond', Georgia, serif;
            font-size: clamp(1.8rem, 2.8vw, 2.5rem);
            font-weight: 400;
            color: #ffffff;
            line-height: 1.05;
            margin: 0;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.35);
        }

        @media (max-width: 768px) {
            .section-card-title {
                font-size: 1.45rem;
            }
        }

        .section-title-line {
            width: 36px;
            height: 1.5px;
            background: rgba(255, 255, 255, 0.5);
            margin: 8px 0 12px;
        }

        @media (max-width: 768px) {
            .section-title-line {
                width: 28px;
                margin: 6px 0 10px;
            }
        }

        .section-card-desc {
            font-family: var(--font-body);
            font-size: clamp(0.78rem, 1.1vw, 0.88rem);
            color: rgba(255, 255, 255, 0.82);
            line-height: 1.48;
            margin: 0 0 18px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.4);
        }

        @media (max-width: 768px) {
            .section-card-desc {
                font-size: 0.72rem;
                line-height: 1.35;
                margin-bottom: 12px;
                -webkit-line-clamp: 2;
            }
        }

        /* Explore Row with Text & Circular Outline Arrow */
        .section-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }

        .explore-label {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #ffffff;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        @media (max-width: 768px) {
            .explore-label {
                font-size: 0.62rem;
                letter-spacing: 1.5px;
            }
        }

        .explore-circle-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1.5px solid rgba(255, 255, 255, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .explore-circle-btn {
                width: 30px;
                height: 30px;
            }
            .explore-circle-btn svg {
                width: 13px;
                height: 13px;
            }
        }

        .section-card:hover .explore-circle-btn {
            background: #ffffff;
            border-color: #ffffff;
            color: var(--burgundy);
            transform: scale(1.1);
        }

        .section-card:hover .explore-label {
            color: #ffffff;
            transform: translateX(2px);
        }

        /* ==========================================================================
           PAGE 2: DETAILED MENU WITH TOUCH SWIPING
           ========================================================================== */
        #detailMenuView {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
        }

        /* Detail Sticky Subheader */
        .detail-nav-bar {
            position: sticky;
            top: 0;
            background: rgba(251, 246, 238, 0.96);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border-light);
            z-index: 150;
            padding: 8px 0;
        }

        .detail-nav-container {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-height: 48px;
        }

        .back-to-sections-btn {
            position: relative;
            z-index: 5;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ffffff;
            border: 1px solid var(--border-light);
            padding: 6px 14px;
            border-radius: 20px;
            color: var(--burgundy);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .back-to-sections-btn:hover {
            background: var(--burgundy);
            color: #FBF6EE;
            border-color: var(--burgundy);
        }

        .detail-brand-crest {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }

        .detail-brand-crest img {
            height: 48px;
            width: auto;
            object-fit: contain;
            display: block;
            transition: transform 0.2s ease;
        }

        .detail-brand-crest:hover img {
            transform: scale(1.05);
        }

        .detail-nav-right-actions {
            position: relative;
            z-index: 5;
        }

        @media (max-width: 600px) {
            .detail-nav-container {
                min-height: 44px;
            }
            .detail-brand-crest img {
                height: 40px;
            }
        }

        /* Carousel Deck for Swiping */
        .swipe-deck-viewport {
            position: relative;
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
            padding: 0;
            overflow: hidden;
            touch-action: pan-y;
            box-sizing: border-box;
            transition: height 0.42s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .swipe-deck-slider {
            display: flex;
            align-items: flex-start;
            transition: transform 0.42s cubic-bezier(0.22, 1, 0.36, 1);
            width: 100%;
            margin: 0;
            padding: 0;
            will-change: transform;
            transform: translate3d(0, 0, 0);
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
        }

        .section-slide-pane {
            flex: 0 0 100%;
            width: 100%;
            min-width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            padding: 14px 16px 210px;
            overflow: hidden;
            opacity: 1;
            transform: translate3d(0, 0, 0);
            pointer-events: none;
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
        }

        .section-slide-pane.active {
            pointer-events: auto;
        }

        /* Editorial Section Header matching reference mockup */
        .editorial-section-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 20px;
            padding: 4px 0 2px;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }

        .editorial-header-left {
            flex: 1;
            min-width: 0;
        }

        .editorial-eyebrow {
            font-size: 0.70rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: #a08264;
            text-transform: uppercase;
            display: block;
            margin-bottom: 3px;
        }

        .editorial-title {
            font-family: 'Dream Avenue', 'Cormorant Garamond', Georgia, serif;
            font-size: clamp(2.2rem, 5.5vw, 3.2rem);
            color: var(--burgundy);
            line-height: 1.04;
            font-weight: 400;
            margin: 0 0 6px;
            letter-spacing: 0.3px;
            word-break: break-word;
        }

        .editorial-title-underline {
            width: 32px;
            height: 2px;
            background: var(--burgundy);
            margin-bottom: 8px;
        }

        .editorial-desc {
            font-size: clamp(0.80rem, 1.8vw, 0.90rem);
            color: #5d564e;
            max-width: 440px;
            line-height: 1.42;
            margin: 0;
            word-break: break-word;
        }

        .editorial-header-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 5px;
            flex-shrink: 0;
            min-width: 0;
            padding-top: 2px;
        }

        .editorial-header-actions-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .editorial-slip-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ffffff;
            border: 1.5px solid rgba(104, 20, 24, 0.18);
            color: #681418;
            font-family: var(--font-sans);
            font-size: 0.80rem;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 20px;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            white-space: nowrap;
            user-select: none;
            text-decoration: none;
            line-height: 1.2;
        }

        .editorial-slip-btn:hover {
            background: #FAF5EB;
            border-color: #681418;
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(104, 20, 24, 0.12);
        }

        .editorial-slip-btn:active {
            transform: translateY(0);
        }

        .editorial-slip-btn svg {
            color: #681418;
            flex-shrink: 0;
        }

        .editorial-slip-btn .slip-badge-count {
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 10px;
            background: #681418;
            color: #ffffff;
            font-size: 0.68rem;
            font-weight: 800;
            margin-left: 2px;
        }

        .editorial-counter-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            border-left: 1.5px solid rgba(104, 20, 24, 0.2);
            padding-left: 10px;
            line-height: 1;
        }

        .counter-number {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--burgundy);
        }

        .counter-label {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #8c7355;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .editorial-cursive-quote {
            position: relative;
            margin-top: 4px;
            font-family: 'Caveat', cursive;
            font-size: clamp(1.15rem, 2.6vw, 1.45rem);
            color: #a67c52;
            white-space: nowrap;
            transform: rotate(-3.5deg);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .cursive-underline-svg {
            width: 100%;
            height: 9px;
            color: #a67c52;
            margin-top: -3px;
            opacity: 0.85;
        }

        /* Dishes Catalog Stack - Split Cards */
        .dishes-catalog-stack {
            display: flex;
            flex-direction: column;
            gap: 18px;
            width: 100%;
            box-sizing: border-box;
        }

        .dish-split-card {
            background: #FAF5EB;
            border-radius: 22px;
            overflow: visible;
            box-shadow: 0 8px 24px rgba(70, 25, 20, 0.07);
            border: 1px solid rgba(104, 20, 24, 0.08);
            display: flex;
            flex-direction: row;
            position: relative;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
        }

        .dish-split-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 34px rgba(70, 25, 20, 0.12);
        }

        /* =====================================================================
           CHEF'S SPECIAL AUTHENTIC 3D BRASS PAPER CLIP & PARCHMENT TAG
           ===================================================================== */
        .chef-paperclip-container {
            position: absolute;
            top: -12px;
            left: 14px;
            right: auto;
            z-index: 25;
            display: inline-flex;
            align-items: flex-start;
            pointer-events: auto;
            cursor: pointer;
            filter: drop-shadow(0 4px 8px rgba(40, 15, 10, 0.28));
            transition: transform 0.25s ease;
        }

        .chef-paperclip-container:hover {
            transform: translateY(-2px) scale(1.03);
        }

        .chef-parchment-note {
            background-color: #FAF4E5;
            background-image: 
                radial-gradient(circle at 18% 22%, rgba(255, 255, 255, 0.95) 0%, transparent 35%),
                radial-gradient(circle at 82% 78%, rgba(212, 175, 55, 0.16) 0%, transparent 40%),
                url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='paperGrain'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3' stitchTiles='stitch'/%3E%3CfeColorMatrix type='matrix' values='0 0 0 0 0.82  0 0 0 0 0.74  0 0 0 0 0.58  0 0 0 0.10 0'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23paperGrain)'/%3E%3C/svg%3E"),
                linear-gradient(135deg, #FFFDF8 0%, #FAF2DD 50%, #F3E3C0 100%);
            border: 1px solid #D6BC85;
            border-bottom: 1.5px solid #BC9852;
            border-radius: 3px;
            padding: 5px 12px 4px 10px;
            box-shadow: 
                0 4px 14px rgba(30, 10, 5, 0.22),
                0 1px 3px rgba(30, 10, 5, 0.14),
                inset 0 0 10px rgba(184, 134, 11, 0.10),
                inset 1px 1px 0 rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            position: relative;
            transform: rotate(-2.5deg);
            transform-origin: right top;
            margin-right: -13px;
            margin-top: 2px;
            z-index: 26;
            white-space: nowrap;
        }

        .parchment-badge-text {
            font-family: 'Cinzel', 'Cormorant Garamond', Georgia, serif;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            color: #581116;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 4px;
            line-height: 1.2;
        }

        .parchment-badge-star {
            color: #b8860b;
            font-size: 0.72rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.15);
        }

        .parchment-badge-sub {
            font-family: 'Caveat', cursive;
            font-size: 0.82rem;
            font-weight: 600;
            color: #875723;
            margin-top: 1px;
            line-height: 1;
        }

        .paperclip-realistic-svg {
            width: 22px;
            height: 48px;
            z-index: 28;
            position: relative;
            margin-top: -5px;
            transform: rotate(4deg);
            flex-shrink: 0;
            filter: drop-shadow(1px 2px 3px rgba(30, 10, 5, 0.35));
        }

        .dish-split-card.has-chef-special .media-top-overlay,
        .dish-split-card:has(.chef-paperclip-container) .media-top-overlay {
            top: 42px;
        }

        /* =====================================================================
           CARD ANIMATIONS (GRADIENT FLOW, GOLD AURA, PULSE, COLOR SHIFT, TILT)
           High-Contrast, Radiant Luxury Visual Effects Visible on All Browsers
           ===================================================================== */
        @keyframes gradientFlowBorder {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .dish-split-card.card-anim-gradient-shimmer,
        .section-card.card-anim-gradient-shimmer {
            position: relative;
            border: 2.5px solid transparent !important;
            background: linear-gradient(var(--card-bg-active, #FAF5EB), var(--card-bg-active, #FAF5EB)) padding-box,
                        linear-gradient(115deg, #e6b800, #80141a, #ffd700, #b32d36, #e6b800, #681418) border-box !important;
            background-size: 100% 100%, 300% 300% !important;
            animation: gradientFlowBorder 4s linear infinite !important;
            box-shadow: 0 10px 32px rgba(104, 20, 24, 0.22), 0 0 18px rgba(212, 175, 55, 0.3) !important;
        }

        @keyframes goldAuraPulse {
            0%, 100% {
                border-color: #d4af37 !important;
                box-shadow: 0 8px 24px rgba(70, 25, 20, 0.08), 0 0 18px rgba(212, 175, 55, 0.45);
            }
            50% {
                border-color: #ffdf73 !important;
                box-shadow: 0 14px 38px rgba(70, 25, 20, 0.16), 0 0 34px rgba(255, 215, 0, 0.7);
            }
        }

        .dish-split-card.card-anim-gold-aura,
        .section-card.card-anim-gold-aura {
            border: 2.5px solid #d4af37 !important;
            animation: goldAuraPulse 2.8s ease-in-out infinite !important;
        }

        @keyframes burgundyPulseBreathe {
            0%, 100% {
                border-color: #681418 !important;
                box-shadow: 0 8px 24px rgba(70, 25, 20, 0.08), 0 0 16px rgba(104, 20, 24, 0.35);
            }
            50% {
                border-color: #a81c24 !important;
                box-shadow: 0 14px 38px rgba(104, 20, 24, 0.35), 0 0 32px rgba(168, 28, 36, 0.65);
            }
        }

        .dish-split-card.card-anim-burgundy-pulse,
        .section-card.card-anim-burgundy-pulse {
            border: 2.5px solid #681418 !important;
            animation: burgundyPulseBreathe 3s ease-in-out infinite !important;
        }

        @keyframes cardBorderColorShift {
            0% { border-color: #d4af37 !important; box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4); }
            25% { border-color: #681418 !important; box-shadow: 0 8px 25px rgba(104, 20, 24, 0.4); }
            50% { border-color: #c99a68 !important; box-shadow: 0 8px 25px rgba(201, 154, 104, 0.4); }
            75% { border-color: #a81c24 !important; box-shadow: 0 8px 25px rgba(168, 28, 36, 0.4); }
            100% { border-color: #d4af37 !important; box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4); }
        }

        .dish-split-card.card-anim-color-shift,
        .section-card.card-anim-color-shift {
            border: 2.5px solid #d4af37 !important;
            animation: cardBorderColorShift 5s linear infinite !important;
        }

        @keyframes gentleCardFloat {
            0% {
                transform: translateY(0px);
                box-shadow: 0 8px 24px rgba(70, 25, 20, 0.08);
            }
            100% {
                transform: translateY(-6px);
                box-shadow: 0 18px 38px rgba(70, 25, 20, 0.18), 0 0 18px rgba(201, 154, 104, 0.25);
            }
        }

        .dish-split-card.card-anim-floating-tilt,
        .section-card.card-anim-floating-tilt {
            animation: gentleCardFloat 3s ease-in-out infinite alternate !important;
            transition: transform 0.35s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.35s ease;
        }

        .dish-split-card.card-anim-floating-tilt:hover,
        .section-card.card-anim-floating-tilt:hover {
            transform: translateY(-9px) scale(1.015) !important;
            box-shadow: 0 24px 48px rgba(70, 25, 20, 0.22), 0 0 24px rgba(201, 154, 104, 0.3) !important;
        }

        /* =====================================================================
           TOP EXPANDABLE SEARCH DRAWER & SMART RECOMMENDATIONS
           ===================================================================== */
        .masthead-search-toggle {
            cursor: pointer;
            border: 1px solid rgba(104, 20, 24, 0.15);
            background: rgba(104, 20, 24, 0.05);
            color: var(--burgundy);
            transition: all 0.25s ease;
        }

        .masthead-search-toggle:hover,
        .masthead-search-toggle.active {
            background: #681418;
            color: #FAF5EE;
            border-color: #681418;
            box-shadow: 0 4px 14px rgba(104, 20, 24, 0.22);
        }

        .top-search-drawer {
            max-width: 920px;
            margin: 0 auto 28px;
            background: #FFFFFF;
            border: 1.5px solid rgba(104, 20, 24, 0.16);
            border-radius: 24px;
            padding: 22px 24px;
            box-shadow: 0 16px 44px rgba(60, 18, 14, 0.12), 0 2px 8px rgba(0, 0, 0, 0.04);
            animation: topDrawerExpand 0.28s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            z-index: 50;
        }

        @keyframes topDrawerExpand {
            from {
                opacity: 0;
                transform: translateY(-10px) scale(0.985);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .top-search-input-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .top-search-input-box {
            position: relative;
            display: flex;
            align-items: center;
            background: #FAF7F2;
            border: 1.5px solid rgba(104, 20, 24, 0.16);
            border-radius: 50px;
            padding: 10px 18px;
            flex: 1;
            transition: all 0.25s ease;
        }

        .top-search-input-box:focus-within {
            background: #FFFFFF;
            border-color: #681418;
            box-shadow: 0 6px 20px rgba(104, 20, 24, 0.12);
        }

        .close-top-search-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            background: rgba(104, 20, 24, 0.06);
            border: 1px solid rgba(104, 20, 24, 0.15);
            border-radius: 50px;
            color: #681418;
            font-family: var(--font-body);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .close-top-search-btn:hover {
            background: #681418;
            color: #FFFFFF;
            border-color: #681418;
            transform: scale(1.02);
        }

        .search-box-icon {
            color: #681418;
            margin-right: 12px;
            flex-shrink: 0;
        }

        #menuMainSearchInput {
            width: 100%;
            border: none;
            outline: none;
            background: transparent;
            font-family: var(--font-body);
            font-size: 0.95rem;
            color: #2B241E;
        }

        #menuMainSearchInput::placeholder {
            color: rgba(43, 36, 30, 0.45);
        }

        .clear-search-btn {
            background: none;
            border: none;
            font-size: 1.3rem;
            line-height: 1;
            color: #9c9890;
            cursor: pointer;
            padding: 0 4px;
            transition: color 0.2s ease;
        }

        .clear-search-btn:hover {
            color: #681418;
        }

        .search-recommendation-chips {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
            justify-content: flex-start;
        }

        .recommendation-label {
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(104, 20, 24, 0.7);
            margin-right: 4px;
        }

        .recom-chip {
            background: #FAF7F2;
            border: 1px solid rgba(104, 20, 24, 0.16);
            border-radius: 50px;
            padding: 6px 14px;
            font-family: var(--font-body);
            font-size: 0.8rem;
            font-weight: 600;
            color: #500f12;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.22s ease;
            box-shadow: 0 1px 4px rgba(70, 25, 20, 0.03);
        }

        .recom-chip:hover {
            background: #681418;
            color: #ffffff;
            border-color: #681418;
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(104, 20, 24, 0.22);
        }

        .recom-chip.active {
            background: #681418;
            color: #ffffff;
            border-color: #681418;
            box-shadow: 0 4px 12px rgba(104, 20, 24, 0.25);
        }

        .recom-chip.chip-chef-special {
            background: linear-gradient(135deg, #FFFDF8, #FAF1DE);
            border: 1.5px solid #d4af37;
            color: #581116;
            font-weight: 700;
        }

        .recom-chip.chip-chef-special:hover,
        .recom-chip.chip-chef-special.active {
            background: linear-gradient(135deg, #581116, #7d1c21);
            color: #FFFDF8;
            border-color: #d4af37;
        }

        /* Live Results Panel */
        .search-live-results-panel {
            background: #FAF7F2;
            border: 1px solid rgba(104, 20, 24, 0.12);
            border-radius: 18px;
            margin-top: 16px;
            padding: 16px;
            max-height: 440px;
            overflow-y: auto;
            animation: fadeInResults 0.2s ease-out;
        }

        @keyframes fadeInResults {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid rgba(104, 20, 24, 0.08);
        }

        .results-count-title {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.25rem;
            font-weight: 600;
            color: #681418;
        }

        .close-results-btn {
            background: none;
            border: none;
            color: #9c9890;
            font-size: 0.85rem;
            cursor: pointer;
            font-weight: 600;
            transition: color 0.2s;
        }

        .close-results-btn:hover {
            color: #681418;
        }

        .results-scroll-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 12px;
        }

        @media (max-width: 600px) {
            .results-scroll-grid {
                grid-template-columns: 1fr;
            }
        }

        .search-result-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 14px;
            border-radius: 14px;
            border: 1px solid rgba(104, 20, 24, 0.08);
            background: #FAF7F2;
            cursor: pointer;
            transition: all 0.22s ease;
            position: relative;
        }

        .search-result-card:hover {
            background: #ffffff;
            border-color: #681418;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(70, 25, 20, 0.1);
        }

        .result-thumb {
            width: 64px;
            height: 64px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .result-meta {
            flex: 1;
            min-width: 0;
        }

        .result-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }

        .result-name {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.12rem;
            font-weight: 600;
            color: #2B241E;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .result-price {
            font-weight: 700;
            color: #681418;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .result-badge-row {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
            font-size: 0.72rem;
        }

        .result-cat-pill {
            background: rgba(104, 20, 24, 0.08);
            color: #681418;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
        }

        .result-special-pill {
            background: linear-gradient(135deg, #FFFDF8, #FAF1DE);
            border: 1px solid #d4af37;
            color: #581116;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 700;
        }

        .detail-search-trigger-btn {
            background: rgba(104, 20, 24, 0.08);
            border: 1px solid rgba(104, 20, 24, 0.15);
            border-radius: 50px;
            padding: 6px 14px;
            color: #681418;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .detail-search-trigger-btn:hover {
            background: #681418;
            color: #ffffff;
        }

        /* Left Half: Photography & Vignette Overlays */
        .split-card-media {
            flex: 0 0 46%;
            max-width: 46%;
            min-width: 0;
            position: relative;
            min-height: 240px;
            overflow: hidden;
            background: #e8ded2;
            box-sizing: border-box;
            border-top-left-radius: 20px;
            border-bottom-left-radius: 20px;
            -webkit-mask-image: -webkit-radial-gradient(white, black);
            transform: translateZ(0);
        }

        .split-card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.6s ease;
        }

        .dish-split-card:hover .split-card-img {
            transform: scale(1.05);
        }

        .split-card-vignette {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(20, 10, 8, 0.72) 0%, rgba(20, 10, 8, 0.08) 38%, rgba(20, 10, 8, 0.25) 60%, rgba(20, 10, 8, 0.88) 100%);
            pointer-events: none;
        }

        .media-top-overlay {
            position: absolute;
            top: 12px;
            left: 12px;
            right: 8px;
            z-index: 2;
            pointer-events: none;
        }

        .media-index-number {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: clamp(1.8rem, 4vw, 2.3rem);
            line-height: 1;
            color: #FAF5EE;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.7);
            display: block;
            font-weight: 400;
        }

        .media-accent-bar {
            width: 20px;
            height: 1.5px;
            background: rgba(250, 245, 238, 0.85);
            margin: 4px 0 6px;
        }

        .media-stacked-title {
            font-size: clamp(0.58rem, 1.4vw, 0.70rem);
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #FAF5EE;
            line-height: 1.25;
            text-transform: uppercase;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.8);
            display: flex;
            flex-direction: column;
            max-width: 130px;
        }

        .media-script-tagline {
            position: absolute;
            bottom: 12px;
            left: 12px;
            right: 8px;
            z-index: 2;
            font-family: 'Caveat', cursive;
            font-size: clamp(1.1rem, 2.6vw, 1.35rem);
            color: #FAF5EE;
            line-height: 1.1;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.85);
            transform: rotate(-4deg);
            pointer-events: none;
        }

        /* Right Half: Editorial Details */
        .split-card-details {
            flex: 1 1 54%;
            max-width: 54%;
            min-width: 0;
            padding: 14px 14px 14px 16px;
            background: transparent;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
            border-top-right-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .split-details-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 6px;
        }

        .title-and-price {
            flex: 1;
        }

        .split-dish-title {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: clamp(1.05rem, 2.5vw, 1.35rem);
            font-weight: 600;
            color: #2B241E;
            line-height: 1.15;
            margin: 0 0 2px;
        }

        .split-dish-price {
            font-size: clamp(0.95rem, 2.2vw, 1.15rem);
            font-weight: 700;
            color: #2B241E;
            margin-bottom: 4px;
        }

        .split-fav-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #a49a8f;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .split-fav-btn:hover {
            transform: scale(1.08);
            color: var(--burgundy);
        }

        .split-fav-btn.active {
            color: #b71c1c !important;
            border-color: rgba(183, 28, 28, 0.35) !important;
            background: #ffebee !important;
            transform: scale(1.1);
        }

        .split-fav-btn.active svg {
            fill: #b71c1c !important;
        }

        @keyframes heartPop {
            0% { transform: scale(1); }
            45% { transform: scale(1.38); }
            100% { transform: scale(1.1); }
        }

        .split-fav-btn.animate-pop {
            animation: heartPop 0.32s cubic-bezier(0.17, 0.89, 0.32, 1.49);
        }

        /* =========================================================================
           QUICK CATEGORY NAV (FAST HORIZONTAL SWITCHER IN DETAILED VIEW)
           ========================================================================= */
        .quick-category-nav {
            width: 100%;
            overflow: hidden;
            position: relative;
            background: rgba(251, 246, 238, 0.98);
            padding: 8px 0 10px;
            border-top: 1px solid rgba(104, 20, 24, 0.08);
        }

        .quick-category-track {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            padding: 2px 16px;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }

        .quick-category-track::-webkit-scrollbar {
            display: none;
        }

        .quick-cat-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 14px;
            border-radius: 20px;
            background: #ffffff;
            border: 1px solid rgba(104, 20, 24, 0.14);
            color: #4A3E36;
            font-family: var(--font-sans);
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            user-select: none;
        }

        .quick-cat-pill .pill-name {
            letter-spacing: 0.3px;
        }

        .quick-cat-pill .pill-count {
            font-size: 0.68rem;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 10px;
            background: rgba(104, 20, 24, 0.08);
            color: var(--burgundy);
            transition: all 0.2s ease;
        }

        .quick-cat-pill:hover {
            border-color: var(--burgundy);
            color: var(--burgundy);
            background: #FAF5EB;
            transform: translateY(-1px);
        }

        .quick-cat-pill.active {
            background: linear-gradient(135deg, #681418 0%, #4a0d10 100%);
            border-color: #681418;
            color: #FFF9F0;
            box-shadow: 0 4px 14px rgba(104, 20, 24, 0.32);
            transform: translateY(-1px);
        }

        .quick-cat-pill.active .pill-count {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        /* =========================================================================
           DINE-IN ORDER SLIP TRIGGER BUTTONS & BADGES
           ========================================================================= */
        .detail-slip-btn, .masthead-slip-toggle {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ffffff;
            border: 1px solid var(--border-light);
            padding: 6px 12px;
            border-radius: 20px;
            color: var(--burgundy);
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .detail-slip-btn:hover, .masthead-slip-toggle:hover {
            background: var(--burgundy);
            color: #FBF6EE;
            border-color: var(--burgundy);
        }

        .detail-slip-btn svg, .masthead-slip-toggle svg {
            color: #b71c1c;
            flex-shrink: 0;
        }

        .slip-badge-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 10px;
            background: #b71c1c;
            color: #ffffff;
            font-size: 0.68rem;
            font-weight: 800;
            transition: transform 0.2s ease;
        }

        /* =========================================================================
           FLOATING DINE-IN TABLE SLIP BOTTOM DOCK
           ========================================================================= */
        .floating-dine-in-slip {
            position: fixed;
            bottom: 22px;
            left: 50%;
            transform: translateX(-50%) translateY(140px);
            z-index: 9995;
            opacity: 0;
            pointer-events: none;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            width: calc(100% - 32px);
            max-width: 420px;
        }

        .floating-dine-in-slip.visible {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
            pointer-events: auto;
        }

        body.in-detailed-view .floating-dine-in-slip.visible {
            bottom: 80px;
        }

        .dine-in-slip-pill {
            width: 100%;
            background: linear-gradient(135deg, #1C0A0D 0%, #3B0D12 100%);
            border: 1.5px solid rgba(212, 175, 55, 0.55);
            border-radius: 30px;
            padding: 7px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.42), 0 0 18px rgba(104, 20, 24, 0.28);
            cursor: pointer;
            color: #FAF5EB;
            font-family: var(--font-sans);
            transition: all 0.25s ease;
        }

        .dine-in-slip-pill:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 34px rgba(0, 0, 0, 0.48), 0 0 24px rgba(212, 175, 55, 0.45);
            border-color: rgba(212, 175, 55, 0.9);
        }

        .slip-pill-left {
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
        }

        .slip-pill-icon {
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(212, 175, 55, 0.35);
            flex-shrink: 0;
        }

        .slip-pill-text-wrap {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .slip-pill-title {
            font-size: 0.82rem;
            font-weight: 800;
            color: #FAF5EB;
            letter-spacing: 0.3px;
        }

        .slip-pill-sub {
            font-size: 0.70rem;
            color: rgba(250, 245, 235, 0.82);
            font-weight: 600;
        }

        .slip-pill-right {
            display: flex;
            align-items: center;
            gap: 5px;
            background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
            color: #1A0D0E;
            font-weight: 800;
            font-size: 0.72rem;
            padding: 5px 12px;
            border-radius: 18px;
            letter-spacing: 0.3px;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(212, 175, 55, 0.35);
        }

        /* =========================================================================
           DINE-IN SAVED DISHES LIST MODAL
           ========================================================================= */
        .slip-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(26, 10, 12, 0.78);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 10000;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .slip-modal-backdrop.open,
        .slip-modal-backdrop.active {
            opacity: 1 !important;
            pointer-events: auto !important;
        }

        .slip-modal-dialog {
            background: #FAF5EB;
            width: 100%;
            max-width: 580px;
            max-height: 88vh;
            border-radius: 26px 26px 0 0;
            box-shadow: 0 -15px 45px rgba(0, 0, 0, 0.38);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transform: translateY(100%);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(104, 20, 24, 0.14);
            border-bottom: none;
        }

        .slip-modal-backdrop.open .slip-modal-dialog,
        .slip-modal-backdrop.active .slip-modal-dialog {
            transform: translateY(0) !important;
        }

        @media (min-width: 640px) {
            .slip-modal-backdrop {
                align-items: center;
                padding: 20px;
            }
            .slip-modal-dialog {
                border-radius: 24px;
                max-height: 85vh;
                border-bottom: 1px solid rgba(104, 20, 24, 0.14);
                transform: translateY(30px) scale(0.97);
            }
            .slip-modal-backdrop.open .slip-modal-dialog,
            .slip-modal-backdrop.active .slip-modal-dialog {
                transform: translateY(0) scale(1) !important;
            }
        }

        .slip-modal-header {
            padding: 16px 20px;
            background: #ffffff;
            border-bottom: 1px solid rgba(104, 20, 24, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-shrink: 0;
        }

        .slip-header-brand {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .slip-header-eyebrow {
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #8a7153;
        }

        .slip-header-title {
            font-family: var(--font-heading);
            font-size: 1.35rem;
            color: var(--primary-accent);
            margin: 0;
            line-height: 1.2;
        }

        .slip-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .slip-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            line-height: 1;
            color: #8a7153;
            cursor: pointer;
            padding: 4px;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .slip-modal-close:hover {
            color: var(--burgundy);
            background: rgba(104, 20, 24, 0.06);
        }

        .slip-item-summary-pill,
        .slip-count-badge {
            font-size: 0.74rem;
            font-weight: 800;
            color: var(--burgundy);
            background: rgba(104, 20, 24, 0.08);
            padding: 4px 10px;
            border-radius: 12px;
        }

        .slip-items-scroll-area,
        .slip-items-container {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            -webkit-overflow-scrolling: touch;
        }

        /* Individual Dish Card in Slip (NO PRICING) */
        .slip-dish-card,
        .slip-item-row {
            background: #ffffff;
            border: 1px solid rgba(104, 20, 24, 0.1);
            border-radius: 14px;
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
        }

        .slip-dish-main-row,
        .slip-item-main {
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: space-between;
        }

        .slip-dish-thumb,
        .slip-item-thumb {
            width: 52px;
            height: 52px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .slip-dish-info,
        .slip-item-info {
            flex: 1 1 auto;
            min-width: 0;
        }

        .slip-dish-name,
        .slip-item-title {
            font-family: var(--font-heading);
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--primary-accent);
            line-height: 1.25;
            margin: 0 0 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .slip-dish-meta,
        .slip-item-meta-top {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.72rem;
            color: #8a7153;
            font-weight: 600;
        }

        .slip-item-cat {
            font-size: 0.68rem;
            background: rgba(104, 20, 24, 0.06);
            color: #681418;
            padding: 2px 7px;
            border-radius: 10px;
            font-weight: 700;
        }

        .slip-dish-controls,
        .slip-item-right-col {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .slip-qty-stepper {
            display: inline-flex;
            align-items: center;
            border: 1px solid rgba(104, 20, 24, 0.25);
            border-radius: 18px;
            overflow: hidden;
            background: #FAF5EB;
        }

        .slip-stepper-btn,
        .slip-qty-btn {
            background: none;
            border: none;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.96rem;
            font-weight: 700;
            color: var(--burgundy);
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .slip-stepper-btn:hover,
        .slip-qty-btn:hover {
            background: rgba(104, 20, 24, 0.12);
        }

        .slip-stepper-val,
        .slip-qty-val {
            min-width: 24px;
            text-align: center;
            font-size: 0.86rem;
            font-weight: 800;
            color: #1A0D0E;
        }

        .slip-item-del-btn,
        .slip-remove-btn {
            background: none;
            border: none;
            color: #991b1b;
            font-size: 1.25rem;
            line-height: 1;
            cursor: pointer;
            padding: 3px 6px;
            border-radius: 4px;
            transition: background 0.15s ease;
        }

        .slip-item-del-btn:hover,
        .slip-remove-btn:hover {
            background: rgba(153, 27, 27, 0.1);
        }

        .slip-dish-note-input,
        .slip-item-note-input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid rgba(0, 0, 0, 0.09);
            border-radius: 6px;
            font-size: 0.76rem;
            color: #4A3E36;
            background: #FAF5EB;
            font-family: var(--font-sans);
            box-sizing: border-box;
        }

        .slip-dish-note-input:focus,
        .slip-item-note-input:focus {
            background: #ffffff;
            border-color: var(--burgundy);
            outline: none;
        }

        /* Top of List: Total Dishes */
        .slip-list-top-bar {
            padding: 10px 20px;
            background: #ffffff;
            border-bottom: 1px solid rgba(104, 20, 24, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .slip-total-dishes-label {
            font-size: 0.95rem;
            color: #1A0D0E;
            font-weight: 600;
        }

        .slip-total-dishes-label strong {
            color: #681418;
            font-weight: 800;
            font-size: 1.12rem;
            margin-left: 2px;
        }

        /* 3-Hour Auto-Expiry Notice (Out of box, small, without image, with hh:mm:ss) */
        .slip-expiry-plain-note {
            font-size: 0.74rem;
            color: #8a7153;
            text-align: center;
            line-height: 1.35;
            background: transparent;
            border: none;
            padding: 0;
            margin: 0;
            font-family: var(--font-sans);
        }

        .slip-expiry-plain-note strong {
            color: #681418;
            font-weight: 700;
        }

        .slip-expiry-timer-digits {
            font-weight: 800;
            color: #681418;
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.5px;
        }

        /* Empty State */
        .slip-empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #6b5e52;
        }

        .slip-empty-icon {
            font-size: 2.5rem;
            margin-bottom: 8px;
            opacity: 0.6;
        }

        .slip-empty-state h3,
        .slip-empty-state h4 {
            font-family: var(--font-heading);
            font-size: 1.3rem;
            color: var(--primary-accent);
            margin: 0 0 6px;
        }

        .slip-empty-state p {
            font-size: 0.82rem;
            margin: 0 0 16px;
            max-width: 320px;
            margin-inline: auto;
            line-height: 1.4;
        }

        /* Slip Footer (NO PRICING • MINIMAL & CLEAN) */
        .slip-modal-footer {
            background: #ffffff;
            border-top: 1px solid rgba(104, 20, 24, 0.1);
            padding: 14px 20px 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            flex-shrink: 0;
        }

        .slip-footer-btns {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-clear-slip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #FAF5EB;
            border: 1px solid rgba(153, 27, 27, 0.2);
            color: #991b1b;
            font-size: 0.82rem;
            font-weight: 700;
            padding: 10px 18px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-clear-slip:hover {
            background: #fee2e2;
        }

        .btn-done-ordering {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--burgundy);
            border: 1px solid var(--burgundy);
            color: #ffffff;
            font-size: 0.84rem;
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-done-ordering:hover {
            background: #4a0d10;
        }

        /* Luxury Aesthetic Toast Notification */
        .slip-toast-notice {
            position: fixed;
            top: 104px;
            left: 50%;
            transform: translateX(-50%) translateY(-18px) scale(0.96);
            background: rgba(28, 11, 14, 0.92);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            backdrop-filter: blur(18px) saturate(180%);
            color: #FFF8EE;
            border: 1px solid rgba(212, 175, 55, 0.38);
            padding: 6px 8px 6px 12px;
            border-radius: 999px;
            font-family: var(--font-sans);
            font-size: 0.82rem;
            z-index: 100000;
            box-shadow: 0 16px 36px -4px rgba(18, 6, 8, 0.5), 0 4px 12px rgba(0, 0, 0, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.12);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            opacity: 0;
            pointer-events: none;
            cursor: pointer;
            transition: transform 0.38s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.28s ease, box-shadow 0.28s ease;
            user-select: none;
            max-width: min(92vw, 440px);
            box-sizing: border-box;
        }

        .slip-toast-notice.visible {
            transform: translateX(-50%) translateY(0) scale(1);
            opacity: 1;
            pointer-events: auto;
        }

        .slip-toast-notice:hover {
            background: rgba(36, 13, 17, 0.96);
            border-color: rgba(212, 175, 55, 0.65);
            box-shadow: 0 20px 42px -4px rgba(18, 6, 8, 0.6), 0 6px 16px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.18);
        }

        .slip-toast-icon-wrap {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .slip-toast-icon-wrap.is-added {
            background: rgba(212, 175, 55, 0.2);
            color: #E8C87A;
            border: 1px solid rgba(212, 175, 55, 0.45);
        }

        .slip-toast-icon-wrap.is-removed {
            background: rgba(217, 83, 79, 0.2);
            color: #f28b82;
            border: 1px solid rgba(217, 83, 79, 0.4);
        }

        .slip-toast-text-wrap {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-width: 0;
            flex: 1 1 auto;
            line-height: 1.2;
        }

        .slip-toast-dish {
            font-weight: 700;
            color: #FFFFFF;
            max-width: 170px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .slip-toast-status {
            color: rgba(255, 248, 238, 0.72);
            font-size: 0.78rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .slip-toast-cta {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(212, 175, 55, 0.16);
            color: #E8C87A;
            border: 1px solid rgba(212, 175, 55, 0.38);
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .slip-toast-notice:hover .slip-toast-cta {
            background: #D4AF37;
            color: #1A0D0E;
            border-color: #D4AF37;
            transform: scale(1.03);
        }

        @media (max-width: 600px) {
            .slip-toast-notice {
                top: 96px;
                padding: 6px 8px 6px 10px;
                gap: 8px;
                max-width: calc(100vw - 24px);
            }
            .slip-toast-dish {
                max-width: 130px;
            }
        }

        .modal-add-slip-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--burgundy);
            color: #ffffff;
            border: 1px solid var(--burgundy);
            padding: 9px 18px;
            border-radius: 24px;
            font-size: 0.84rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-add-slip-btn:hover {
            background: #4a0d10;
            transform: translateY(-1px);
        }

        .modal-add-slip-btn.in-slip {
            background: #15803d;
            border-color: #15803d;
        }

        .split-dish-desc {
            font-size: clamp(0.68rem, 1.5vw, 0.76rem);
            color: #665b50;
            line-height: 1.35;
            margin: 0 0 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .split-divider {
            border-top: 1px solid rgba(104, 20, 24, 0.08);
            margin: 4px 0 6px;
        }

        .split-highlights-list {
            display: flex;
            flex-direction: column;
            gap: 3px;
            margin-bottom: 6px;
        }

        .split-highlight-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: clamp(0.64rem, 1.4vw, 0.70rem);
            color: #5a5046;
            line-height: 1.25;
        }

        .highlight-icon {
            font-size: 0.76rem;
            line-height: 1;
            flex-shrink: 0;
        }

        .split-diet-row {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: #8a7153;
            text-transform: uppercase;
            margin: 8px 0 10px;
        }

        .diet-dot.veg {
            color: #388e3c;
        }

        .diet-dot.non-veg {
            color: #d32f2f;
        }

        .split-action-row {
            display: flex;
            align-items: center;
            margin-top: auto;
        }

        .split-view-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--burgundy);
            color: #FAF5EE;
            border: none;
            border-radius: 20px;
            padding: 6px 14px;
            font-size: clamp(0.70rem, 1.6vw, 0.78rem);
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(104, 20, 24, 0.22);
            transition: all 0.2s ease;
        }

        .split-view-btn:hover {
            background: #500f12;
            transform: translateY(-1px);
        }

        /* ==========================================================================
           FLOATING SLIDE NAVIGATION DOCK & CROSS-BROWSER OVERLAY SCRIM
           ========================================================================== */
        .dock-backdrop-scrim {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 120px;
            background: linear-gradient(to top, rgba(251, 246, 238, 0.98) 0%, rgba(251, 246, 238, 0.82) 48%, rgba(251, 246, 238, 0) 100%);
            pointer-events: none;
            z-index: 990;
        }

        .floating-slide-dock {
            position: fixed;
            bottom: 18px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 24px);
            max-width: 440px;
            background: #FAF6EE; /* Solid fallback for browsers without backdrop-filter */
            background: rgba(251, 246, 238, 0.96);
            border-radius: 40px;
            box-shadow: 0 14px 40px rgba(45, 20, 15, 0.18), 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1.5px solid rgba(255, 255, 255, 0.95);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 7px 14px;
            box-sizing: border-box;
            -webkit-user-select: none;
            user-select: none;
        }

        @supports (backdrop-filter: blur(20px)) or (-webkit-backdrop-filter: blur(20px)) {
            .floating-slide-dock {
                background: rgba(251, 246, 238, 0.92);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
            }
        }

        .dock-nav-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: none;
            border: none;
            padding: 4px;
            cursor: pointer;
            color: inherit;
            text-decoration: none;
            border-radius: 24px;
            transition: background 0.2s;
            flex: 1;
        }

        .dock-nav-btn.next {
            justify-content: flex-end;
        }

        .dock-arrow-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(104, 20, 24, 0.06);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--burgundy);
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }

        .dock-nav-btn:hover .dock-arrow-circle {
            background: var(--burgundy);
            color: #FAF5EE;
            transform: scale(1.06);
        }

        .dock-text-col {
            display: flex;
            flex-direction: column;
            line-height: 1.15;
            text-align: left;
            max-width: 85px;
        }

        .dock-text-col.text-right {
            text-align: right;
        }

        .dock-action-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #2B241E;
            letter-spacing: 0.2px;
        }

        .dock-target-name {
            font-size: 0.65rem;
            color: #887d72;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .dock-center-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: -18px;
            padding: 0 8px;
            flex-shrink: 0;
        }

        .dock-explore-circle {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: var(--burgundy);
            color: #FAF5EE;
            border: 3px solid #FAF5EE;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 6px 18px rgba(104, 20, 24, 0.35);
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .dock-explore-circle:hover {
            transform: scale(1.08);
            background: #500f12;
        }

        .dock-explore-label {
            font-size: 0.64rem;
            font-weight: 600;
            color: #63574c;
            margin-top: 3px;
            letter-spacing: 0.2px;
            white-space: nowrap;
        }

        /* ==========================================================================
           DISH QUICK-VIEW MODAL
           ========================================================================== */
        .dish-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(20, 10, 8, 0.65);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        .dish-modal-backdrop.active {
            opacity: 1;
            pointer-events: auto;
        }

        .dish-modal-dialog {
            background: #FAF5EE;
            border-radius: 24px;
            max-width: 440px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            position: relative;
            transform: translateY(20px) scale(0.96);
            transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .dish-modal-backdrop.active .dish-modal-dialog {
            transform: translateY(0) scale(1);
        }

        .dish-modal-close-btn {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--burgundy);
            z-index: 5;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s;
        }

        .dish-modal-close-btn:hover {
            transform: scale(1.1);
        }

        .dish-modal-media {
            position: relative;
            width: 100%;
            height: 220px;
            background: #e8ded2;
        }

        .dish-modal-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dish-modal-badge {
            position: absolute;
            bottom: 12px;
            left: 14px;
            background: rgba(251, 246, 238, 0.95);
            color: var(--burgundy);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.70rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .dish-modal-content {
            padding: 20px 22px 24px;
        }

        .dish-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }

        .dish-modal-title {
            font-family: 'Dream Avenue', 'Cormorant Garamond', Georgia, serif;
            font-size: 1.55rem;
            color: var(--burgundy);
            margin: 0;
            line-height: 1.15;
        }

        .dish-modal-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--burgundy);
            white-space: nowrap;
        }

        .dish-modal-desc {
            font-size: 0.88rem;
            color: #63574c;
            line-height: 1.5;
            margin: 0 0 18px;
        }

        .dish-modal-wa-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #25D366;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 28px;
            font-weight: 700;
            font-size: 0.88rem;
            letter-spacing: 0.5px;
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.3);
            transition: all 0.2s ease;
        }

        .dish-modal-wa-btn:hover {
            background: #1eb956;
            transform: translateY(-2px);
        }

        /* Mobile Viewport Optimizations */
        @media (max-width: 768px) {
            .menu-main-wrap {
                padding: 15px 14px 50px;
            }

            .sections-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .swipe-deck-viewport {
                padding: 0;
            }

            .section-slide-pane {
                padding: 10px 14px 210px;
            }

            .editorial-header-actions-row {
                gap: 8px;
            }

            .editorial-slip-btn {
                padding: 4px 10px;
                font-size: 0.76rem;
            }

            .dish-split-card {
                border-radius: 18px;
            }

            .split-card-media {
                flex: 0 0 47%;
                min-height: 220px;
                border-top-left-radius: 16px;
                border-bottom-left-radius: 16px;
            }

            .split-card-details {
                flex: 1 1 53%;
                padding: 12px 10px;
                border-top-right-radius: 16px;
                border-bottom-right-radius: 16px;
            }
        }


    </style>
</head>
<body>

    <div class="views-wrapper">

        <!-- ==========================================================================
             VIEW 1: MENU SECTIONS OVERVIEW (First Page)
             ========================================================================== -->
        <section id="sectionsOverviewView">
            <!-- Redesigned Top Masthead -->
            <div class="menu-top-masthead">
                <div class="masthead-utility-row">
                    <a href="index.php" class="masthead-back-link" aria-label="Return to Homepage">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                        <span>Home</span>
                    </a>

                    <div class="masthead-brand-crest">
                        <img src="assets/images/swans_only.png" alt="Orah Emblem" class="masthead-swans-img">
                    </div>

                    <div class="masthead-right-actions" style="display:flex; align-items:center; gap:8px;">
                        <button type="button" class="masthead-back-link masthead-slip-toggle" id="overviewSlipBtn" aria-label="View Dine-In List">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><path d="M9 12h6M9 16h4"/></svg>
                            <span>Dine-In</span>
                            <span class="slip-badge-count" style="display:none;">0</span>
                        </button>

                        <button type="button" class="masthead-back-link masthead-search-toggle" id="toggleTopSearchBtn" aria-expanded="false" aria-label="Toggle Search">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <span>Search</span>
                        </button>
                    </div>
                </div>

                <!-- Top Expandable Search Drawer (Click to expand) -->
                <div class="top-search-drawer" id="topSearchDrawer" style="display: none;">
                    <div class="top-search-input-row">
                        <div class="top-search-input-box">
                            <svg class="search-box-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" id="menuMainSearchInput" placeholder="Search dish name, ingredients, or pairings..." autocomplete="off">
                            <button type="button" id="clearMenuSearchBtn" class="clear-search-btn" aria-label="Clear search" style="display:none;">&times;</button>
                        </div>
                        <button type="button" class="close-top-search-btn" id="closeTopSearchBtn" aria-label="Close search">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            <span>Close</span>
                        </button>
                    </div>

                    <!-- Recommendations Chips -->
                    <div class="search-recommendation-chips">
                        <span class="recommendation-label">Recommendations:</span>
                        <button type="button" class="recom-chip chip-chef-special" data-recom="chef_special">
                            Chef's Specials
                        </button>
                        <button type="button" class="recom-chip" data-recom="featured">
                            Signatures
                        </button>
                        <button type="button" class="recom-chip" data-recom="truffle">
                            Truffle Picks
                        </button>
                        <button type="button" class="recom-chip" data-recom="sourdough">
                            Sourdough Flatbreads
                        </button>
                        <button type="button" class="recom-chip" data-recom="veg">
                            Pure Veg
                        </button>
                        <button type="button" class="recom-chip" data-recom="pasta">
                            Artisan Pastas
                        </button>
                        <button type="button" class="recom-chip" data-recom="dessert">
                            Desserts
                        </button>
                    </div>

                    <!-- Live Search Results Dropdown/Drawer -->
                    <div class="search-live-results-panel" id="searchLiveResultsPanel" style="display:none;">
                        <div class="results-header">
                            <div class="results-count-title" id="resultsCountTitle">Recommended Dishes</div>
                            <button type="button" class="close-results-btn" id="closeResultsPanelBtn">&times; Clear</button>
                        </div>
                        <div class="results-scroll-grid" id="resultsScrollGrid">
                            <!-- Populated dynamically by JS -->
                        </div>
                    </div>
                </div>

                <div class="masthead-center-content">
                    <h1 class="masthead-title">The Menu</h1>

                    <p class="masthead-tagline">
                        Handcrafted sourdoughs, slow-cooked pastas, and specialty estate roasts. Tap any section to explore.
                    </p>
                </div>
            </div>

            <div class="sections-grid-container">
                <div class="sections-grid">
                    <?php foreach ($orderedSections as $sectionName => $dishes): ?>
                        <?php 
                            $meta = $sectionMeta[$sectionName] ?? [
                                'subtitle' => 'Handcrafted delicacies prepared with fine estate ingredients.',
                                'image' => 'uploads/artisanal_pizza.jpg',
                                'animation' => 'none'
                            ];
                            $count = count($dishes);
                            $catAnim = $meta['animation'] ?? 'none';
                            $catAnimClass = '';
                            if ($catAnim === 'gradient_shimmer') $catAnimClass = 'card-anim-gradient-shimmer';
                            elseif ($catAnim === 'gold_aura') $catAnimClass = 'card-anim-gold-aura';
                            elseif ($catAnim === 'burgundy_pulse') $catAnimClass = 'card-anim-burgundy-pulse';

                            $catCardBg = $meta['card_bg'] ?? '';
                            $catBorder = $meta['border_color'] ?? '';
                            $catCustomStyle = '';
                            if (!empty($catCardBg)) $catCustomStyle .= "--card-bg-active: {$catCardBg}; background-color: {$catCardBg} !important; ";
                            if (!empty($catBorder)) $catCustomStyle .= "border-color: {$catBorder} !important; border-width: 2.5px !important; ";
                        ?>
                        <?php 
                            $displayName = ($sectionName === 'Pasta') ? 'Pastas' : $sectionName;
                        ?>
                        <article class="section-card <?= $catAnimClass ?>" style="<?= $catCustomStyle ?>" data-section-target="<?= htmlspecialchars($sectionName) ?>" tabindex="0" role="button" aria-label="Explore <?= htmlspecialchars($displayName) ?>">
                            <!-- Full Bleed Background Media with Warm Dark Vignette -->
                            <div class="section-card-bg">
                                <img src="<?= htmlspecialchars($meta['image']) ?>" alt="<?= htmlspecialchars($displayName) ?>" class="section-card-img" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=700&q=80'">
                                <div class="section-card-overlay"></div>
                            </div>

                            <!-- Top-Left Maroon Pill Badge -->
                            <div class="section-card-badge">
                                <span><?= $count ?> ITEMS</span>
                            </div>

                            <!-- Bottom Content Stack -->
                            <div class="section-card-body">
                                <h2 class="section-card-title"><?= htmlspecialchars($displayName) ?></h2>
                                <div class="section-title-line"></div>
                                <p class="section-card-desc"><?= htmlspecialchars($meta['subtitle']) ?></p>

                                <div class="section-card-footer">
                                    <span class="explore-label">EXPLORE</span>
                                    <div class="explore-circle-btn" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                            <polyline points="12 5 19 12 12 19"></polyline>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

            </div>
        </section>


        <!-- ==========================================================================
             VIEW 2: DETAILED MENU WITH TOUCH SWIPE CONTROLLER
             ========================================================================== -->
        <section id="detailMenuView">
            <!-- Sticky Top Header in Detailed View -->
            <div class="detail-nav-bar">
                <div class="detail-nav-container">
                    <button type="button" class="back-to-sections-btn" id="backToSectionsBtn">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                        <span>All Sections</span>
                    </button>

                    <div class="detail-brand-crest">
                        <img src="assets/images/swans_only.png" alt="Orah Emblem">
                    </div>

                    <div class="detail-nav-right-actions" style="display:flex; align-items:center; gap:8px;">
                        <button type="button" class="detail-search-trigger-btn" id="detailSearchTriggerBtn" aria-label="Search Dishes & Recommendations">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <span>Search</span>
                        </button>
                    </div>
                </div>

                <!-- Quick Nav Horizontal Track for Fast Category Switch -->
                <nav class="quick-category-nav" id="quickCategoryNav" aria-label="Quick Category Switch">
                    <div class="quick-category-track" id="quickCategoryTrack">
                        <?php $qIdx = 0; ?>
                        <?php foreach ($orderedSections as $secK => $dishesK): ?>
                            <?php 
                                $pillName = ($secK === 'Pasta') ? 'Pastas' : $secK;
                                $pCount = count($dishesK);
                            ?>
                            <button type="button" 
                                    class="quick-cat-pill <?= $qIdx === 0 ? 'active' : '' ?>" 
                                    data-section-key="<?= htmlspecialchars($secK) ?>" 
                                    data-section-index="<?= $qIdx ?>"
                                    aria-label="Switch to <?= htmlspecialchars($pillName) ?>">
                                <span><?= htmlspecialchars($pillName) ?></span>
                                <span class="quick-cat-count"><?= $pCount ?></span>
                            </button>
                            <?php $qIdx++; ?>
                        <?php endforeach; ?>
                    </div>
                </nav>
            </div>

            <!-- Swiping Viewport with Slide Deck -->
            <div class="swipe-deck-viewport" id="swipeDeckViewport">
                <div class="swipe-deck-slider" id="swipeDeckSlider">
                    <?php 
                        $sIndex = 0;
                        $totalSecCount = count($orderedSections);
                    ?>
                    <?php foreach ($orderedSections as $sKey => $dishes): ?>
                        <?php 
                            $meta = $sectionMeta[$sKey] ?? [
                                'eyebrow' => 'OUR SIGNATURES',
                                'scriptQuote' => 'Artisan Craft',
                                'subtitle' => 'Handcrafted delicacies prepared with fine estate ingredients.'
                            ];
                            $count = count($dishes);
                            $displayName = ($sKey === 'Pasta') ? 'Pastas' : $sKey;

                            // Sort dishes inside this category by explicit order number
                            usort($dishes, function($a, $b) {
                                $ordA = isset($a['order']) && is_numeric($a['order']) ? (int)$a['order'] : 999;
                                $ordB = isset($b['order']) && is_numeric($b['order']) ? (int)$b['order'] : 999;
                                if ($ordA === $ordB) return 0;
                                return ($ordA < $ordB) ? -1 : 1;
                            });
                        ?>
                        <div class="section-slide-pane" data-section-key="<?= htmlspecialchars($sKey) ?>" data-pane-index="<?= $sIndex ?>">
                            <!-- Top Editorial Section Header matching reference mockup -->
                            <header class="editorial-section-header">
                                <div class="editorial-header-left">
                                    <span class="editorial-eyebrow"><?= htmlspecialchars($meta['eyebrow'] ?? 'OUR SIGNATURES') ?></span>
                                    <h2 class="editorial-title"><?= htmlspecialchars($displayName) ?></h2>
                                    <div class="editorial-title-underline"></div>
                                    <p class="editorial-desc"><?= htmlspecialchars($meta['subtitle']) ?></p>
                                </div>
                                <div class="editorial-header-right">
                                    <div class="editorial-header-actions-row">
                                        <button type="button" class="editorial-slip-btn open-slip-btn" aria-label="View Dine-In List">
                                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><path d="M9 12h6M9 16h4"/></svg>
                                            <span>Dine-In</span>
                                            <span class="slip-badge-count" style="display:none;">0</span>
                                        </button>
                                        <div class="editorial-counter-box">
                                            <span class="counter-number"><?= $count ?></span>
                                            <span class="counter-label">ITEMS</span>
                                        </div>
                                    </div>
                                    <div class="editorial-cursive-quote">
                                        <span><?= htmlspecialchars($meta['scriptQuote'] ?? 'Artisan Craft') ?></span>
                                        <svg class="cursive-underline-svg" viewBox="0 0 100 20" preserveAspectRatio="none">
                                            <path d="M 2 12 Q 50 18 98 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                </div>
                            </header>

                            <!-- Stack of Editorial Split Dish Cards -->
                            <div class="dishes-catalog-stack">
                                <?php foreach ($dishes as $dIdx => $dish): ?>
                                    <?php 
                                        $dishName = $dish['name'] ?? 'Untitled Dish';
                                        $particular = $dish['particular'] ?? $dishName;
                                        $dishPrice = $dish['price'] ?? '₹0';
                                        $dishDesc = $dish['description'] ?? '';
                                        $dishImg = $dish['image'] ?? 'uploads/artisanal_pizza.jpg';
                                        $isVeg = !empty($dish['is_veg']);
                                        $indexNum = str_pad($dIdx + 1, 2, '0', STR_PAD_LEFT);
                                        $highlights = getDishHighlights($dish);
                                        $tagline = !empty($dish['tagline']) ? $dish['tagline'] : getDishTagline($dIdx, $dish);
                                        $nameWords = preg_split('/[\s&]+/', strtoupper($dishName));
                                        $customFields = $dish['custom_fields'] ?? [];

                                        $isChefSpecial = !empty($dish['is_chef_special']);
                                        $chefSpecialNote = $dish['chef_special_note'] ?? '';
                                        $cardStyle = $dish['card_style'] ?? [];
                                        $cardAnim = $cardStyle['animation'] ?? 'none';
                                        $cardBg = $cardStyle['bg_color'] ?? '';
                                        $cardBorder = $cardStyle['border_color'] ?? '';

                                        $cardAnimClass = '';
                                        if ($cardAnim === 'gradient_shimmer') $cardAnimClass = 'card-anim-gradient-shimmer';
                                        elseif ($cardAnim === 'gold_aura') $cardAnimClass = 'card-anim-gold-aura';
                                        elseif ($cardAnim === 'burgundy_pulse') $cardAnimClass = 'card-anim-burgundy-pulse';
                                        elseif ($cardAnim === 'color_shift') $cardAnimClass = 'card-anim-color-shift';
                                        elseif ($cardAnim === 'floating_tilt') $cardAnimClass = 'card-anim-floating-tilt';

                                        $cardInlineStyle = '';
                                        if (!empty($cardBg)) $cardInlineStyle .= "--card-bg-active: {$cardBg}; background-color: {$cardBg} !important; ";
                                        if (!empty($cardBorder)) $cardInlineStyle .= "border-color: {$cardBorder} !important; border-width: 2.5px !important; ";
                                    ?>
                                    <article class="dish-split-card <?= $cardAnimClass ?> <?= $isChefSpecial ? 'has-chef-special' : '' ?>" style="<?= $cardInlineStyle ?>" data-dish-id="<?= htmlspecialchars($dish['id'] ?? '') ?>">
                                        <?php if ($isChefSpecial): ?>
                                            <!-- Realistic 3D Brass Paper Clip holding Chef's Special Parchment Tag -->
                                            <div class="chef-paperclip-container" title="Chef's Special Selection" onclick="event.stopPropagation();">
                                                <div class="chef-parchment-note">
                                                    <div class="parchment-badge-text">
                                                        <span class="parchment-badge-star">✦</span> CHEF'S SPECIAL
                                                    </div>
                                                    <?php if (!empty($chefSpecialNote)): ?>
                                                        <div class="parchment-badge-sub"><?= htmlspecialchars($chefSpecialNote) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <svg class="paperclip-realistic-svg" viewBox="0 0 28 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <defs>
                                                        <linearGradient id="brassGrad_<?= $sIndex ?>_<?= $dIdx ?>" x1="0%" y1="0%" x2="100%" y2="100%">
                                                            <stop offset="0%" stop-color="#fff8dc"/>
                                                            <stop offset="25%" stop-color="#d4af37"/>
                                                            <stop offset="50%" stop-color="#9a6e14"/>
                                                            <stop offset="75%" stop-color="#ffd966"/>
                                                            <stop offset="90%" stop-color="#b8860b"/>
                                                            <stop offset="100%" stop-color="#694d0c"/>
                                                        </linearGradient>
                                                        <filter id="brassShadow_<?= $sIndex ?>_<?= $dIdx ?>" x="-40%" y="-20%" width="180%" height="150%">
                                                            <feDropShadow dx="1.5" dy="3" stdDeviation="1.8" flood-color="rgba(35,12,6,0.52)"/>
                                                        </filter>
                                                    </defs>
                                                    <path d="M 9 28 L 9 46 C 9 53 19 53 19 46 L 19 14 C 19 6 6 6 6 14 L 6 44 C 6 56 22 56 22 44 L 22 18" 
                                                          stroke="url(#brassGrad_<?= $sIndex ?>_<?= $dIdx ?>)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" filter="url(#brassShadow_<?= $sIndex ?>_<?= $dIdx ?>)"/>
                                                    <path d="M 8.5 27 L 8.5 45" stroke="rgba(255,255,255,0.7)" stroke-width="0.8" stroke-linecap="round"/>
                                                    <path d="M 21.5 16 L 21.5 44" stroke="rgba(255,255,255,0.6)" stroke-width="0.8" stroke-linecap="round"/>
                                                </svg>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Left Side: Photo with Vignette Overlays -->
                                        <div class="split-card-media">
                                            <img src="<?= htmlspecialchars($dishImg) ?>" alt="<?= htmlspecialchars($dishName) ?>" class="split-card-img" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=700&q=80'">
                                            <div class="split-card-vignette"></div>

                                            <!-- Top Left: 01, Accent Line, Stacked Caps Title -->
                                            <div class="media-top-overlay">
                                                <span class="media-index-number"><?= $indexNum ?></span>
                                                <div class="media-accent-bar"></div>
                                                <div class="media-stacked-title">
                                                    <?php foreach (array_slice($nameWords, 0, 3) as $w): ?>
                                                        <span><?= htmlspecialchars($w) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>

                                            <!-- Bottom Left: Cursive Script Tagline -->
                                            <div class="media-script-tagline">
                                                <?= htmlspecialchars($tagline) ?>
                                            </div>
                                        </div>

                                        <!-- Right Side: Details, Highlights, & Action Button -->
                                        <div class="split-card-details">
                                            <div>
                                                <div class="split-details-header">
                                                    <div class="title-and-price">
                                                        <h3 class="split-dish-title"><?= htmlspecialchars($dishName) ?></h3>
                                                        <div class="split-dish-price"><?= htmlspecialchars($dishPrice) ?></div>
                                                    </div>
                                                    <button type="button" class="split-fav-btn" 
                                                            aria-label="Add <?= htmlspecialchars($dishName) ?> to Dine-In List"
                                                            data-dish-id="<?= htmlspecialchars(md5($dishName . $sKey)) ?>"
                                                            data-dish-name="<?= htmlspecialchars($dishName) ?>"
                                                            data-dish-price="<?= htmlspecialchars($dishPrice) ?>"
                                                            data-dish-img="<?= htmlspecialchars($dishImg) ?>"
                                                            data-dish-veg="<?= $isVeg ? '1' : '0' ?>"
                                                            data-dish-cat="<?= htmlspecialchars($displayName) ?>"
                                                            title="Add to Dine-In Table Slip">
                                                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                                                        </svg>
                                                    </button>
                                                </div>

                                                <?php 
                                                    // Only display real descriptions; filter out empty or boilerplate descriptions
                                                    $isSampleDesc = empty($dishDesc) || 
                                                                    stripos($dishDesc, 'Orah House signature') !== false || 
                                                                    stripos($dishDesc, 'slow-fermented artisanal craft') !== false ||
                                                                    stripos($dishDesc, 'specialty herbs and house infusions') !== false ||
                                                                    stripos($dishDesc, 'House signature prepared with') !== false;
                                                ?>
                                                <?php if (!empty($dishDesc) && !$isSampleDesc): ?>
                                                    <p class="split-dish-desc"><?= htmlspecialchars($dishDesc) ?></p>
                                                <?php endif; ?>

                                                <!-- Dietary Badge -->
                                                <div class="split-diet-row">
                                                    <span class="diet-dot <?= $isVeg ? 'veg' : 'non-veg' ?>">●</span>
                                                    <span><?= $isVeg ? 'VEGETARIAN' : 'NON-VEG' ?></span>
                                                </div>

                                                <?php if (!empty($customFields)): ?>
                                                    <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:8px;">
                                                        <?php foreach ($customFields as $cf): ?>
                                                            <?php if (!empty($cf['name']) && !empty($cf['value'])): ?>
                                                                <span style="font-size:0.68rem; background:rgba(104,20,24,0.06); color:#681418; padding:3px 8px; border-radius:12px; font-weight:700; border:1px solid rgba(104,20,24,0.1);">
                                                                    <?= htmlspecialchars($cf['name']) ?>: <?= htmlspecialchars($cf['value']) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Bottom Action Button -->
                                            <div class="split-action-row">
                                                <button type="button" class="split-view-btn"
                                                        data-name="<?= htmlspecialchars($dishName) ?>"
                                                        data-price="<?= htmlspecialchars($dishPrice) ?>"
                                                        data-desc="<?= htmlspecialchars($dishDesc ?: ($particular . ' prepared with slow-fermented artisanal craft.')) ?>"
                                                        data-img="<?= htmlspecialchars($dishImg) ?>"
                                                        data-badge="<?= htmlspecialchars($displayName) ?>"
                                                        data-veg="<?= $isVeg ? '1' : '0' ?>"
                                                        data-custom='<?= htmlspecialchars(json_encode($customFields), ENT_QUOTES, 'UTF-8') ?>'>
                                                    <span>View Dish</span>
                                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php $sIndex++; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Ambient Bottom Gradient Scrim (Fades content gracefully behind dock) -->
            <div class="dock-backdrop-scrim" aria-hidden="true"></div>

            <!-- Floating Slide Navigation Dock matching reference mockup -->
            <aside class="floating-slide-dock" id="floatingSlideDock" aria-label="Menu category slide dock">
                <!-- Previous Section -->
                <button type="button" class="dock-nav-btn prev" id="dockPrevBtn" aria-label="Previous section">
                    <div class="dock-arrow-circle">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
                    </div>
                    <div class="dock-text-col">
                        <span class="dock-action-label">Previous</span>
                        <span class="dock-target-name" id="dockPrevLabel">...</span>
                    </div>
                </button>

                <!-- Center Explore Menu Button -->
                <div class="dock-center-wrap">
                    <button type="button" class="dock-explore-circle" id="dockExploreBtn" aria-label="Explore Menu Overview">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </button>
                    <span class="dock-explore-label">Explore Menu</span>
                </div>

                <!-- Next Section -->
                <button type="button" class="dock-nav-btn next" id="dockNextBtn" aria-label="Next section">
                    <div class="dock-text-col text-right">
                        <span class="dock-action-label">Next</span>
                        <span class="dock-target-name" id="dockNextLabel">...</span>
                    </div>
                    <div class="dock-arrow-circle">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
                    </div>
                </button>
            </aside>
        </section>

    </div>

    <!-- Dish Quick-View Modal -->
    <div class="dish-modal-backdrop" id="dishModalBackdrop" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="dish-modal-dialog">
            <button type="button" class="dish-modal-close-btn" id="dishModalCloseBtn" aria-label="Close dialog">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
            <div class="dish-modal-media">
                <img src="" alt="" id="modalDishImg" class="dish-modal-img">
                <span class="dish-modal-badge" id="modalDishBadge"></span>
            </div>
            <div class="dish-modal-content">
                <div class="dish-modal-header">
                    <div>
                        <h3 class="dish-modal-title" id="modalDishTitle"></h3>
                        <div style="font-size: 0.72rem; color: #8a7153; font-weight: 700; text-transform: uppercase; margin-top: 3px;" id="modalDishDiet"></div>
                    </div>
                    <div class="dish-modal-price" id="modalDishPrice"></div>
                </div>
                <p class="dish-modal-desc" id="modalDishDesc"></p>
                <div id="modalCustomFieldsContainer" style="display:flex; gap:8px; flex-wrap:wrap; margin-top:14px;"></div>

                <div class="dish-modal-actions" style="margin-top: 18px; padding-top: 14px; border-top: 1px solid rgba(104,20,24,0.08); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="modal-add-to-slip-btn" id="modalAddToSlipBtn">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                        <span id="modalSlipBtnText">Add to Dine-In List</span>
                    </button>
                    <button type="button" class="modal-view-slip-link" id="modalViewSlipLink" style="font-size: 0.8rem; font-weight: 700; color: #681418; background: none; border: none; cursor: pointer; text-decoration: underline; padding: 4px 6px;">
                        View List (<span class="slip-badge-count-text">0</span>) ↗
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Dine-In List Trigger Pill -->
    <div class="floating-dine-in-slip" id="floatingDineInSlip" role="region" aria-label="Dine-in saved dishes bar">
        <button type="button" class="dine-in-slip-pill" id="openFloatingSlipBtn" aria-label="Open Dine-In List">
            <div class="slip-pill-left">
                <span class="slip-pill-icon">📋</span>
                <div class="slip-pill-text-wrap">
                    <span class="slip-pill-title">My Dine-In List</span>
                    <span class="slip-pill-sub" id="slipPillMeta">0 items added</span>
                </div>
            </div>
            <div class="slip-pill-right">
                <span class="slip-pill-cta">View List (<span class="slip-badge-count-text">0</span>) ↗</span>
            </div>
        </button>
    </div>

    <!-- Dine-In Saved Dishes Modal / Drawer -->
    <div class="slip-modal-backdrop" id="dineInSlipModal" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="slip-modal-dialog">
            <!-- Modal Header -->
            <div class="slip-modal-header">
                <div class="slip-header-brand">
                    <div class="slip-header-eyebrow">SAVED DISHES</div>
                    <h2 class="slip-header-title">My Dine-In List</h2>
                </div>
                <div class="slip-header-actions">
                    <button type="button" class="slip-modal-close" id="closeSlipModalBtn" aria-label="Close Dine-In List">&times;</button>
                </div>
            </div>

            <!-- Top of List: Total Dishes -->
            <div class="slip-list-top-bar" id="slipTopDishBar">
                <span class="slip-total-dishes-label">Total Dishes: <strong id="slipTopDishCount">0</strong></span>
            </div>

            <!-- Items Scroll Area -->
            <div class="slip-items-scroll-area" id="slipItemsContainer">
                <!-- Dynamically populated by Dine-In List engine -->
            </div>

            <!-- Empty State -->
            <div class="slip-empty-state" id="slipEmptyState" style="display:none;">
                <div class="slip-empty-icon">🤍</div>
                <h3>Your Dine-In List is empty</h3>
                <p>Tap the heart icon (<svg viewBox="0 0 24 24" width="13" height="13" fill="#681418" stroke="#681418" style="vertical-align:middle;display:inline-block;"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>) on any dish while browsing to add it here. When your waiter arrives, just show this list!</p>
                <div class="slip-expiry-plain-note" style="margin-top:14px;">This list will be automatically gone after 3 hours (<span class="slip-expiry-timer-digits">03:00:00</span>)</div>
            </div>

            <!-- Slip Footer (NO PRICING • MINIMAL & CLEAN) -->
            <div class="slip-modal-footer" id="slipModalFooter">
                <!-- 3-Hour Auto-Expiry Notice (Out of box, small, without image, with time hh:mm:ss) -->
                <div class="slip-expiry-plain-note">
                    This list will be automatically gone after 3 hours (<span id="slipExpiryTimerBadge" class="slip-expiry-timer-digits">03:00:00</span>)
                </div>

                <div class="slip-footer-btns">
                    <button type="button" class="btn-clear-slip" id="slipClearAllBtn">Clear List</button>
                    <button type="button" class="btn-done-ordering" id="slipDoneOrderingBtn">Done</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Toast Notification -->
    <div class="slip-toast-notice" id="slipToastNotice" role="status" aria-live="polite"></div>

    <!-- Interactive Navigation & Touch Swipe Engine -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const overviewView = document.getElementById('sectionsOverviewView');
            const detailView = document.getElementById('detailMenuView');
            const backBtn = document.getElementById('backToSectionsBtn');
            const sectionCards = document.querySelectorAll('.section-card');
            const slider = document.getElementById('swipeDeckSlider');
            const viewport = document.getElementById('swipeDeckViewport');
            const dockPrevBtn = document.getElementById('dockPrevBtn');
            const dockNextBtn = document.getElementById('dockNextBtn');
            const dockExploreBtn = document.getElementById('dockExploreBtn');
            const dockPrevLabel = document.getElementById('dockPrevLabel');
            const dockNextLabel = document.getElementById('dockNextLabel');

            const sectionPanes = Array.from(document.querySelectorAll('.section-slide-pane'));
            const totalSections = sectionPanes.length;
            const sectionKeys = sectionPanes.map(p => p.getAttribute('data-section-key'));
            let currentSectionIndex = 0;
            let isAnimating = false;
            let startTime = 0;

            // Modal elements
            const modalBackdrop = document.getElementById('dishModalBackdrop');
            const modalCloseBtn = document.getElementById('dishModalCloseBtn');
            const modalImg = document.getElementById('modalDishImg');
            const modalBadge = document.getElementById('modalDishBadge');
            const modalTitle = document.getElementById('modalDishTitle');
            const modalPrice = document.getElementById('modalDishPrice');
            const modalDesc = document.getElementById('modalDishDesc');
            const modalDiet = document.getElementById('modalDishDiet');
            const modalCfContainer = document.getElementById('modalCustomFieldsContainer');

            const initialRouteSection = <?= json_encode($initialSectionKey) ?>;
            const baseUrl = <?= json_encode($baseUrl) ?>;

            // Get display name for labels
            function getCleanName(key) {
                if (!key) return '';
                return (key === 'Pasta') ? 'Pastas' : key;
            }

            // Canonical slug mapping (e.g. Pizzas -> pizza, Flat Breads -> flat-breads)
            function getCanonicalSlug(catName) {
                if (!catName) return '';
                const cleaned = catName.toLowerCase().trim();
                if (cleaned === 'pizzas' || cleaned === 'pizza') return 'pizza';
                if (cleaned === 'flat breads' || cleaned === 'flatbreads') return 'flat-breads';
                if (cleaned === 'pasta' || cleaned === 'pastas') return 'pasta';
                if (cleaned === 'desserts' || cleaned === 'dessert') return 'desserts';
                if (cleaned === 'appetizers' || cleaned === 'appetizer') return 'appetizers';
                if (cleaned === 'beverages' || cleaned === 'beverage') return 'beverages';
                return cleaned.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            }

            // Match route string (path or hash) to existing section key
            function matchSectionFromRoute(routeStr) {
                if (!routeStr) return null;
                const clean = routeStr.replace(/^#/, '').replace(/^\/?menu\.php\/?/, '').replace(/^\//, '').toLowerCase().trim();
                if (!clean) return null;

                for (const key of sectionKeys) {
                    const canonical = getCanonicalSlug(key);
                    const rawSlug = key.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                    if (clean === canonical || 
                        clean === rawSlug ||
                        clean.replace(/s$/, '') === canonical.replace(/s$/, '') ||
                        clean.replace(/s$/, '') === rawSlug.replace(/s$/, '') ||
                        clean.replace(/-/g, '') === canonical.replace(/-/g, '')) {
                        return key;
                    }
                }
                return null;
            }

            // Dynamically adjust viewport height to fit active section cleanly
            function updateViewportHeight(targetIdx = currentSectionIndex) {
                const pane = sectionPanes[targetIdx];
                if (pane && viewport) {
                    const h = Math.max(pane.scrollHeight || 0, pane.offsetHeight || 0);
                    if (h > 0) {
                        viewport.style.height = h + 'px';
                    }
                }
            }

            // Continuous height observer for dynamic font/image rendering across all browsers
            if (window.ResizeObserver) {
                const paneObserver = new ResizeObserver(() => {
                    updateViewportHeight(currentSectionIndex);
                });
                sectionPanes.forEach(pane => paneObserver.observe(pane));
            }

            // Hook into image load events to adjust height immediately as dish photos finish loading
            viewport?.querySelectorAll('img').forEach(img => {
                if (!img.complete) {
                    img.addEventListener('load', () => updateViewportHeight(currentSectionIndex), { once: true });
                }
            });

            // Update dock previous and next labels with soft cross-fade
            function updateDockLabels() {
                const prevIdx = (currentSectionIndex - 1 + totalSections) % totalSections;
                const nextIdx = (currentSectionIndex + 1) % totalSections;

                if (dockPrevLabel && dockNextLabel) {
                    dockPrevLabel.style.opacity = '0';
                    dockNextLabel.style.opacity = '0';
                    dockPrevLabel.style.transform = 'translateY(2px)';
                    dockNextLabel.style.transform = 'translateY(2px)';

                    setTimeout(() => {
                        dockPrevLabel.textContent = getCleanName(sectionKeys[prevIdx]);
                        dockNextLabel.textContent = getCleanName(sectionKeys[nextIdx]);
                        dockPrevLabel.style.opacity = '1';
                        dockNextLabel.style.opacity = '1';
                        dockPrevLabel.style.transform = 'translateY(0)';
                        dockNextLabel.style.transform = 'translateY(0)';
                    }, 140);
                }
            }

            // Quick Category Nav Pills Synchronization
            function updateQuickNavPills(activeIdx) {
                const pills = document.querySelectorAll('.quick-cat-pill');
                pills.forEach((p, idx) => {
                    if (idx === activeIdx) {
                        p.classList.add('active');
                        p.setAttribute('aria-selected', 'true');
                        // Auto scroll active pill into comfortable view
                        const track = document.getElementById('quickCategoryTrack');
                        if (track) {
                            const trackWidth = track.offsetWidth;
                            const pillLeft = p.offsetLeft;
                            const pillWidth = p.offsetWidth;
                            const scrollPos = pillLeft - (trackWidth / 2) + (pillWidth / 2);
                            track.scrollTo({ left: scrollPos, behavior: 'smooth' });
                        }
                    } else {
                        p.classList.remove('active');
                        p.removeAttribute('aria-selected');
                    }
                });
            }

            // Click listener for Quick Category Nav Pills
            document.querySelectorAll('.quick-cat-pill').forEach(pill => {
                pill.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetIdx = parseInt(pill.getAttribute('data-section-index'), 10);
                    if (!isNaN(targetIdx)) {
                        goToSection(targetIdx, true, false, true);
                    }
                });
            });

            // Slide to a specific section with silky ease-out
            function goToSection(index, animated = true, allowScroll = false, updateUrl = true) {
                if (isAnimating && animated) return;
                if (index < 0) index = 0;
                if (index >= totalSections) index = totalSections - 1;
                currentSectionIndex = index;

                if (animated) {
                    isAnimating = true;
                    setTimeout(() => { isAnimating = false; }, 430);
                }

                // Update active state on panes
                sectionPanes.forEach((pane, idx) => {
                    if (idx === currentSectionIndex) {
                        pane.classList.add('active');
                    } else {
                        pane.classList.remove('active');
                    }
                });

                // Hardware-accelerated 3D transition with Apple-fluid curve
                slider.style.transition = animated ? 'transform 0.42s cubic-bezier(0.22, 1, 0.36, 1)' : 'none';
                slider.style.transform = `translate3d(-${currentSectionIndex * 100}%, 0, 0)`;

                // Concurrently morph viewport height
                updateViewportHeight(currentSectionIndex);

                // Update dock labels
                updateDockLabels();

                // Update Quick Category Nav Pills
                updateQuickNavPills(currentSectionIndex);

                // Update URL to /menu.php/{category}
                if (updateUrl) {
                    const activeKey = sectionKeys[currentSectionIndex];
                    if (activeKey) {
                        const slug = getCanonicalSlug(activeKey);
                        const targetPath = baseUrl + 'menu.php/' + slug;
                        if (window.location.pathname !== targetPath) {
                            history.replaceState({ section: activeKey }, '', targetPath);
                        }
                    }
                }

                // Only smoothly scroll if user tapped dock while far down the page
                if (allowScroll && window.scrollY > 250 && animated) {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }

            // Show Detailed Menu
            function openDetailView(sectionKey, updateUrl = true) {
                let targetIndex = sectionKeys.findIndex(k => k === sectionKey);
                if (targetIndex === -1) targetIndex = 0;

                overviewView.style.display = 'none';
                detailView.style.display = 'block';
                document.body.classList.add('in-detailed-view');
                goToSection(targetIndex, false, false, updateUrl);
                updateViewportHeight(targetIndex);

                requestAnimationFrame(() => {
                    detailView.style.opacity = '1';
                });

                window.scrollTo({ top: 0, behavior: 'instant' });
            }

            // Return to Overview (First Page)
            function showOverview(updateUrl = true) {
                document.body.classList.remove('in-detailed-view');
                detailView.style.opacity = '0';
                setTimeout(() => {
                    detailView.style.display = 'none';
                    overviewView.style.display = 'block';
                    overviewView.style.opacity = '1';
                }, 200);

                window.scrollTo({ top: 0, behavior: 'smooth' });
                if (updateUrl) {
                    history.pushState(null, '', baseUrl + 'menu.php');
                }
            }

            backBtn?.addEventListener('click', () => showOverview(true));
            dockExploreBtn?.addEventListener('click', () => showOverview(true));

            // Dock Prev / Next Click Handlers
            dockPrevBtn?.addEventListener('click', () => {
                const targetIdx = (currentSectionIndex - 1 + totalSections) % totalSections;
                goToSection(targetIdx, true, true);
            });

            dockNextBtn?.addEventListener('click', () => {
                const targetIdx = (currentSectionIndex + 1) % totalSections;
                goToSection(targetIdx, true, true);
            });

            // Clicking any section card on Page 1 opens Page 2
            sectionCards.forEach(card => {
                card.addEventListener('click', () => {
                    const target = card.getAttribute('data-section-target');
                    openDetailView(target);
                });
            });

            // Re-calculate height on window resize
            window.addEventListener('resize', () => {
                updateViewportHeight(currentSectionIndex);
            });

            // =========================================================================
            // TOUCH / SWIPE GESTURE ENGINE (SLIDE FEATURE WITH VELOCITY)
            // =========================================================================
            let startX = 0;
            let startY = 0;
            let currentX = 0;
            let currentY = 0;
            let isSwiping = false;
            let isHorizontalSwipe = null;

            function handleTouchStart(e) {
                if (isAnimating) return;
                const touch = e.touches ? e.touches[0] : e;
                startX = touch.clientX;
                startY = touch.clientY;
                currentX = startX;
                currentY = startY;
                isSwiping = true;
                isHorizontalSwipe = null;
                startTime = Date.now();
            }

            function handleTouchMove(e) {
                if (!isSwiping) return;
                const touch = e.touches ? e.touches[0] : e;
                currentX = touch.clientX;
                currentY = touch.clientY;

                const diffX = currentX - startX;
                const diffY = currentY - startY;

                if (isHorizontalSwipe === null) {
                    if (Math.abs(diffX) > 8 || Math.abs(diffY) > 8) {
                        isHorizontalSwipe = Math.abs(diffX) > Math.abs(diffY);
                    }
                }

                if (isHorizontalSwipe) {
                    if (e.cancelable) e.preventDefault();
                    slider.classList.add('is-dragging');
                    let dragOffset = diffX;
                    if ((currentSectionIndex === 0 && diffX > 0) || (currentSectionIndex === totalSections - 1 && diffX < 0)) {
                        dragOffset = diffX * 0.28; // soft rubber band at edges
                    }
                    const basePercent = -currentSectionIndex * 100;
                    const pixelWidth = viewport.offsetWidth || 1;
                    const percentOffset = (dragOffset / pixelWidth) * 100;

                    slider.style.transition = 'none';
                    slider.style.transform = `translate3d(${basePercent + percentOffset}%, 0, 0)`;
                }
            }

            function handleTouchEnd() {
                if (!isSwiping) return;
                isSwiping = false;
                slider.classList.remove('is-dragging');

                if (isHorizontalSwipe) {
                    const diffX = currentX - startX;
                    const elapsed = Math.max(1, Date.now() - startTime);
                    const velocity = Math.abs(diffX) / elapsed; // px per millisecond

                    // Quick flick (> 0.20 px/ms) or drag past 40px
                    const isFlick = velocity > 0.20 && Math.abs(diffX) > 20;
                    const isPastThreshold = Math.abs(diffX) > 40;

                    if ((isFlick || isPastThreshold) && diffX < 0) {
                        // Swipe left -> Next section
                        const nextIdx = (currentSectionIndex + 1) % totalSections;
                        goToSection(nextIdx, true, false);
                    } else if ((isFlick || isPastThreshold) && diffX > 0) {
                        // Swipe right -> Prev section
                        const prevIdx = (currentSectionIndex - 1 + totalSections) % totalSections;
                        goToSection(prevIdx, true, false);
                    } else {
                        // Snap back smoothly
                        goToSection(currentSectionIndex, true, false);
                    }
                }
                isHorizontalSwipe = null;
            }

            // Touch Listeners for Mobile Swipe
            viewport.addEventListener('touchstart', handleTouchStart, { passive: true });
            viewport.addEventListener('touchmove', handleTouchMove, { passive: false });
            viewport.addEventListener('touchend', handleTouchEnd);
            viewport.addEventListener('touchcancel', handleTouchEnd);

            // Keyboard Navigation & Escape Handler Support
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (slipModal && slipModal.classList.contains('active')) {
                        closeSlipModal();
                        return;
                    }
                    if (modalBackdrop && modalBackdrop.classList.contains('active')) {
                        closeModal();
                        return;
                    }
                }
                if (detailView.style.display !== 'none' && detailView.style.display !== '') {
                    if (e.key === 'ArrowRight') {
                        goToSection((currentSectionIndex + 1) % totalSections, true);
                    } else if (e.key === 'ArrowLeft') {
                        goToSection((currentSectionIndex - 1 + totalSections) % totalSections, true);
                    }
                }
            });

            // =========================================================================
            // DINE-IN SAVED DISHES LIST ENGINE (NO PRICING • 3-HOUR AUTO-EXPIRY)
            // =========================================================================
            const SLIP_STORAGE_KEY = 'orah_table_slip_v2';
            const SLIP_EXPIRY_KEY = 'orah_table_slip_time';
            const THREE_HOURS_MS = 3 * 60 * 60 * 1000; // 3 hours in milliseconds

            const floatingSlip = document.getElementById('floatingDineInSlip');
            const openFloatingSlipBtn = document.getElementById('openFloatingSlipBtn');
            const overviewSlipBtn = document.getElementById('overviewSlipBtn');
            const detailSlipTriggerBtn = document.getElementById('detailSlipTriggerBtn');
            const slipModal = document.getElementById('dineInSlipModal');
            const closeSlipModalBtn = document.getElementById('closeSlipModalBtn');
            const slipDoneOrderingBtn = document.getElementById('slipDoneOrderingBtn');
            const slipItemsContainer = document.getElementById('slipItemsContainer');
            const slipEmptyState = document.getElementById('slipEmptyState');
            const slipModalFooter = document.getElementById('slipModalFooter');
            const slipClearAllBtn = document.getElementById('slipClearAllBtn');
            const slipPillMeta = document.getElementById('slipPillMeta');
            const slipTopDishCount = document.getElementById('slipTopDishCount');
            const slipExpiryTimerBadge = document.getElementById('slipExpiryTimerBadge');
            const toastNotice = document.getElementById('slipToastNotice');
            const modalAddToSlipBtn = document.getElementById('modalAddToSlipBtn');
            const modalSlipBtnText = document.getElementById('modalSlipBtnText');
            const modalViewSlipLink = document.getElementById('modalViewSlipLink');

            let currentModalDishData = null;
            let toastTimer = null;

            function escapeHtml(str) {
                if (!str) return '';
                return String(str).replace(/[&<>"']/g, m => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                }[m]));
            }

            function showToast(message) {
                if (!toastNotice) return;

                let iconSvg = '';
                let textHtml = '';
                
                // Parse "Added "Dish Name" to Dine-In list" or "Removed "Dish Name" from Dine-In list"
                const match = message.match(/^(Added|Removed)\s+"([^"]+)"\s+(to|from)\s+(?:Dine-In\s+)?list/i);
                if (match) {
                    const isAdded = match[1].toLowerCase() === 'added';
                    const dishName = match[2];
                    iconSvg = isAdded
                        ? `<div class="slip-toast-icon-wrap is-added"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>`
                        : `<div class="slip-toast-icon-wrap is-removed"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"><line x1="6" y1="12" x2="18" y2="12"/></svg></div>`;
                    
                    textHtml = `
                        <div class="slip-toast-text-wrap">
                            <span class="slip-toast-dish" title="${escapeHtml(dishName)}">${escapeHtml(dishName)}</span>
                            <span class="slip-toast-status">${isAdded ? 'added' : 'removed'}</span>
                        </div>
                    `;
                } else {
                    const isClear = message.toLowerCase().includes('clear') || message.toLowerCase().includes('removed');
                    iconSvg = `<div class="slip-toast-icon-wrap ${isClear ? 'is-removed' : 'is-added'}"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>`;
                    textHtml = `
                        <div class="slip-toast-text-wrap">
                            <span class="slip-toast-dish">${escapeHtml(message)}</span>
                        </div>
                    `;
                }

                toastNotice.innerHTML = `
                    ${iconSvg}
                    ${textHtml}
                    <div class="slip-toast-cta" role="button" aria-label="View Dine-In list">
                        <span>View</span>
                        <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17l10-10M17 17V7H7"/></svg>
                    </div>
                `;

                toastNotice.classList.add('visible');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => {
                    toastNotice.classList.remove('visible');
                }, 3200);
            }

            // Click toast notice to open list immediately
            toastNotice?.addEventListener('click', () => {
                toastNotice.classList.remove('visible');
                openSlipModal();
            });

            // Pause toast auto-dismiss while hovered
            toastNotice?.addEventListener('mouseenter', () => {
                clearTimeout(toastTimer);
            });
            toastNotice?.addEventListener('mouseleave', () => {
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => {
                    toastNotice.classList.remove('visible');
                }, 2000);
            });

            function getExpiryRemainingText() {
                const timeRaw = localStorage.getItem(SLIP_EXPIRY_KEY);
                if (!timeRaw) return '03:00:00';
                const createdTime = parseInt(timeRaw, 10);
                if (isNaN(createdTime)) return '03:00:00';
                const remaining = THREE_HOURS_MS - (Date.now() - createdTime);
                if (remaining <= 0) return '00:00:00';
                const hrs = Math.floor(remaining / (60 * 60 * 1000));
                const mins = Math.floor((remaining % (60 * 60 * 1000)) / (60 * 1000));
                const secs = Math.floor((remaining % (60 * 1000)) / 1000);
                return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            }

            // Live countdown ticker for 3-hour expiry note (hh:mm:ss)
            setInterval(() => {
                if (slipExpiryTimerBadge) {
                    slipExpiryTimerBadge.textContent = getExpiryRemainingText();
                }
            }, 1000);

            function getSlipItems() {
                try {
                    // Check if 3 hours have elapsed since creation
                    const timeRaw = localStorage.getItem(SLIP_EXPIRY_KEY);
                    if (timeRaw) {
                        const createdTime = parseInt(timeRaw, 10);
                        if (!isNaN(createdTime) && (Date.now() - createdTime > THREE_HOURS_MS)) {
                            // Automatically expired! Clear list
                            localStorage.removeItem(SLIP_STORAGE_KEY);
                            localStorage.removeItem(SLIP_EXPIRY_KEY);
                            return [];
                        }
                    }

                    const raw = localStorage.getItem(SLIP_STORAGE_KEY);
                    if (raw) {
                        const parsed = JSON.parse(raw);
                        if (Array.isArray(parsed)) return parsed;
                    }
                } catch(e) {}
                return [];
            }

            function saveSlipItems(items) {
                try {
                    if (items.length === 0) {
                        localStorage.removeItem(SLIP_STORAGE_KEY);
                        localStorage.removeItem(SLIP_EXPIRY_KEY);
                    } else {
                        if (!localStorage.getItem(SLIP_EXPIRY_KEY)) {
                            localStorage.setItem(SLIP_EXPIRY_KEY, Date.now().toString());
                        }
                        localStorage.setItem(SLIP_STORAGE_KEY, JSON.stringify(items));
                    }
                } catch(e) {}
                syncSlipUI();
            }

            function isDishInSlip(dishId, dishName) {
                const items = getSlipItems();
                return items.some(item => (dishId && item.id === dishId) || (dishName && item.name.toLowerCase() === dishName.toLowerCase()));
            }

            function toggleDishInSlip(dishData, triggerBtn = null) {
                if (!dishData || !dishData.name) return;
                const items = getSlipItems();
                const existingIdx = items.findIndex(it => (dishData.id && it.id === dishData.id) || (it.name.toLowerCase() === dishData.name.toLowerCase()));

                if (existingIdx > -1) {
                    const removed = items.splice(existingIdx, 1)[0];
                    saveSlipItems(items);
                    showToast(`Removed "${removed.name}" from Dine-In list`);
                } else {
                    const newItem = {
                        id: dishData.id || ('dish_' + Math.random().toString(36).substr(2, 9)),
                        name: dishData.name,
                        img: dishData.img || '',
                        veg: (dishData.veg === '1' || dishData.veg === true || dishData.veg === 1),
                        cat: dishData.cat || dishData.badge || '',
                        qty: 1,
                        note: ''
                    };
                    items.push(newItem);
                    saveSlipItems(items);
                    showToast(`Added "${newItem.name}" to Dine-In list`);
                }

                if (triggerBtn) {
                    triggerBtn.classList.add('animate-pop');
                    setTimeout(() => triggerBtn.classList.remove('animate-pop'), 400);
                }
            }

            function updateDishQuantity(dishId, delta) {
                const items = getSlipItems();
                const item = items.find(it => it.id === dishId);
                if (!item) return;

                item.qty = (item.qty || 1) + delta;
                if (item.qty <= 0) {
                    const filtered = items.filter(it => it.id !== dishId);
                    saveSlipItems(filtered);
                    showToast(`Removed "${item.name}" from Dine-In list`);
                } else {
                    saveSlipItems(items);
                }
            }

            function updateDishNote(dishId, noteText) {
                const items = getSlipItems();
                const item = items.find(it => it.id === dishId);
                if (item) {
                    item.note = noteText;
                    try {
                        localStorage.setItem(SLIP_STORAGE_KEY, JSON.stringify(items));
                    } catch(e) {}
                }
            }

            function removeDishFromSlip(dishId) {
                const items = getSlipItems();
                const item = items.find(it => it.id === dishId);
                const filtered = items.filter(it => it.id !== dishId);
                saveSlipItems(filtered);
                if (item) showToast(`Removed "${item.name}" from list`);
            }

            function clearAllSlipItems() {
                if (confirm('Clear all items from your Dine-In Table List?')) {
                    saveSlipItems([]);
                    showToast('Dine-In list cleared');
                }
            }

            function syncSlipUI() {
                const items = getSlipItems();
                const totalItemCount = items.reduce((sum, it) => sum + (it.qty || 1), 0);

                // 1. Update Badges
                const badgeElements = document.querySelectorAll('.slip-badge-count');
                badgeElements.forEach(badge => {
                    badge.textContent = totalItemCount;
                    badge.style.display = totalItemCount > 0 ? 'inline-flex' : 'none';
                });

                const badgeTextElements = document.querySelectorAll('.slip-badge-count-text');
                badgeTextElements.forEach(el => {
                    el.textContent = totalItemCount;
                });

                // 2. Update Floating Bar
                if (floatingSlip) {
                    if (totalItemCount > 0) {
                        floatingSlip.classList.add('visible');
                    } else {
                        floatingSlip.classList.remove('visible');
                    }
                }
                if (slipPillMeta) {
                    slipPillMeta.textContent = `${totalItemCount} ${totalItemCount === 1 ? 'item' : 'items'} added`;
                }

                // 3. Update Dish Card Heart Buttons
                document.querySelectorAll('.split-fav-btn').forEach(btn => {
                    const dishId = btn.getAttribute('data-dish-id');
                    const dishName = btn.getAttribute('data-dish-name');
                    if (isDishInSlip(dishId, dishName)) {
                        btn.classList.add('active');
                        btn.setAttribute('aria-pressed', 'true');
                    } else {
                        btn.classList.remove('active');
                        btn.setAttribute('aria-pressed', 'false');
                    }
                });

                // 4. Update Modal Button
                if (currentModalDishData && modalAddToSlipBtn && modalSlipBtnText) {
                    if (isDishInSlip(currentModalDishData.id, currentModalDishData.name)) {
                        modalAddToSlipBtn.classList.add('in-slip');
                        modalSlipBtnText.textContent = '✓ In Dine-In List (Tap to remove)';
                    } else {
                        modalAddToSlipBtn.classList.remove('in-slip');
                        modalSlipBtnText.textContent = 'Add to Dine-In List';
                    }
                }

                // 5. Update Expiry timer badge
                if (slipExpiryTimerBadge) {
                    slipExpiryTimerBadge.textContent = getExpiryRemainingText();
                }

                // 6. Update Top Dish Count
                if (slipTopDishCount) {
                    slipTopDishCount.textContent = totalItemCount;
                }

                // 7. Update Modal Content if open
                if (slipModal && (slipModal.classList.contains('active') || slipModal.classList.contains('open'))) {
                    renderSlipModalContent();
                }
            }

            function renderSlipModalContent() {
                const items = getSlipItems();
                const totalItemCount = items.reduce((sum, it) => sum + (it.qty || 1), 0);

                if (slipTopDishCount) {
                    slipTopDishCount.textContent = totalItemCount;
                }
                if (slipExpiryTimerBadge) {
                    slipExpiryTimerBadge.textContent = getExpiryRemainingText();
                }

                if (items.length === 0) {
                    if (slipEmptyState) slipEmptyState.style.display = 'block';
                    if (slipItemsContainer) slipItemsContainer.style.display = 'none';
                    if (slipModalFooter) slipModalFooter.style.display = 'none';
                    return;
                }

                if (slipEmptyState) slipEmptyState.style.display = 'none';
                if (slipItemsContainer) slipItemsContainer.style.display = 'flex';
                if (slipModalFooter) slipModalFooter.style.display = 'block';

                if (!slipItemsContainer) return;
                slipItemsContainer.innerHTML = '';

                items.forEach((item) => {
                    const row = document.createElement('div');
                    row.className = 'slip-dish-card';
                    row.setAttribute('data-dish-id', item.id);

                    const dietLabel = item.veg ? '● VEG' : '● NON-VEG';
                    const dietColor = item.veg ? '#2e7d32' : '#c62828';
                    const imgUrl = item.img || 'assets/placeholder.jpg';

                    // NO PRICING: Only display thumbnail, name, dietary, category, quantity stepper, and kitchen note
                    row.innerHTML = `
                        <div class="slip-dish-main-row">
                            <img src="${escapeHtml(imgUrl)}" alt="${escapeHtml(item.name)}" class="slip-dish-thumb" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=100&q=70'">
                            <div class="slip-dish-info">
                                <div class="slip-dish-meta">
                                    <span style="color: ${dietColor}; font-weight:700;">${dietLabel}</span>
                                    ${item.cat ? `<span class="slip-item-cat">${escapeHtml(item.cat)}</span>` : ''}
                                </div>
                                <h4 class="slip-dish-name">${escapeHtml(item.name)}</h4>
                            </div>
                            <div class="slip-dish-controls">
                                <div class="slip-qty-stepper">
                                    <button type="button" class="slip-stepper-btn minus" data-id="${escapeHtml(item.id)}" aria-label="Decrease quantity">−</button>
                                    <span class="slip-stepper-val">${item.qty || 1}</span>
                                    <button type="button" class="slip-stepper-btn plus" data-id="${escapeHtml(item.id)}" aria-label="Increase quantity">+</button>
                                </div>
                                <button type="button" class="slip-item-del-btn" data-id="${escapeHtml(item.id)}" aria-label="Remove item">×</button>
                            </div>
                        </div>
                        <div class="slip-dish-note-wrap">
                            <input type="text" class="slip-dish-note-input" data-id="${escapeHtml(item.id)}" placeholder="✎ Special instructions (e.g. Less spicy, dressing on side...)" value="${escapeHtml(item.note || '')}" maxlength="120">
                        </div>
                    `;

                    slipItemsContainer.appendChild(row);
                });
            }

            // Stepper and Note Events in Slip Modal
            slipItemsContainer?.addEventListener('click', (e) => {
                const minusBtn = e.target.closest('.slip-stepper-btn.minus, .slip-qty-btn.minus');
                const plusBtn = e.target.closest('.slip-stepper-btn.plus, .slip-qty-btn.plus');
                const removeBtn = e.target.closest('.slip-item-del-btn, .slip-remove-btn');

                if (minusBtn) {
                    const id = minusBtn.getAttribute('data-id');
                    updateDishQuantity(id, -1);
                } else if (plusBtn) {
                    const id = plusBtn.getAttribute('data-id');
                    updateDishQuantity(id, 1);
                } else if (removeBtn) {
                    const id = removeBtn.getAttribute('data-id');
                    removeDishFromSlip(id);
                }
            });

            slipItemsContainer?.addEventListener('input', (e) => {
                if (e.target.classList.contains('slip-dish-note-input') || e.target.classList.contains('slip-item-note-input')) {
                    const id = e.target.getAttribute('data-id');
                    updateDishNote(id, e.target.value);
                }
            });

            // Clear All List
            slipClearAllBtn?.addEventListener('click', clearAllSlipItems);

            // Open & Close Slip Modal (Supports both .active and .open)
            function openSlipModal() {
                if (!slipModal) return;
                renderSlipModalContent();
                slipModal.classList.add('active', 'open');
                slipModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function closeSlipModal() {
                if (!slipModal) return;
                slipModal.classList.remove('active', 'open');
                slipModal.setAttribute('aria-hidden', 'true');
                if (!modalBackdrop || !modalBackdrop.classList.contains('active')) {
                    document.body.style.overflow = '';
                }
            }

            openFloatingSlipBtn?.addEventListener('click', openSlipModal);
            overviewSlipBtn?.addEventListener('click', openSlipModal);
            document.querySelectorAll('.editorial-slip-btn, .open-slip-btn').forEach(btn => {
                btn.addEventListener('click', openSlipModal);
            });
            closeSlipModalBtn?.addEventListener('click', closeSlipModal);
            slipDoneOrderingBtn?.addEventListener('click', closeSlipModal);
            modalViewSlipLink?.addEventListener('click', () => {
                closeModal();
                openSlipModal();
            });

            slipModal?.addEventListener('click', (e) => {
                if (e.target === slipModal) closeSlipModal();
            });

            // Heart Buttons Click Listeners
            document.querySelectorAll('.split-fav-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const dishData = {
                        id: btn.getAttribute('data-dish-id'),
                        name: btn.getAttribute('data-dish-name'),
                        img: btn.getAttribute('data-dish-img'),
                        veg: btn.getAttribute('data-dish-veg'),
                        cat: btn.getAttribute('data-dish-cat')
                    };
                    toggleDishInSlip(dishData, btn);
                });
            });

            // Modal Slip Button Click Listener
            modalAddToSlipBtn?.addEventListener('click', (e) => {
                e.stopPropagation();
                if (currentModalDishData) {
                    toggleDishInSlip(currentModalDishData, modalAddToSlipBtn);
                }
            });

            // =========================================================================
            // DISH MODAL QUICK VIEW
            // =========================================================================
            function openModal(data) {
                if (!modalBackdrop) return;
                currentModalDishData = data;

                modalImg.src = data.img;
                modalBadge.textContent = data.badge;
                modalTitle.textContent = data.name;
                modalPrice.textContent = data.price;
                modalDesc.textContent = data.desc;
                modalDiet.textContent = (data.veg === '1' || data.veg === 1 || data.veg === true) ? '● VEGETARIAN' : '● NON-VEG';
                modalDiet.style.color = (data.veg === '1' || data.veg === 1 || data.veg === true) ? '#2e7d32' : '#c62828';

                // Populate Dynamic Custom Extra Fields
                if (modalCfContainer) {
                    modalCfContainer.innerHTML = '';
                    if (Array.isArray(data.custom)) {
                        data.custom.forEach(cf => {
                            if (cf.name && cf.value) {
                                const pill = document.createElement('span');
                                pill.style.cssText = 'font-size:0.75rem; background:#FAF5EB; color:#681418; padding:5px 12px; border-radius:14px; font-weight:700; border:1px solid rgba(104,20,24,0.14);';
                                pill.textContent = `${cf.name}: ${cf.value}`;
                                modalCfContainer.appendChild(pill);
                            }
                        });
                    }
                }

                // Update Modal Slip button state
                if (modalAddToSlipBtn && modalSlipBtnText) {
                    if (isDishInSlip(data.id, data.name)) {
                        modalAddToSlipBtn.classList.add('in-slip');
                        modalSlipBtnText.textContent = '✓ In Table Slip (Tap to remove)';
                    } else {
                        modalAddToSlipBtn.classList.remove('in-slip');
                        modalSlipBtnText.textContent = 'Add to Dine-In Slip';
                    }
                }

                modalBackdrop.classList.add('active');
                modalBackdrop.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                if (!modalBackdrop) return;
                currentModalDishData = null;
                modalBackdrop.classList.remove('active');
                modalBackdrop.setAttribute('aria-hidden', 'true');
                if (!slipModal || !slipModal.classList.contains('active')) {
                    document.body.style.overflow = '';
                }
            }

            document.querySelectorAll('.split-view-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    let custom = [];
                    try {
                        const raw = btn.getAttribute('data-custom');
                        if (raw) custom = JSON.parse(raw);
                    } catch(err) {}

                    const card = btn.closest('.split-dish-card');
                    const favBtn = card?.querySelector('.split-fav-btn');

                    openModal({
                        id: favBtn?.getAttribute('data-dish-id') || ('dish_' + Math.random().toString(36).substr(2, 9)),
                        name: btn.getAttribute('data-name'),
                        price: btn.getAttribute('data-price'),
                        desc: btn.getAttribute('data-desc'),
                        img: btn.getAttribute('data-img'),
                        badge: btn.getAttribute('data-badge'),
                        veg: btn.getAttribute('data-veg'),
                        custom: custom
                    });
                });
            });

            modalCloseBtn?.addEventListener('click', closeModal);
            modalBackdrop?.addEventListener('click', (e) => {
                if (e.target === modalBackdrop) closeModal();
            });

            // =========================================================================
            // TOP EXPANDABLE SEARCH & SMART RECOMMENDATIONS ENGINE
            // =========================================================================
            const catalogDishes = <?= json_encode(array_values($catalog), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
            const searchInput = document.getElementById('menuMainSearchInput');
            const clearSearchBtn = document.getElementById('clearMenuSearchBtn');
            const resultsPanel = document.getElementById('searchLiveResultsPanel');
            const resultsGrid = document.getElementById('resultsScrollGrid');
            const resultsCountTitle = document.getElementById('resultsCountTitle');
            const closeResultsBtn = document.getElementById('closeResultsPanelBtn');
            const recomChips = document.querySelectorAll('.recom-chip');
            const detailSearchBtn = document.getElementById('detailSearchTriggerBtn');
            const toggleTopSearchBtn = document.getElementById('toggleTopSearchBtn');
            const closeTopSearchBtn = document.getElementById('closeTopSearchBtn');
            const topSearchDrawer = document.getElementById('topSearchDrawer');

            let activeFilter = null; // e.g. 'chef_special', 'featured', etc.

            function openSearchDrawer() {
                if (!topSearchDrawer) return;
                topSearchDrawer.style.display = 'block';
                toggleTopSearchBtn?.classList.add('active');
                toggleTopSearchBtn?.setAttribute('aria-expanded', 'true');
                setTimeout(() => {
                    searchInput?.focus();
                }, 50);
            }

            function closeSearchDrawer() {
                if (!topSearchDrawer) return;
                topSearchDrawer.style.display = 'none';
                toggleTopSearchBtn?.classList.remove('active');
                toggleTopSearchBtn?.setAttribute('aria-expanded', 'false');
            }

            function toggleSearchDrawer() {
                if (!topSearchDrawer) return;
                if (topSearchDrawer.style.display === 'none' || !topSearchDrawer.style.display) {
                    openSearchDrawer();
                } else {
                    closeSearchDrawer();
                }
            }

            toggleTopSearchBtn?.addEventListener('click', (e) => {
                e.preventDefault();
                toggleSearchDrawer();
            });

            closeTopSearchBtn?.addEventListener('click', (e) => {
                e.preventDefault();
                closeSearchDrawer();
            });

            // Close top drawer on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && topSearchDrawer && topSearchDrawer.style.display !== 'none') {
                    closeSearchDrawer();
                }
            });

            function performSearch() {
                const query = (searchInput?.value || '').trim().toLowerCase();
                
                if (!query && !activeFilter) {
                    if (resultsPanel) resultsPanel.style.display = 'none';
                    if (clearSearchBtn) clearSearchBtn.style.display = 'none';
                    return;
                }

                if (clearSearchBtn) clearSearchBtn.style.display = query ? 'block' : 'none';

                const matches = catalogDishes.filter(dish => {
                    const name = (dish.name || '').toLowerCase();
                    const desc = (dish.description || '').toLowerCase();
                    const badge = (dish.badge || '').toLowerCase();
                    const tagline = (dish.tagline || '').toLowerCase();
                    const particular = (dish.particular || '').toLowerCase();

                    // 1. Text Query Filter
                    let textMatches = true;
                    if (query) {
                        textMatches = name.includes(query) || 
                                      desc.includes(query) || 
                                      badge.includes(query) || 
                                      tagline.includes(query) || 
                                      particular.includes(query);
                    }

                    // 2. Active Recommendation Filter
                    let filterMatches = true;
                    if (activeFilter === 'chef_special') {
                        filterMatches = !!dish.is_chef_special;
                    } else if (activeFilter === 'featured') {
                        filterMatches = !!dish.featured;
                    } else if (activeFilter === 'truffle') {
                        filterMatches = name.includes('truffle') || desc.includes('truffle') || particular.includes('truffle');
                    } else if (activeFilter === 'sourdough') {
                        filterMatches = name.includes('sourdough') || desc.includes('sourdough') || badge.includes('flat') || desc.includes('flat bread');
                    } else if (activeFilter === 'veg') {
                        filterMatches = !!dish.is_veg;
                    } else if (activeFilter === 'pasta') {
                        filterMatches = badge === 'pasta' || name.includes('pasta') || name.includes('spaghetti');
                    } else if (activeFilter === 'dessert') {
                        filterMatches = badge === 'desserts' || name.includes('chocolate') || desc.includes('sweet') || desc.includes('dessert');
                    }

                    return textMatches && filterMatches;
                });

                renderSearchResults(matches, query);
            }

            function renderSearchResults(matches, query) {
                if (!resultsGrid || !resultsPanel) return;
                resultsGrid.innerHTML = '';

                let titleText = 'Recommended Dishes';
                if (query && activeFilter) {
                    titleText = `Found ${matches.length} dishes for "${query}" (${activeFilter.replace('_', ' ')})`;
                } else if (query) {
                    titleText = `Found ${matches.length} matching "${query}"`;
                } else if (activeFilter) {
                    const labels = {
                        chef_special: "Chef's Special Picks (With Paper Clip Tag)",
                        featured: "Signature House Creations",
                        truffle: "Truffle & Forest Mushroom Delights",
                        sourdough: "Hand-Stretched Sourdough Melts",
                        veg: "Pure Vegetarian Selections",
                        pasta: "House-Made Fresh Artisan Pastas",
                        dessert: "Sweet Endings & Molten Confections"
                    };
                    titleText = labels[activeFilter] || `Curated Selections (${matches.length})`;
                }

                if (resultsCountTitle) resultsCountTitle.textContent = `${titleText} (${matches.length})`;

                if (matches.length === 0) {
                    resultsGrid.innerHTML = `
                        <div style="grid-column: 1 / -1; padding: 32px 16px; text-align: center; color: #8c7365;">
                            <div style="font-weight: 600; font-size: 1rem; color: #500f12;">No dishes found matching your criteria</div>
                            <div style="font-size: 0.82rem; margin-top: 4px;">Try another search term or click one of our curated recommendations above.</div>
                        </div>
                    `;
                } else {
                    matches.forEach(d => {
                        const card = document.createElement('div');
                        card.className = 'search-result-card';
                        
                        let specialBadge = '';
                        if (d.is_chef_special) {
                            specialBadge = '<span class="result-special-pill">Chef\'s Special</span>';
                        }
                        const vegDot = d.is_veg ? '<span style="color:#2e7d32;font-size:0.75rem;">● Veg</span>' : '<span style="color:#c62828;font-size:0.75rem;">● Non-Veg</span>';

                        const imgUrl = (d.image && !d.image.startsWith('http')) ? d.image : (d.image || 'assets/placeholder.jpg');

                        card.innerHTML = `
                            <img src="${imgUrl}" alt="${d.name || ''}" class="result-thumb" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=120&q=80'">
                            <div class="result-meta">
                                <div class="result-title-row">
                                    <span class="result-name">${d.name || 'Dish'}</span>
                                    <span class="result-price">${d.price || ''}</span>
                                </div>
                                <div class="result-badge-row">
                                    <span class="result-cat-pill">${d.badge || 'General'}</span>
                                    ${specialBadge}
                                    ${vegDot}
                                </div>
                            </div>
                        `;

                        card.addEventListener('click', () => {
                            closeSearchDrawer();
                            
                            // Open Detailed View for this section
                            const sectionKey = d.badge;
                            if (sectionKey) {
                                openDetailView(sectionKey);
                                // Also open the Dish Modal for rich inspection
                                setTimeout(() => {
                                    openModal({
                                        name: d.name,
                                        price: d.price,
                                        desc: d.description || `${d.name} crafted with fine estate ingredients.`,
                                        img: d.image || 'uploads/artisanal_pizza.jpg',
                                        badge: d.badge,
                                        veg: d.is_veg ? '1' : '0',
                                        custom: d.custom_fields || []
                                    });
                                }, 300);
                            }
                        });

                        resultsGrid.appendChild(card);
                    });
                }

                resultsPanel.style.display = 'block';
            }

            searchInput?.addEventListener('input', performSearch);

            clearSearchBtn?.addEventListener('click', () => {
                if (searchInput) searchInput.value = '';
                activeFilter = null;
                recomChips.forEach(c => c.classList.remove('active'));
                if (resultsPanel) resultsPanel.style.display = 'none';
                if (clearSearchBtn) clearSearchBtn.style.display = 'none';
            });

            closeResultsBtn?.addEventListener('click', () => {
                if (resultsPanel) resultsPanel.style.display = 'none';
                if (searchInput) searchInput.value = '';
                if (clearSearchBtn) clearSearchBtn.style.display = 'none';
                activeFilter = null;
                recomChips.forEach(c => c.classList.remove('active'));
            });

            recomChips.forEach(chip => {
                chip.addEventListener('click', () => {
                    const filter = chip.getAttribute('data-recom');
                    if (activeFilter === filter) {
                        activeFilter = null;
                        chip.classList.remove('active');
                    } else {
                        recomChips.forEach(c => c.classList.remove('active'));
                        chip.classList.add('active');
                        activeFilter = filter;
                    }
                    performSearch();
                });
            });

            // Detail Header Search Trigger
            detailSearchBtn?.addEventListener('click', () => {
                // Switch smoothly back to overview and open top search drawer
                overviewView.style.display = 'block';
                overviewView.style.opacity = '1';
                detailView.style.display = 'none';
                detailView.style.opacity = '0';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                setTimeout(() => {
                    openSearchDrawer();
                    // Open recommendations automatically
                    if (!activeFilter && (!searchInput || !searchInput.value)) {
                        const chefChip = document.querySelector('.recom-chip.chip-chef-special');
                        if (chefChip) chefChip.click();
                    }
                }, 150);
            });

            // =========================================================================
            // URL ROUTING & PERSISTENT DEEP LINKING
            // =========================================================================
            // Detect if a category was requested via URL path (/menu.php/pizza) or hash (#pizzas)
            const hashSection = matchSectionFromRoute(window.location.hash);
            const pathSection = matchSectionFromRoute(window.location.pathname);
            const targetOnLoad = initialRouteSection || hashSection || pathSection;

            if (targetOnLoad) {
                openDetailView(targetOnLoad, true);
            }

            // Handle browser Back & Forward navigation
            window.addEventListener('popstate', (e) => {
                const stateSection = e.state && e.state.section;
                const urlSection = matchSectionFromRoute(window.location.pathname) || matchSectionFromRoute(window.location.hash);
                const sectionToOpen = stateSection || urlSection;

                if (sectionToOpen) {
                    openDetailView(sectionToOpen, false);
                } else {
                    showOverview(false);
                }
            });

            // Handle manual or anchor hash changes
            window.addEventListener('hashchange', () => {
                const hashMatch = matchSectionFromRoute(window.location.hash);
                if (hashMatch) {
                    openDetailView(hashMatch, true);
                }
            });

            // Initialize Dine-In Saved Dishes List state
            syncSlipUI();

            updateDockLabels();
        });
    </script>

    <!-- Dynamic Pop-Up & Form Studio Integration -->
    <?php require_once __DIR__ . '/popup_engine.php'; ?>
</body>
</html>

