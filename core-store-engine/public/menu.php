<?php
require_once __DIR__ . '/../engine/JsonStorage.php';
require_once __DIR__ . '/../engine/ThemeEngine.php';

use CoreStore\Engine\JsonStorage;
use CoreStore\Engine\ThemeEngine;

// Read settings & catalog
$settingsStorage = new JsonStorage(__DIR__ . '/../data/settings.json');
$settings = $settingsStorage->read();
$themeEngine = new ThemeEngine($settings);

$catalogStorage = new JsonStorage(__DIR__ . '/../data/catalog.json');
$catalog = $catalogStorage->read();

$brand = $settings['brand'] ?? ['text' => 'Orah House'];

// Group catalog items by section badge
$sections = [];
$sectionMeta = [
    'Flat Breads' => [
        'subtitle' => 'Hand-stretched sourdough flatbreads baked with artisanal melts & toppings',
        'image' => 'uploads/mozzarella_flatbread.jpg'
    ],
    'Pizzas' => [
        'subtitle' => 'Slow-fermented Neapolitan style with San Marzano tomatoes & fresh basil',
        'image' => 'uploads/artisanal_pizza.jpg'
    ],
    'Pasta' => [
        'subtitle' => 'House-made pastas tossed in rich sauces with seasonal ingredients',
        'image' => 'uploads/pastas_showcase.jpg'
    ],
    'Desserts' => [
        'subtitle' => 'Sweet endings crafted with premium ingredients',
        'image' => 'uploads/desserts_showcase.jpg'
    ],
    'Appetizers' => [
        'subtitle' => 'Char-grilled skewers, molten cheese bites, and savory small plates',
        'image' => 'uploads/paneer_skewers.jpg'
    ],
    'Bowls & Salads' => [
        'subtitle' => 'Nourishing harvest grains, wild greens, and vibrant house vinaigrettes',
        'image' => 'uploads/exotic_rice_bowl.jpg'
    ],
    'Salads' => [
        'subtitle' => 'Fresh garden greens, pineapple crunch, and house-whipped dressings',
        'image' => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=800&q=80'
    ],
    'Burgers' => [
        'subtitle' => 'Artisanal patties, slow-cooked mushroom sauce, and toasted sesame brioche',
        'image' => 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=800&q=80'
    ],
    'Open Toast & Bruschetta' => [
        'subtitle' => 'Golden toasted brioche, avocado salsa, and classic Italian crostinis',
        'image' => 'https://images.unsplash.com/photo-1506280754576-f6fa8a873550?w=800&q=80'
    ],
    'Sandwiches' => [
        'subtitle' => 'Multi-grain artisan loaves pressed with gourmet tandoor & BBQ fillings',
        'image' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=800&q=80'
    ],
    'Siders' => [
        'subtitle' => 'Crispy golden fries, rustic roasted wedges, and warm dipping nachos',
        'image' => 'https://images.unsplash.com/photo-1541592106381-b31e9677c0e5?w=800&q=80'
    ],
    'Coffee & Beverages' => [
        'subtitle' => 'V60 single origin pour-overs, textured Spanish lattes, and cold steeps',
        'image' => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=800&q=80'
    ]
];

foreach ($catalog as $dish) {
    if (!($dish['available'] ?? true)) continue;
    $badge = $dish['badge'] ?? 'General';
    if (!isset($sections[$badge])) {
        $sections[$badge] = [];
    }
    $sections[$badge][] = $dish;
}

// Preferred visual order for sections (Matches reference showcase)
$preferredOrder = [
    'Flat Breads',
    'Pizzas',
    'Pasta',
    'Desserts',
    'Appetizers',
    'Burgers',
    'Open Toast & Bruschetta',
    'Sandwiches',
    'Bowls & Salads',
    'Salads',
    'Siders',
    'Coffee & Beverages'
];

