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
    
    <!-- Preconnect and Load Google Fonts: Italianno (subheading), Manrope (body), Playfair Display (editorial serif) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Italianno&family=Manrope:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
</head>
<body>

    <header class="store-header">
        <div class="header-container">
            <div class="brand-logo">
                <?php if (($brand['type'] ?? 'text') === 'image' && !empty($brand['logo_url'])): ?>
                    <img src="<?= htmlspecialchars($brand['logo_url']) ?>" alt="Logo">
                <?php else: ?>
                    <div class="brand-title"><?= htmlspecialchars($brand['text'] ?? 'ORAH') ?></div>
                    <?php if (!empty($brand['subtext'])): ?>
                        <div class="brand-subtitle"><?= nl2br(htmlspecialchars($brand['subtext'])) ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <nav class="nav-menu">
                <ul>
                    <?php foreach ($menu as $index => $item): ?>
                        <li><a href="<?= htmlspecialchars($item['url'] ?? '#') ?>" class="<?= $index === 0 ? 'active' : '' ?>"><?= htmlspecialchars($item['label'] ?? '') ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            
            <div class="coffee-menu-toggle" id="coffeeToggle">
                <!-- SVG container for the animated hamburger/cup -->
                <svg class="coffee-anim-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <!-- Hamburger Lines -->
                    <g class="burger-lines">
                        <line x1="20" y1="30" x2="80" y2="30" />
                        <line x1="20" y1="50" x2="80" y2="50" />
                        <line x1="20" y1="70" x2="80" y2="70" />
                    </g>
                    <!-- Coffee Cup Outline (Initially hidden/undrawn) -->
                    <g class="cup-outline">
                        <!-- Cup Body -->
                        <path class="cup-path" d="M30,30 L30,70 C30,80 40,85 50,85 C60,85 70,80 70,70 L70,30 Z" />
                        <!-- Cup Handle -->
                        <path class="cup-handle" d="M70,40 C80,40 85,45 85,55 C85,65 80,70 70,70" />
                    </g>
                    <!-- Liquid Mask/Fill -->
                    <clipPath id="liquid-mask">
                        <path d="M32,32 L32,70 C32,78 40,83 50,83 C60,83 68,78 68,70 L68,32 Z" />
                    </clipPath>
                    <rect class="coffee-liquid-fill" x="30" y="30" width="40" height="55" clip-path="url(#liquid-mask)" />
                    <!-- Steam Lines -->
                    <g class="steam-lines">
                        <path class="steam steam-1" d="M40,25 Q35,15 45,5" />
                        <path class="steam steam-2" d="M50,25 Q45,15 55,5" />
                        <path class="steam steam-3" d="M60,25 Q55,15 65,5" />
                    </g>
                </svg>
            </div>
            
            <div class="header-actions">
                <a href="#" class="action-icon" aria-label="Search">
                    <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="1.5" fill="none"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </a>
                <a href="#visit" class="visit-btn">Visit Us &rarr;</a>
                <div class="header-divider"></div>
                <div class="social-icons">
                    <a href="#" class="action-icon" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="1.5" fill="none"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                    </a>
                    <a href="#" class="action-icon" aria-label="Facebook">
                        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="1.5" fill="none"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <?php if ($hero): ?>
    <section class="hero-section">
        <!-- Top Editorial Corner Callouts (From Reference) -->
        <div class="hero-callout hero-callout-left">
            <span>GOOD FOOD</span>
            <span>BRIGHTER</span>
            <span>CONVERSATIONS</span>
            <div class="callout-line"></div>
        </div>

        <div class="hero-callout hero-callout-right">
            <span>A CAFÉ</span>
            <span>FOR EVERY</span>
            <span>OCCASION</span>
            <div class="callout-line"></div>
        </div>

        <!-- Ambient Watermark Words (From Reference) -->
        <div class="hero-watermark-words" aria-hidden="true">
            <span class="watermark-word wm-coffee">COFFEE</span>
            <span class="watermark-word wm-pasta">PASTA</span>
            <span class="watermark-word wm-desserts">DESSERTS</span>
            <span class="watermark-word wm-conversation">CONVERSATION</span>
            <div class="watermark-sub wm-good-food">
                <span>GOOD</span>
                <span>FOOD</span>
            </div>
            <div class="watermark-sub wm-great-company">
                <span>GREAT</span>
                <span>COMPANY</span>
            </div>
        </div>

        <!-- Concentric Architectural & Celestial SVG Arcs (From Reference) -->
        <svg class="hero-celestial-svg" viewBox="0 0 1000 1000" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            <!-- Sweeping curved lines framing the central typography -->
            <path d="M 500,180 C 380,180 180,300 110,540" class="celestial-arc" />
            <path d="M 500,180 C 640,180 760,230 880,380" class="celestial-arc" />
            
            <!-- Concentric arcs anchored at bottom center -->
            <circle cx="500" cy="1000" r="280" class="celestial-arc" />
            <circle cx="500" cy="1000" r="480" class="celestial-arc" />
            <circle cx="500" cy="1000" r="820" class="celestial-arc" />
            
            <!-- Center Lower Needle Line -->
            <line x1="500" y1="630" x2="500" y2="700" class="needle-line" />
            <line x1="500" y1="785" x2="500" y2="855" class="needle-line" />
        </svg>

        <!-- Center Lower Compass Needle Badge -->
        <div class="hero-needle-badge" aria-hidden="true">
            <span>MORE</span>
            <span>THAN</span>
            <span>A CAFÉ</span>
        </div>

        <!-- Content Container (above the curve) -->
        <div class="hero-container">
            <div class="hero-content">
                <?php if (!empty($hero['title'])): ?>
                    <h1 class="hero-title"><?= nl2br(htmlspecialchars($hero['title'])) ?></h1>
                <?php endif; ?>
                
                <?php if (!empty($hero['description'])): ?>
                    <p class="hero-description"><?= htmlspecialchars($hero['description']) ?></p>
                <?php endif; ?>
                
                <?php if (!empty($hero['button_text'])): ?>
                    <a href="#catalog" class="hero-btn">
                        <span><?= htmlspecialchars($hero['button_text']) ?></span>
                    </a>
                <?php endif; ?>
                
                <?php if (!empty($hero['footer_note'])): ?>
                    <div class="hero-footer-note"><?= nl2br(htmlspecialchars($hero['footer_note'])) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Burgundy Transition Shape with Curved Text along the Arch (From Reference) -->
        <div class="hero-transition-bg">
            <svg class="arch-curve-svg" viewBox="0 0 1400 240" preserveAspectRatio="none" aria-hidden="true">
                <path id="arch-curve-path" d="M -100,190 Q 700,50 1500,190" fill="none" />
                <text class="arch-svg-text">
                    <textPath href="#arch-curve-path" startOffset="50%" text-anchor="middle">
                        PASTA &nbsp;&nbsp;&nbsp; DESSERTS &nbsp;&nbsp;&nbsp; CONVERSATION &nbsp;&nbsp;&nbsp; COFFEE &nbsp;&nbsp;&nbsp; GATHER &nbsp;&nbsp;&nbsp; PASTA &nbsp;&nbsp;&nbsp; DESSERTS
                    </textPath>
                </text>
            </svg>
            
            <div class="scroll-down-indicator">
                <span>SCROLL</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
            </div>
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

    <!-- Coffee Liquid Background Overlay (Kept for legacy or fallback, but replaced by full screen overlay) -->
    <!-- The Full-Screen Premium Coffee Bar Overlay -->
    <div class="coffee-bar-overlay" id="coffeeOverlay">
        <div class="overlay-bg-image"></div>
        <div class="overlay-blur"></div>
        
        <div class="overlay-header">
            <button class="close-overlay-btn" id="closeSidebar">
                <svg viewBox="0 0 24 24" width="32" height="32" stroke="currentColor" stroke-width="1.5" fill="none"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        <nav class="overlay-nav-menu">
            <ul>
                <li style="--anim-delay: 0.1s"><a href="#home">Home</a></li>
                <li style="--anim-delay: 0.2s"><a href="#menu">Menu</a></li>
                <li style="--anim-delay: 0.3s"><a href="#story">Our Story</a></li>
                <li style="--anim-delay: 0.4s"><a href="#gallery">Gallery</a></li>
                <li style="--anim-delay: 0.5s"><a href="#contact">Contact</a></li>
                <li style="--anim-delay: 0.7s" class="cta-li"><a href="#reserve" class="reserve-cta">Reserve a Table</a></li>
            </ul>
        </nav>
        
        <div class="overlay-footer">
            <div class="overlay-socials">
                <a href="#" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5" fill="none"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                </a>
                <a href="#" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5" fill="none"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                </a>
            </div>
            <p>EAT. SIP. GATHER. &copy; <?= date('Y') ?></p>
        </div>
    </div>

    <!-- Load Core Engine JS -->
    <script src="assets/js/engine.js"></script>
</body>
</html>
