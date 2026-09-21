<?php
require_once __DIR__ . '/../engine/JsonStorage.php';
require_once __DIR__ . '/../engine/ThemeEngine.php';
require_once __DIR__ . '/../engine/CardRenderer.php';

use CoreStore\Engine\JsonStorage;
use CoreStore\Engine\ThemeEngine;
use CoreStore\Engine\CardRenderer;

// Initialize Core Engine Components
$storage = new JsonStorage(__DIR__ . '/../data/settings.json');
$settings = $storage->read();

$themeEngine = new ThemeEngine($settings);
$renderer = new CardRenderer($themeEngine);

// Fetch Catalog Data
$catalogStorage = new JsonStorage(__DIR__ . '/../data/catalog.json');
$catalog = $catalogStorage->read();

// Prepare Header Data
$brand = $settings['brand'] ?? ['type' => 'text', 'text' => 'Store'];
$menu = $settings['menu'] ?? [];
usort($menu, function($a, $b) {
    return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
});

// Prepare Hero Data
$hero = $settings['hero'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($brand['text'] ?? 'Storefront') ?></title>
    
    <!-- Inject Dynamic CSS Custom Properties -->
    <style>
        <?= $themeEngine->renderCssCustomProperties() ?>
        
        body {
            font-family: var(--font-body);
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            box-sizing: border-box;
        }
    </style>
    
    <!-- Load Core Engine Styles -->
    <link rel="stylesheet" href="assets/css/engine.css">
    
    <!-- Preconnect and Load Google Fonts: Cormorant Garamond (italic luxury serif), Manrope (body & architectural uppercase), Playfair Display -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400;1,500;1,600;1,700&family=Manrope:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
</head>
<body>

    <header class="store-header">
        <div class="header-container">
            <a href="#" class="brand-logo">
                <img src="<?= htmlspecialchars($brand['logo_url'] ?? 'assets/images/logo.png') ?>" alt="Orah House" class="header-logo-img">
            </a>
            
            <nav class="nav-menu">
                <ul>
                    <?php foreach ($menu as $index => $item): ?>
                        <li><a href="<?= htmlspecialchars($item['url'] ?? '#') ?>" class="<?= $index === 0 ? 'active' : '' ?>"><?= htmlspecialchars($item['label'] ?? '') ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            
            <div class="header-actions">
                <a href="#visit" class="header-visit-btn">Visit Us &rarr;</a>
                <button class="coffee-menu-toggle" id="coffeeToggle" aria-label="Toggle navigation menu" type="button">
                    <span class="circle-line"></span>
                    <span class="circle-line"></span>
                    <span class="circle-line"></span>
                </button>
            </div>
        </div>
    </header>

    <?php if ($hero): ?>
    <section class="hero-section">
        <!-- Top Editorial Corner Callouts -->
        <div class="hero-corner-callout corner-top-left">
            <span>GOOD FOOD</span>
            <span>BRIGHTER</span>
            <span>CONVERSATIONS</span>
            <div class="callout-underline"></div>
        </div>

        <div class="hero-corner-callout corner-top-right">
            <span>A CAFÉ</span>
            <span>FOR EVERY</span>
            <span>OCCASION</span>
            <div class="callout-underline"></div>
        </div>

        <!-- Central Architectural Framework & Content Container -->
        <div class="hero-arch-wrapper">
            <!-- Architectural SVG Framework -->
            <svg class="arch-frame-svg" viewBox="0 0 680 960" preserveAspectRatio="none" aria-hidden="true">
                <!-- Top vertical needle drop & circle -->
                <line x1="340" y1="0" x2="340" y2="70" class="arch-line" />
                <circle cx="340" cy="76" r="5.5" class="arch-line" fill="#FBF6EE" />
                
                <!-- Grand Archway framing center typography -->
                <path d="M 40, 960 L 40, 360 C 40, 85 640, 85 640, 360 L 640, 960" class="arch-line" />
                
                <!-- Mid needle below button -->
                <line x1="340" y1="630" x2="340" y2="700" class="arch-line" />
                
                <!-- Outward Sweeping Lower Arcs -->
                <path d="M 340, 960 C 270, 830 100, 770 -200, 790" class="arch-line" />
                <path d="M 340, 960 C 410, 830 580, 770 880, 790" class="arch-line" />
                <path d="M -160, 850 C 80, 850 250, 900 340, 960 C 430, 900 600, 850 840, 850" class="arch-line" />
            </svg>

            <!-- Top Arch Badge: MORE THAN A CAFÉ -->
            <div class="hero-top-badge" aria-hidden="true">
                <span>MORE</span>
                <span>THAN</span>
                <span>A CAFÉ</span>
            </div>

            <!-- Left Step Indicator along Arch -->
            <div class="hero-step-indicator" aria-hidden="true">
                <span class="step-num active">01</span>
                <span class="step-bar"></span>
                <span class="step-num">02</span>
                <span class="step-num">03</span>
                <span class="step-num">04</span>
            </div>

            <!-- Right Side Editorial Text along Arch -->
            <div class="hero-side-editorial" aria-hidden="true">
                <span>GOOD FOOD</span>
                <span>GREAT COMPANY</span>
                <span>MEMORABLE MOMENTS</span>
            </div>

            <!-- Central Hero Content -->
            <div class="hero-container">
                <div class="hero-content">
                    <?php if (!empty($hero['title'])): ?>
                        <h1 class="hero-title"><?= nl2br(htmlspecialchars($hero['title'])) ?></h1>
                    <?php endif; ?>
                    
                    <?php if (!empty($hero['description'])): ?>
                        <p class="hero-description"><?= nl2br(htmlspecialchars($hero['description'])) ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($hero['button_text'])): ?>
                        <a href="#catalog" class="hero-btn">
                            <span><?= htmlspecialchars($hero['button_text']) ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Circular Rotating Stamp Badge -->
            <div class="hero-stamp-badge" aria-hidden="true">
                <svg class="stamp-svg" viewBox="0 0 160 160">
                    <path id="stampCirclePath" d="M 80, 80 m -60, 0 a 60,60 0 1,1 120,0 a 60,60 0 1,1 -120,0" fill="none" />
                    <text class="stamp-text">
                        <textPath href="#stampCirclePath" startOffset="0%">
                            • COFFEE • PEOPLE • COFFEE • PEOPLE •
                        </textPath>
                    </text>
                </svg>
                <div class="stamp-emblem">
                    <img src="assets/images/swans_only.png" alt="Orah Emblem">
                </div>
            </div>
        </div>

        <!-- Bottom Left Corner Callout -->
        <div class="hero-corner-callout corner-bottom-left">
            <span>GOOD</span>
            <span>FOOD</span>
            <div class="callout-underline"></div>
        </div>

        <!-- Bottom Right Corner Callout -->
        <div class="hero-corner-callout corner-bottom-right">
            <span>GREAT</span>
            <span>COMPANY</span>
            <div class="callout-underline"></div>
        </div>

        <!-- Burgundy Arch Dome Transition Shape -->
        <div class="hero-dome-transition">
            <svg class="dome-curve-svg" viewBox="0 0 1400 180" preserveAspectRatio="none" aria-hidden="true">
                <path id="domeCurvePath" d="M -400, 132 C -50, 108 300, 60 700, 60 C 1100, 60 1450, 108 1800, 132" fill="none" />
                <text class="dome-curve-text">
                    <textPath id="domeMarqueePath" href="#domeCurvePath" startOffset="0">
                        DESSERTS &nbsp; · &nbsp; COFFEE &nbsp; · &nbsp; CONVERSATIONS &nbsp; · &nbsp; PASTA &nbsp; · &nbsp; GOOD TIMES &nbsp; · &nbsp; DESSERTS &nbsp; · &nbsp; COFFEE &nbsp; · &nbsp; CONVERSATIONS &nbsp; · &nbsp; PASTA &nbsp; · &nbsp; GOOD TIMES &nbsp; · &nbsp; DESSERTS &nbsp; · &nbsp; COFFEE &nbsp; · &nbsp; CONVERSATIONS &nbsp; · &nbsp; PASTA &nbsp; · &nbsp; GOOD TIMES &nbsp; · &nbsp; DESSERTS &nbsp; · &nbsp; COFFEE &nbsp; · &nbsp; CONVERSATIONS &nbsp; · &nbsp; PASTA &nbsp; · &nbsp; GOOD TIMES &nbsp; · &nbsp; DESSERTS &nbsp; · &nbsp; COFFEE &nbsp; · &nbsp; CONVERSATIONS &nbsp; · &nbsp; PASTA &nbsp; · &nbsp; GOOD TIMES &nbsp; · &nbsp; DESSERTS &nbsp; · &nbsp; COFFEE &nbsp; · &nbsp; CONVERSATIONS &nbsp; · &nbsp; PASTA &nbsp; · &nbsp; GOOD TIMES &nbsp; · &nbsp;
                    </textPath>
                </text>
            </svg>
            
            <div class="dome-scroll-indicator">
                <span class="scroll-word">SCROLL</span>
                <div class="scroll-arrow-line">
                    <svg viewBox="0 0 20 60" fill="none" stroke="currentColor" stroke-width="1.6">
                        <line x1="10" y1="0" x2="10" y2="52" />
                        <polyline points="4,44 10,52 16,44" />
                    </svg>
                </div>
            </div>

            <div class="dome-footer-hallmark">
                <div class="hallmark-divider"></div>
                <div class="hallmark-text">
                    <span class="hallmark-brand">ORAH HOUSE</span>
                    <span class="hallmark-city">NASHIK</span>
                </div>
                <div class="hallmark-divider"></div>
            </div>

            <!-- Faint Botanical Line Art on Bottom Right -->
            <svg class="dome-botanical-svg" viewBox="0 0 180 180" fill="none" stroke="rgba(201, 154, 104, 0.35)" stroke-width="1.2" aria-hidden="true">
                <path d="M 140,180 C 140,110 80,70 10,110 C 80,150 120,175 140,180 Z" />
                <path d="M 140,180 C 180,100 125,50 60,80 C 105,130 130,170 140,180 Z" />
                <path d="M 140,180 C 200,135 190,75 120,70 C 120,125 135,165 140,180 Z" />
            </svg>
        </div>
    </section>
    <?php endif; ?>

    <div class="container" id="catalog">
        <div class="store-catalog-grid">
            <?php foreach ($catalog as $index => $product): ?>
                <?= $renderer->render($product, $index) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- The Full-Screen Premium Coffee Bar Overlay -->
    <div class="coffee-bar-overlay" id="coffeeOverlay" aria-modal="true" role="dialog">
        <div class="overlay-bg-image"></div>
        <div class="overlay-blur"></div>
        
        <div class="overlay-header">
            <div class="overlay-brand">
                <img src="<?= htmlspecialchars($brand['logo_url'] ?? 'assets/images/logo.png') ?>" alt="Orah House" class="overlay-logo-img">
            </div>
            <button class="close-overlay-btn" id="closeSidebar" aria-label="Close Menu" type="button">
                <svg viewBox="0 0 24 24" width="26" height="26" stroke="currentColor" stroke-width="1.6" fill="none"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        <nav class="overlay-nav-menu">
            <ul>
                <li style="--anim-delay: 0.06s"><a href="#home">Home</a></li>
                <li style="--anim-delay: 0.12s"><a href="#menu">Menu</a></li>
                <li style="--anim-delay: 0.18s"><a href="#story">Our Story</a></li>
                <li style="--anim-delay: 0.24s"><a href="#experiences">Experiences</a></li>
                <li style="--anim-delay: 0.30s"><a href="#gallery">Gallery</a></li>
                <li style="--anim-delay: 0.36s"><a href="#contact">Contact</a></li>
                <li style="--anim-delay: 0.44s" class="cta-li"><a href="#reserve" class="reserve-cta">Reserve a Table &rarr;</a></li>
            </ul>
        </nav>
        
        <div class="overlay-footer">
            <div class="overlay-tagline">EAT. SIP. GATHER. · NASHIK</div>
            <div class="overlay-socials">
                <a href="#" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="1.5" fill="none"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                </a>
                <a href="#" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="1.5" fill="none"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                </a>
            </div>
            <p>&copy; <?= date('Y') ?> Orah House. All Rights Reserved.</p>
        </div>
    </div>

    <!-- Load Core Engine JS -->
    <script src="assets/js/engine.js"></script>
</body>
</html>