$orderedSections = [];
foreach ($preferredOrder as $orderKey) {
    if (isset($sections[$orderKey])) {
        $orderedSections[$orderKey] = $sections[$orderKey];
    }
}
// Add any remaining categories
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
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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

        body {
            background-color: var(--cream-bg);
            color: var(--burgundy);
            font-family: var(--font-body);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            -webkit-tap-highlight-color: transparent;
        }

        /* Top App Bar */
        .menu-appbar {
            position: sticky;
            top: 0;
            width: 100%;
            background: rgba(251, 246, 238, 0.96);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border-light);
            z-index: 200;
            transition: all 0.3s ease;
        }

        .menu-appbar-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--burgundy);
        }

        .brand-logo-img {
            height: 36px;
            width: auto;
        }

        .brand-title {
            font-family: var(--font-heading);
            font-size: 1.25rem;
            letter-spacing: 2px;
            font-weight: 500;
        }

        .appbar-nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .appbar-btn {
            font-size: 0.80rem;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            text-decoration: none;
            color: var(--burgundy);
            padding: 7px 15px;
            border-radius: 20px;
            transition: all 0.2s ease;
        }

        .appbar-btn:hover {
            background: rgba(104, 20, 24, 0.08);
        }

        .btn-download-pdf {
            background: var(--burgundy);
            color: #FBF6EE !important;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(104, 20, 24, 0.2);
        }

        .btn-download-pdf:hover {
            background: var(--burgundy-dark) !important;
            transform: translateY(-1px);
        }

        /* Main View Container */
        .views-wrapper {
            position: relative;
            min-height: calc(100vh - 120px);
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

        .overview-hero {
            text-align: center;
            padding: 55px 20px 30px;
            max-width: 820px;
            margin: 0 auto;
        }

        .overview-eyebrow {
            font-size: 0.70rem;
            font-weight: 700;
            letter-spacing: 4px;
            color: var(--gold-accent);
            text-transform: uppercase;
            display: block;
            margin-bottom: 10px;
        }

        .overview-title {
            font-family: var(--font-heading);
            font-size: clamp(2.3rem, 5vw, 3.6rem);
            line-height: 1.05;
            margin-bottom: 14px;
            font-weight: 400;
            letter-spacing: 0.5px;
        }

        .overview-subtitle {
            font-size: clamp(0.95rem, 1.8vw, 1.1rem);
            color: rgba(104, 20, 24, 0.82);
            line-height: 1.55;
            margin-bottom: 30px;
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
        }

        /* Detail Sticky Subheader */
        .detail-nav-bar {
            position: sticky;
            top: 65px;
            background: rgba(251, 246, 238, 0.96);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border-light);
            z-index: 150;
            padding: 10px 0;
        }

        .detail-nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .back-to-sections-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ffffff;
            border: 1px solid var(--border-light);
            padding: 8px 14px;
            border-radius: 20px;
            color: var(--burgundy);
            font-size: 0.82rem;
            font-weight: 600;
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

        /* Horizontal Category Pills Carousel */
        .category-pills-carousel {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding: 4px 0;
            flex: 1;
        }

        .category-pills-carousel::-webkit-scrollbar {
            display: none;
        }

        .cat-tab-btn {
            background: #ffffff;
            border: 1px solid var(--border-light);
            color: var(--burgundy);
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .cat-tab-btn:hover {
            border-color: var(--burgundy);
        }

        .cat-tab-btn.active {
            background: var(--burgundy);
            color: #FBF6EE;
            border-color: var(--burgundy);
            box-shadow: 0 4px 12px rgba(104, 20, 24, 0.2);
        }

        /* Swipe Controller & Indicators */
        .swipe-hint-bar {
            text-align: center;
            padding: 14px 20px 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.76rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            color: var(--gold-accent);
            text-transform: uppercase;
        }

        /* Carousel Deck for Swiping */
        .swipe-deck-viewport {
            position: relative;
            max-width: 1280px;
            margin: 0 auto;
            padding: 10px 20px 70px;
            overflow: hidden;
            touch-action: pan-y;
        }

        .swipe-deck-slider {
            display: flex;
            transition: transform 0.35s cubic-bezier(0.2, 0.8, 0.2, 1);
            width: 100%;
        }

        .section-slide-pane {
            flex: 0 0 100%;
            width: 100%;
            box-sizing: border-box;
            opacity: 1;
            transition: opacity 0.25s ease;
        }

        .section-slide-header {
            margin-bottom: 30px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
        }

        .section-slide-title {
            font-family: var(--font-heading);
            font-size: clamp(2rem, 4vw, 2.8rem);
            color: var(--burgundy);
            margin-bottom: 4px;
            font-weight: 500;
        }

        .section-slide-desc {
            font-size: 0.92rem;
            color: #5d564e;
            max-width: 620px;
            line-height: 1.5;
        }

        /* Desktop Prev / Next Buttons */
        .deck-nav-btn {
            position: absolute;
            top: 45%;
            transform: translateY(-50%);
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid var(--border-light);
            color: var(--burgundy);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            z-index: 50;
            transition: all 0.2s ease;
        }

        .deck-nav-btn:hover {
            background: var(--burgundy);
            color: #FBF6EE;
            transform: translateY(-50%) scale(1.08);
        }

        .deck-nav-btn.prev-btn { left: 4px; }
        .deck-nav-btn.next-btn { right: 4px; }

        @media (max-width: 900px) {
            .deck-nav-btn { display: none; }
        }

        /* Dish Card in Detailed View */
        .dishes-catalog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 28px;
        }

        .dish-detail-card {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 6px 20px rgba(104, 20, 24, 0.05);
            transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.3s ease;
        }

        .dish-detail-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 32px rgba(104, 20, 24, 0.12);
        }

        .dish-detail-media {
            position: relative;
            width: 100%;
            height: 220px;
            overflow: hidden;
            background: #eae2d5;
        }

        .dish-detail-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .dish-detail-card:hover .dish-detail-img {
            transform: scale(1.06);
        }

        .dish-detail-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(251, 246, 238, 0.95);
            backdrop-filter: blur(8px);
            color: var(--burgundy);
            font-size: 0.70rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 12px;
        }

        .dish-detail-body {
            padding: 22px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .dish-header-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }

        .dish-unique-title {
            font-family: var(--font-heading);
            font-size: 1.35rem;
            color: var(--burgundy);
            font-weight: 500;
            line-height: 1.2;
        }

        .dish-price-text {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--burgundy);
            white-space: nowrap;
        }

        .dish-particular-label {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--gold-accent);
            text-transform: uppercase;
            margin-bottom: 10px;
            display: block;
        }

        .dish-desc-text {
            font-size: 0.88rem;
            color: #5a544c;
            line-height: 1.5;
            margin-bottom: 20px;
            flex: 1;
        }

        .dish-footer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 1px solid rgba(104, 20, 24, 0.08);
        }

        .dish-diet-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #2e7d32;
        }

        .dish-order-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(104, 20, 24, 0.07);
            color: var(--burgundy);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 16px;
            font-size: 0.80rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
        }

        .dish-order-link:hover {
            background: var(--burgundy);
            color: #FBF6EE;
        }

        /* Bottom Booklet Download Banner */
        .pdf-download-strip {
            background: var(--burgundy);
            color: #FBF6EE;
            border-radius: 24px;
            padding: 44px 32px;
            margin: 40px auto 80px;
            max-width: 1240px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            box-shadow: 0 16px 40px rgba(104, 20, 24, 0.22);
        }

        .pdf-strip-title {
            font-family: var(--font-heading);
            font-size: clamp(1.6rem, 3.2vw, 2.2rem);
            margin-bottom: 6px;
            font-weight: 400;
        }

        .pdf-strip-sub {
            font-size: 0.92rem;
            opacity: 0.88;
            max-width: 580px;
        }

        .pdf-strip-btn {
            background: #FBF6EE;
            color: var(--burgundy);
            text-decoration: none;
            padding: 12px 26px;
            border-radius: 28px;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 1.2px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .pdf-strip-btn:hover {
            transform: translateY(-2px);
            background: #ffffff;
        }

        /* Mobile Viewport Optimizations */
        @media (max-width: 768px) {
            .menu-main-wrap {
                padding: 15px 14px 50px;
            }

            .overview-hero {
                padding: 30px 12px 18px;
            }

            .sections-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 14px;
            }

            .dishes-catalog-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .dish-detail-media {
                height: 190px;
            }

            .swipe-deck-viewport {
                padding: 8px 12px 50px;
            }

            .detail-nav-bar {
                top: 55px;
            }

            .detail-nav-container {
                padding: 0 12px;
                gap: 10px;
            }

            .back-to-sections-btn {
                padding: 7px 11px;
                font-size: 0.76rem;
            }

            .cat-tab-btn {
                padding: 7px 14px;
                font-size: 0.76rem;
            }

            .pdf-download-strip {
                padding: 28px 20px;
                margin: 25px auto 50px;
                border-radius: 18px;
            }

            .pdf-strip-btn {
                width: 100%;
                justify-content: center;
                box-sizing: border-box;
            }

            .appbar-btn {
                padding: 5px 9px;
                font-size: 0.72rem;
            }

            .btn-download-pdf span {
                display: none;
            }

            .btn-download-pdf::after {
                content: "PDF";
                font-size: 0.72rem;
                font-weight: 700;
            }
        }

        /* Footer */
        .menu-footer {
            background: #ffffff;
            border-top: 1px solid var(--border-light);
            padding: 35px 20px;
            text-align: center;
            color: rgba(104, 20, 24, 0.7);
            font-size: 0.82rem;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header class="menu-appbar">
        <div class="menu-appbar-inner">
            <a href="index.php" class="brand-link">
                <img src="assets/images/swans_only.png" alt="Orah Emblem" class="brand-logo-img">
                <span class="brand-title">ORAH HOUSE</span>
            </a>

            <div class="appbar-nav-actions">
                <a href="index.php" class="appbar-btn">Home</a>
                <a href="index.php#story" class="appbar-btn">Our Story</a>
                <a href="Menu.pdf" target="_blank" download="Orah_House_Menu.pdf" class="appbar-btn btn-download-pdf">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    <span>Download PDF</span>
                </a>
            </div>
        </div>
    </header>

    <div class="views-wrapper">

        <!-- ==========================================================================
             VIEW 1: MENU SECTIONS OVERVIEW (First Page)
             ========================================================================== -->
        <section id="sectionsOverviewView">
            <div class="overview-hero">
                <span class="overview-eyebrow">THE CULINARY ARCHITECTURE</span>
                <h1 class="overview-title">Explore By Section</h1>
                <p class="overview-subtitle">
                    Select a section below to explore our seasonal creations. Inside each section, swipe left or right on your screen to effortlessly glide across the entire menu.
                </p>
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

                <!-- Booklet Download Banner -->
                <div class="pdf-download-strip">
                    <div class="pdf-strip-info">
                        <h3 class="pdf-strip-title">The Complete Printed Menu</h3>
                        <p class="pdf-strip-sub">Download our complete 10-page table menu booklet with full descriptions, coffee tasting guides, and kitchen craft stories.</p>
                    </div>
                    <a href="Menu.pdf" target="_blank" download="Orah_House_Menu.pdf" class="pdf-strip-btn">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                        <span>DOWNLOAD MENU (PDF)</span>
                    </a>
                </div>
            </div>
        </section>


        <!-- ==========================================================================
             VIEW 2: DETAILED MENU WITH TOUCH SWIPE CONTROLLER
             ========================================================================== -->
        <section id="detailMenuView">
            <!-- Sticky Section Navigation Bar -->
            <div class="detail-nav-bar">
                <div class="detail-nav-container">
                    <button type="button" class="back-to-sections-btn" id="backToSectionsBtn">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                        <span>All Sections</span>
                    </button>

                    <div class="category-pills-carousel" id="categoryTabsCarousel">
                        <?php foreach ($sectionKeys as $idx => $sKey): ?>
                            <button type="button" class="cat-tab-btn <?= $idx === 0 ? 'active' : '' ?>" data-index="<?= $idx ?>" data-key="<?= htmlspecialchars($sKey) ?>">
                                <?= htmlspecialchars($sKey) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Swipe Guidance Indicator -->
            <div class="swipe-hint-bar">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8L22 12L18 16M6 8L2 12L6 16M2 12H22"/></svg>
                <span>Swipe left / right or tap tabs above to switch sections</span>
            </div>

            <!-- Swiping Viewport -->
            <div class="swipe-deck-viewport" id="swipeDeckViewport">
                <!-- Floating Arrow Buttons for Desktop Navigation -->
                <button type="button" class="deck-nav-btn prev-btn" id="prevSectionBtn" aria-label="Previous Section">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <button type="button" class="deck-nav-btn next-btn" id="nextSectionBtn" aria-label="Next Section">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </button>

                <!-- Slider Containing Panes for Each Section -->
                <div class="swipe-deck-slider" id="swipeDeckSlider">
                    <?php foreach ($orderedSections as $sKey => $dishes): ?>
                        <?php 
                            $meta = $sectionMeta[$sKey] ?? [
                                'subtitle' => 'Handcrafted delicacies prepared with fine estate ingredients.'
                            ];
                        ?>
                        <div class="section-slide-pane" data-section-key="<?= htmlspecialchars($sKey) ?>">
                            <div class="section-slide-header">
                                <div>
                                    <h2 class="section-slide-title"><?= htmlspecialchars($sKey) ?></h2>
                                    <p class="section-slide-desc"><?= htmlspecialchars($meta['subtitle']) ?></p>
                                </div>
                                <span style="font-weight: 700; font-size: 0.85rem; color: var(--gold-accent);"><?= count($dishes) ?> Items</span>
                            </div>

                            <div class="dishes-catalog-grid">
                                <?php foreach ($dishes as $dish): ?>
                                    <?php 
                                        $dishName = $dish['name'] ?? 'Untitled Dish';
                                        $particular = $dish['particular'] ?? $dishName;
                                        $dishPrice = $dish['price'] ?? '₹0';
                                        $dishDesc = $dish['description'] ?? '';
                                        $dishImg = $dish['image'] ?? 'uploads/artisanal_pizza.jpg';
                                        $isVeg = !empty($dish['is_veg']);
                                        $orderUrl = "https://wa.me/?text=" . urlencode("Hello Orah House, I'd like to order: {$dishName} ({$dishPrice})");
                                    ?>
                                    <article class="dish-detail-card">
                                        <div class="dish-detail-media">
                                            <img src="<?= htmlspecialchars($dishImg) ?>" alt="<?= htmlspecialchars($dishName) ?>" class="dish-detail-img" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=700&q=80'">
                                            <span class="dish-detail-badge"><?= htmlspecialchars($sKey) ?></span>
                                        </div>

                                        <div class="dish-detail-body">
                                            <div class="dish-header-row">
                                                <h3 class="dish-unique-title"><?= htmlspecialchars($dishName) ?></h3>
                                                <span class="dish-price-text"><?= htmlspecialchars($dishPrice) ?></span>
                                            </div>

                                            <?php if (!empty($particular) && $particular !== $dishName): ?>
                                                <span class="dish-particular-label"><?= htmlspecialchars($particular) ?></span>
                                            <?php endif; ?>

                                            <p class="dish-desc-text"><?= htmlspecialchars($dishDesc) ?></p>

                                            <div class="dish-footer-row">
                                                <span class="dish-diet-tag">
                                                    <span>●</span>
                                                    <span><?= $isVeg ? 'Vegetarian' : 'Non-Veg' ?></span>
                                                </span>

                                                <a href="<?= htmlspecialchars($orderUrl) ?>" target="_blank" rel="noopener" class="dish-order-link">
                                                    <span>Order Dish</span>
                                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                                </a>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

    </div>

    <!-- Footer -->
    <footer class="menu-footer">
        <p>&copy; <?= date('Y') ?> Orah House &bull; Anandwalli, Nashik, Maharashtra 422013 &bull; All Rights Reserved</p>
    </footer>

    <!-- Interactive Navigation & Touch Swipe Engine -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const overviewView = document.getElementById('sectionsOverviewView');
            const detailView = document.getElementById('detailMenuView');
            const backBtn = document.getElementById('backToSectionsBtn');
            const sectionCards = document.querySelectorAll('.section-card');
            const catTabs = document.querySelectorAll('.cat-tab-btn');
            const slider = document.getElementById('swipeDeckSlider');
            const viewport = document.getElementById('swipeDeckViewport');
            const prevBtn = document.getElementById('prevSectionBtn');
            const nextBtn = document.getElementById('nextSectionBtn');
            const tabsCarousel = document.getElementById('categoryTabsCarousel');

            const totalSections = catTabs.length;
            let currentSectionIndex = 0;

            // Show Detailed Menu
            function openDetailView(sectionKey) {
                // Find index
                let targetIndex = 0;
                catTabs.forEach((tab, idx) => {
                    if (tab.getAttribute('data-key') === sectionKey) {
                        targetIndex = idx;
                    }
                });

                overviewView.style.display = 'none';
                detailView.style.display = 'block';
                setTimeout(() => {
                    detailView.style.opacity = '1';
                }, 10);

                goToSection(targetIndex, false);
                window.scrollTo({ top: 0, behavior: 'smooth' });
                history.replaceState(null, '', '#' + encodeURIComponent(sectionKey.toLowerCase().replace(/[\s&]+/g, '-')));
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

            // Clicking any section card on Page 1 opens Page 2
            sectionCards.forEach(card => {
                card.addEventListener('click', () => {
                    const target = card.getAttribute('data-section-target');
                    openDetailView(target);
                });
            });

            // Slide to a specific section
            function goToSection(index, animated = true) {
                if (index < 0) index = 0;
                if (index >= totalSections) index = totalSections - 1;
                currentSectionIndex = index;

                // Update slider transform
                slider.style.transition = animated ? 'transform 0.35s cubic-bezier(0.2, 0.8, 0.2, 1)' : 'none';
                slider.style.transform = `translateX(-${currentSectionIndex * 100}%)`;

                // Update tabs
                catTabs.forEach((tab, idx) => {
                    if (idx === currentSectionIndex) {
                        tab.classList.add('active');
                        tab.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                    } else {
                        tab.classList.remove('active');
                    }
                });

                // Update button disabled state
                if (prevBtn) prevBtn.style.opacity = (currentSectionIndex === 0) ? '0.35' : '1';
                if (nextBtn) nextBtn.style.opacity = (currentSectionIndex === totalSections - 1) ? '0.35' : '1';

                const activeKey = catTabs[currentSectionIndex]?.getAttribute('data-key');
                if (activeKey) {
                    history.replaceState(null, '', '#' + encodeURIComponent(activeKey.toLowerCase().replace(/[\s&]+/g, '-')));
                }
            }

            // Clicking Tabs
            catTabs.forEach((tab, idx) => {
                tab.addEventListener('click', () => {
                    goToSection(idx, true);
                });
            });

            // Desktop Arrow navigation
            prevBtn?.addEventListener('click', () => {
                if (currentSectionIndex > 0) goToSection(currentSectionIndex - 1, true);
            });
            nextBtn?.addEventListener('click', () => {
                if (currentSectionIndex < totalSections - 1) goToSection(currentSectionIndex + 1, true);
            });

            // =========================================================================
            // TOUCH / MOUSE SWIPE GESTURE ENGINE
            // =========================================================================
            let startX = 0;
            let startY = 0;
            let currentX = 0;
            let currentY = 0;
            let isSwiping = false;
            let isHorizontalSwipe = null;

            function handleTouchStart(e) {
                const touch = e.touches ? e.touches[0] : e;
                startX = touch.clientX;
                startY = touch.clientY;
                currentX = startX;
                currentY = startY;
                isSwiping = true;
                isHorizontalSwipe = null;
            }

            function handleTouchMove(e) {
                if (!isSwiping) return;
                const touch = e.touches ? e.touches[0] : e;
                currentX = touch.clientX;
                currentY = touch.clientY;

                const diffX = currentX - startX;
                const diffY = currentY - startY;

                // Determine swipe orientation if not yet decided
                if (isHorizontalSwipe === null) {
                    if (Math.abs(diffX) > 10 || Math.abs(diffY) > 10) {
                        isHorizontalSwipe = Math.abs(diffX) > Math.abs(diffY);
                    }
                }

                // If user is swiping horizontally, prevent vertical scroll and track visually
                if (isHorizontalSwipe) {
                    if (e.cancelable) e.preventDefault();
                    // Slight resistance at the ends
                    let dragOffset = diffX;
                    if ((currentSectionIndex === 0 && diffX > 0) || (currentSectionIndex === totalSections - 1 && diffX < 0)) {
                        dragOffset = diffX * 0.3;
                    }
                    const basePercent = -currentSectionIndex * 100;
                    const pixelWidth = viewport.offsetWidth || 1;
                    const percentOffset = (dragOffset / pixelWidth) * 100;

                    slider.style.transition = 'none';
                    slider.style.transform = `translateX(${basePercent + percentOffset}%)`;
                }
            }

            function handleTouchEnd() {
                if (!isSwiping) return;
                isSwiping = false;

                if (isHorizontalSwipe) {
                    const diffX = currentX - startX;
                    const threshold = 45; // Minimum px to trigger section switch

                    if (diffX < -threshold && currentSectionIndex < totalSections - 1) {
                        goToSection(currentSectionIndex + 1, true); // Swipe left -> next
                    } else if (diffX > threshold && currentSectionIndex > 0) {
                        goToSection(currentSectionIndex - 1, true); // Swipe right -> prev
                    } else {
                        goToSection(currentSectionIndex, true); // Return to current
                    }
                }
                isHorizontalSwipe = null;
            }

            // Touch listeners (Mobile / Tablets)
            viewport.addEventListener('touchstart', handleTouchStart, { passive: true });
            viewport.addEventListener('touchmove', handleTouchMove, { passive: false });
            viewport.addEventListener('touchend', handleTouchEnd);
            viewport.addEventListener('touchcancel', handleTouchEnd);

            // Mouse drag listeners (Desktop swipe)
            let isMouseDown = false;
            viewport.addEventListener('mousedown', (e) => {
                if (e.target.closest('button') || e.target.closest('a')) return;
                isMouseDown = true;
                handleTouchStart(e);
            });
            window.addEventListener('mousemove', (e) => {
                if (!isMouseDown) return;
                handleTouchMove(e);
            });
            window.addEventListener('mouseup', () => {
                if (!isMouseDown) return;
                isMouseDown = false;
                handleTouchEnd();
            });

            // Check URL Hash on load
            const initialHash = window.location.hash.replace('#', '').toLowerCase();
            if (initialHash) {
                let matchedKey = null;
                catTabs.forEach(tab => {
                    const key = tab.getAttribute('data-key');
                    const slug = key.toLowerCase().replace(/[\s&]+/g, '-');
                    if (slug === initialHash || slug.includes(initialHash)) {
                        matchedKey = key;
                    }
                });

                if (matchedKey) {
                    openDetailView(matchedKey);
                }
            }
        });
    </script>
</body>
</html>
