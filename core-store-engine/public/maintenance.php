<?php
require_once __DIR__ . '/../engine/JsonStorage.php';
require_once __DIR__ . '/../engine/ThemeEngine.php';

use CoreStore\Engine\JsonStorage;
use CoreStore\Engine\ThemeEngine;

$settingsStorage = new JsonStorage(__DIR__ . '/../data/settings.json');
$settings = $settingsStorage->read() ?: [];
$themeEngine = new ThemeEngine($settings);

$pagesStorage = new JsonStorage(__DIR__ . '/../data/pages.json');
$pages = $pagesStorage->read() ?: [];

// Detect current page caller
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'menu.php');
$pageConfig = $pages[$currentPage] ?? [
    'title' => 'Orah House',
    'status' => 'down',
    'down_message' => 'Our doors and kitchen are currently preparing for a private culinary gathering. We invite you to connect directly with our concierge.'
];

$downMessage = $pageConfig['down_message'] ?? 'Our space is currently undergoing seasonal curation. Check back shortly.';
$altPage = ($currentPage === 'menu.php') ? 'index.php' : 'menu.php';
$altPageName = ($currentPage === 'menu.php') ? 'Visit Storefront' : 'View Menu';
$isAltLive = ($pages[$altPage]['status'] ?? 'live') === 'live';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageConfig['title'] ?? 'Under Curation') ?> &bull; Orah House</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        <?= $themeEngine->renderCssCustomProperties() ?>

        :root {
            --cream-bg: #FBF6EE;
            --burgundy: #681418;
            --burgundy-dark: #4a0c0f;
            --gold-accent: #c99a68;
            --card-surface: #ffffff;
            --font-serif: 'Dream Avenue', 'Cormorant Garamond', Georgia, serif;
            --font-body: 'Manrope', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--cream-bg);
            color: #2b241e;
            font-family: var(--font-body);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            text-align: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Subtle atmospheric ambient glow */
        body::before {
            content: '';
            position: absolute;
            top: 20%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(201, 154, 104, 0.12) 0%, rgba(104, 20, 24, 0.04) 50%, transparent 75%);
            pointer-events: none;
            z-index: 0;
        }

        .maintenance-card {
            position: relative;
            z-index: 2;
            max-width: 620px;
            width: 100%;
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(104, 20, 24, 0.12);
            border-radius: 28px;
            padding: clamp(34px, 6vw, 56px) clamp(22px, 5vw, 44px);
            box-shadow: 0 16px 45px rgba(50, 15, 18, 0.07);
        }

        .logo-wrap {
            margin-bottom: 24px;
            position: relative;
            display: inline-block;
        }

        .swans-logo {
            width: 110px;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(104, 20, 24, 0.15));
        }

        .eyebrow-pill {
            display: inline-block;
            background: rgba(104, 20, 24, 0.08);
            color: var(--burgundy);
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            padding: 5px 14px;
            border-radius: 20px;
            margin-bottom: 16px;
            border: 1px solid rgba(104, 20, 24, 0.14);
        }

        .maintenance-title {
            font-family: var(--font-serif);
            font-size: clamp(2.2rem, 5vw, 3.2rem);
            color: var(--burgundy);
            line-height: 1.05;
            font-weight: 400;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }

        .cursive-quote {
            font-family: 'Caveat', cursive;
            font-size: 1.45rem;
            color: #a67c52;
            margin-bottom: 20px;
            display: block;
        }

        .divider-ornament {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 0 auto 24px;
            width: 180px;
        }

        .divider-ornament span {
            flex: 1;
            height: 1px;
            background: rgba(201, 154, 104, 0.5);
        }

        .divider-ornament svg {
            color: var(--gold-accent);
        }

        .maintenance-desc {
            font-size: 0.95rem;
            color: #5a5047;
            line-height: 1.65;
            margin-bottom: 34px;
        }

        .actions-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
        }

        @media (min-width: 480px) {
            .actions-group {
                flex-direction: row;
                justify-content: center;
            }
        }

        .btn-concierge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--burgundy);
            color: #ffffff;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            padding: 14px 26px;
            border-radius: 28px;
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(104, 20, 24, 0.28);
            transition: all 0.25s ease;
        }

        .btn-concierge:hover {
            background: var(--burgundy-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 26px rgba(104, 20, 24, 0.38);
        }

        .btn-alt {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: #ffffff;
            color: #2b241e;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 13px 22px;
            border-radius: 28px;
            border: 1px solid rgba(104, 20, 24, 0.16);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-alt:hover {
            border-color: var(--burgundy);
            color: var(--burgundy);
        }

        .brand-footer-note {
            margin-top: 36px;
            font-size: 0.75rem;
            color: #9c9186;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <main class="maintenance-card">
        <div class="logo-wrap">
            <img src="assets/images/swans_only.png" alt="Orah House" class="swans-logo" onerror="this.style.display='none'">
        </div>

        <div>
            <span class="eyebrow-pill">Private Tasting &bull; Under Curation</span>
        </div>

        <h1 class="maintenance-title">Currently Under Curation</h1>
        <span class="cursive-quote">Crafting Moments That Matter</span>

        <div class="divider-ornament">
            <span></span>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <span></span>
        </div>

        <p class="maintenance-desc">
            <?= nl2br(htmlspecialchars($downMessage)) ?>
        </p>

        <div class="actions-group">
            <a href="https://wa.me/919429693199?text=Hello%20Orah%20House%2C%20I%20would%20like%20to%20inquire%20about%20a%20table%20reservation." target="_blank" rel="noopener" class="btn-concierge">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                    <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2z"/>
                </svg>
                <span>Concierge & Reservations</span>
            </a>

            <?php if ($isAltLive): ?>
                <a href="<?= htmlspecialchars($altPage) ?>" class="btn-alt">
                    <span><?= htmlspecialchars($altPageName) ?> &rarr;</span>
                </a>
            <?php endif; ?>
        </div>

        <div class="brand-footer-note">
            Orah House &bull; Architectural Dining &bull; Nashik
        </div>
    </main>

</body>
</html>
