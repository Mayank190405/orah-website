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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(104, 20, 24, 0.08);
        }

        @media (max-width: 768px) {
            .masthead-utility-row {
                margin-bottom: 16px;
                padding-bottom: 10px;
            }
        }

        .masthead-back-link {
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

        .masthead-brand-crest {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--burgundy);
        }

        .masthead-swans-img {
            height: 28px;
            width: auto;
        }

        .masthead-brand-name {
            font-family: var(--font-heading);
            font-size: 1.15rem;
            letter-spacing: 2px;
            font-weight: 500;
        }

        @media (max-width: 520px) {
            .masthead-brand-name {
                display: none;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .back-to-sections-btn {
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
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: 'Dream Avenue', 'Cormorant Garamond', Georgia, serif;
            font-size: 0.95rem;
            letter-spacing: 1.5px;
            color: var(--burgundy);
            font-weight: 600;
        }

        .detail-brand-crest img {
            width: 20px;
            height: auto;
        }

        /* Carousel Deck for Swiping */
        .swipe-deck-viewport {
            position: relative;
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
            padding: 0 0 130px;
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
            padding: 14px 16px 0;
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
            gap: 12px;
            margin-bottom: 22px;
            padding: 8px 0 4px;
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
            margin-bottom: 4px;
        }

        .editorial-title {
            font-family: 'Dream Avenue', 'Cormorant Garamond', Georgia, serif;
            font-size: clamp(2.3rem, 6vw, 3.4rem);
            color: var(--burgundy);
            line-height: 1.02;
            font-weight: 400;
            margin: 0 0 8px;
            letter-spacing: 0.3px;
            word-break: break-word;
        }

        .editorial-title-underline {
            width: 34px;
            height: 2px;
            background: var(--burgundy);
            margin-bottom: 10px;
        }

        .editorial-desc {
            font-size: clamp(0.82rem, 1.8vw, 0.92rem);
            color: #5d564e;
            max-width: 440px;
            line-height: 1.45;
            margin: 0;
            word-break: break-word;
        }

        .editorial-header-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
            flex-shrink: 0;
            min-width: 0;
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
            margin-top: 10px;
            font-family: 'Caveat', cursive;
            font-size: clamp(1.2rem, 3vw, 1.7rem);
            color: #a67c52;
            white-space: nowrap;
            transform: rotate(-6deg);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .cursive-underline-svg {
            width: 100%;
            height: 10px;
            color: #a67c52;
            margin-top: -4px;
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
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(70, 25, 20, 0.07);
            border: 1px solid rgba(104, 20, 24, 0.08);
            display: flex;
            flex-direction: row;
            position: relative;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .dish-split-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 34px rgba(70, 25, 20, 0.12);
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
            background: #FAF5EB;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
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
            color: #b71c1c;
            border-color: rgba(183, 28, 28, 0.2);
            background: #ffebee;
        }

        .split-fav-btn.active svg {
            fill: #b71c1c;
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
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: #8a7153;
            text-transform: uppercase;
            margin: 4px 0 8px;
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
           FLOATING SLIDE NAVIGATION DOCK
           ========================================================================== */
        .floating-slide-dock {
            position: fixed;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 24px);
            max-width: 430px;
            background: rgba(248, 243, 235, 0.94);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 40px;
            box-shadow: 0 12px 36px rgba(45, 20, 15, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.8);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 12px;
            box-sizing: border-box;
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
                padding: 0 0 120px;
            }

            .section-slide-pane {
                padding: 10px 12px 0;
            }

            .dish-split-card {
                border-radius: 18px;
            }

            .split-card-media {
                flex: 0 0 47%;
                min-height: 220px;
            }

            .split-card-details {
                flex: 1 1 53%;
                padding: 12px 10px;
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
                        <span class="masthead-brand-name">ORAH HOUSE</span>
                    </div>

                    <div class="utility-spacer" aria-hidden="true"></div>
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
                                'image' => 'uploads/artisanal_pizza.jpg'
                            ];
                            $count = count($dishes);
                        ?>
                        <?php 
                            $displayName = ($sectionName === 'Pasta') ? 'Pastas' : $sectionName;
                        ?>
                        <article class="section-card" data-section-target="<?= htmlspecialchars($sectionName) ?>" tabindex="0" role="button" aria-label="Explore <?= htmlspecialchars($displayName) ?>">
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
                        <span>ORAH HOUSE</span>
                    </div>

                    <div style="width: 80px;" aria-hidden="true"></div>
                </div>
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
                                    <div class="editorial-counter-box">
                                        <span class="counter-number"><?= $count ?></span>
                                        <span class="counter-label">ITEMS</span>
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
                                    ?>
                                    <article class="dish-split-card" data-dish-id="<?= htmlspecialchars($dish['id'] ?? '') ?>">
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
                                                    <button type="button" class="split-fav-btn" aria-label="Favorite <?= htmlspecialchars($dishName) ?>">
                                                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                                                        </svg>
                                                    </button>
                                                </div>

                                                <?php if (!empty($dishDesc)): ?>
                                                    <p class="split-dish-desc"><?= htmlspecialchars($dishDesc) ?></p>
                                                <?php elseif (!empty($particular) && $particular !== $dishName): ?>
                                                    <p class="split-dish-desc"><?= htmlspecialchars($particular) ?> prepared with slow-fermented artisanal craft and fresh estate ingredients.</p>
                                                <?php else: ?>
                                                    <p class="split-dish-desc">House signature prepared with artisanal craft, estate herbs, and slow-baked pantry ingredients.</p>
                                                <?php endif; ?>

                                                <div class="split-divider"></div>

                                                <!-- 4 Highlights with Icons -->
                                                <div class="split-highlights-list">
                                                    <?php foreach ($highlights as $hl): ?>
                                                        <div class="split-highlight-item">
                                                            <span class="highlight-icon"><?= $hl['icon'] ?></span>
                                                            <span class="highlight-name"><?= htmlspecialchars($hl['name']) ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>

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
            </div>
        </div>
    </div>

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

            // Get display name for labels
            function getCleanName(key) {
                if (!key) return '';
                return (key === 'Pasta') ? 'Pastas' : key;
            }

            // Dynamically adjust viewport height to fit active section cleanly
            function updateViewportHeight(targetIdx = currentSectionIndex) {
                const pane = sectionPanes[targetIdx];
                if (pane && viewport) {
                    const h = pane.offsetHeight;
                    if (h > 0) {
                        viewport.style.height = h + 'px';
                    }
                }
            }

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

            // Slide to a specific section with silky ease-out
            function goToSection(index, animated = true, allowScroll = false) {
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

                // Update URL hash without scroll jumps
                const activeKey = sectionKeys[currentSectionIndex];
                if (activeKey) {
                    history.replaceState(null, '', '#' + encodeURIComponent(activeKey.toLowerCase().replace(/[\s&]+/g, '-')));
                }

                // Only smoothly scroll if user tapped dock while far down the page
                if (allowScroll && window.scrollY > 250 && animated) {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }

            // Show Detailed Menu
            function openDetailView(sectionKey) {
                let targetIndex = sectionKeys.findIndex(k => k === sectionKey);
                if (targetIndex === -1) targetIndex = 0;

                overviewView.style.display = 'none';
                detailView.style.display = 'block';
                goToSection(targetIndex, false, false);
                updateViewportHeight(targetIndex);

                requestAnimationFrame(() => {
                    detailView.style.opacity = '1';
                });

                window.scrollTo({ top: 0, behavior: 'instant' });
            }

            // Return to Overview (First Page)
            function showOverview() {
                detailView.style.opacity = '0';
                setTimeout(() => {
                    detailView.style.display = 'none';
                    overviewView.style.display = 'block';
                    overviewView.style.opacity = '1';
                }, 200);

                window.scrollTo({ top: 0, behavior: 'smooth' });
                history.replaceState(null, '', window.location.pathname);
            }

            backBtn?.addEventListener('click', showOverview);
            dockExploreBtn?.addEventListener('click', showOverview);

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

            // Keyboard Arrow Support
            window.addEventListener('keydown', (e) => {
                if (detailView.style.display !== 'none' && detailView.style.display !== '') {
                    if (e.key === 'ArrowRight') {
                        goToSection((currentSectionIndex + 1) % totalSections, true);
                    } else if (e.key === 'ArrowLeft') {
                        goToSection((currentSectionIndex - 1 + totalSections) % totalSections, true);
                    } else if (e.key === 'Escape') {
                        closeModal();
                    }
                }
            });

            // =========================================================================
            // FAVORITE BUTTON INTERACTION
            // =========================================================================
            document.querySelectorAll('.split-fav-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    btn.classList.toggle('active');
                });
            });

            // =========================================================================
            // DISH MODAL QUICK VIEW
            // =========================================================================
            function openModal(data) {
                if (!modalBackdrop) return;
                modalImg.src = data.img;
                modalBadge.textContent = data.badge;
                modalTitle.textContent = data.name;
                modalPrice.textContent = data.price;
                modalDesc.textContent = data.desc;
                modalDiet.textContent = (data.veg === '1') ? '● VEGETARIAN' : '● NON-VEG';
                modalDiet.style.color = (data.veg === '1') ? '#2e7d32' : '#c62828';

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

                modalBackdrop.classList.add('active');
                modalBackdrop.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                if (!modalBackdrop) return;
                modalBackdrop.classList.remove('active');
                modalBackdrop.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            document.querySelectorAll('.split-view-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    let custom = [];
                    try {
                        const raw = btn.getAttribute('data-custom');
                        if (raw) custom = JSON.parse(raw);
                    } catch(err) {}

                    openModal({
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

            // Initial Hash Routing
            const initialHash = window.location.hash.replace('#', '').toLowerCase();
            if (initialHash) {
                const matchedKey = sectionKeys.find(key => {
                    const slug = key.toLowerCase().replace(/[\s&]+/g, '-');
                    return slug === initialHash || slug.includes(initialHash);
                });

                if (matchedKey) {
                    openDetailView(matchedKey);
                }
            }

            updateDockLabels();
        });
    </script>

    <!-- Dynamic Pop-Up & Form Studio Integration -->
    <?php require_once __DIR__ . '/popup_engine.php'; ?>
</body>
</html>

