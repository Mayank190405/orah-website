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
        
        html {
            scroll-behavior: smooth;
        }
        
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
                    <path id="stampCirclePath" d="M 80, 20 a 60,60 0 1,1 0,120 a 60,60 0 1,1 0,-120" fill="none" />
                    <text class="stamp-text">
                        <textPath href="#stampCirclePath" startOffset="0%" textLength="376.99" lengthAdjust="spacing">
                            COFFEE &nbsp;•&nbsp; PEOPLE &nbsp;•&nbsp; COFFEE &nbsp;•&nbsp; PEOPLE &nbsp;•&nbsp;
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
                <path id="domeCurvePath" d="M -1200, 150 C -700, 130 -200, 106 0, 92 C 200, 78 450, 60 700, 60 C 950, 60 1200, 78 1400, 92 C 1600, 106 2800, 130 4200, 150" fill="none" />
                <text class="dome-curve-text">
                    <textPath id="domeMarqueePath" href="#domeCurvePath" startOffset="0">
                        <tspan id="marqueeItem">DESSERTS &nbsp; · &nbsp; COFFEE &nbsp; · &nbsp; CONVERSATIONS &nbsp; · &nbsp; PASTA &nbsp; · &nbsp; GOOD TIMES &nbsp; · &nbsp; </tspan>
                        <tspan>DESSERTS &nbsp; · &nbsp; COFFEE &nbsp; · &nbsp; CONVERSATIONS &nbsp; · &nbsp; PASTA &nbsp; · &nbsp; GOOD TIMES &nbsp; · &nbsp; </tspan>
                        <tspan>DESSERTS &nbsp; · &nbsp; COFFEE &nbsp; · &nbsp; CONVERSATIONS &nbsp; · &nbsp; PASTA &nbsp; · &nbsp; GOOD TIMES &nbsp; · &nbsp; </tspan>
                        <tspan>DESSERTS &nbsp; · &nbsp; COFFEE &nbsp; · &nbsp; CONVERSATIONS &nbsp; · &nbsp; PASTA &nbsp; · &nbsp; GOOD TIMES &nbsp; · &nbsp; </tspan>
                        <tspan>DESSERTS &nbsp; · &nbsp; COFFEE &nbsp; · &nbsp; CONVERSATIONS &nbsp; · &nbsp; PASTA &nbsp; · &nbsp; GOOD TIMES &nbsp; · &nbsp; </tspan>
                        <tspan>DESSERTS &nbsp; · &nbsp; COFFEE &nbsp; · &nbsp; CONVERSATIONS &nbsp; · &nbsp; PASTA &nbsp; · &nbsp; GOOD TIMES &nbsp; · &nbsp; </tspan>
                        <tspan>DESSERTS &nbsp; · &nbsp; COFFEE &nbsp; · &nbsp; CONVERSATIONS &nbsp; · &nbsp; PASTA &nbsp; · &nbsp; GOOD TIMES &nbsp; · &nbsp; </tspan>
                        <tspan>DESSERTS &nbsp; · &nbsp; COFFEE &nbsp; · &nbsp; CONVERSATIONS &nbsp; · &nbsp; PASTA &nbsp; · &nbsp; GOOD TIMES &nbsp; · &nbsp; </tspan>
                    </textPath>
                </text>
            </svg>
            
            <a href="#story" class="dome-scroll-indicator" id="heroScrollIndicator" aria-label="Scroll down to Our Story">
                <span class="scroll-word">SCROLL</span>
                <div class="scroll-arrow-line">
                    <svg viewBox="0 0 20 60" fill="none" stroke="currentColor" stroke-width="1.6">
                        <line x1="10" y1="0" x2="10" y2="52" />
                        <polyline points="4,44 10,52 16,44" />
                    </svg>
                </div>
            </a>

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

    <!-- Architectural About Us Overlay Parallax Section -->
    <section class="about-parallax-section" id="story">
        <!-- Top Decorative Architectural Line & Crest -->
        <div class="about-top-crest">
            <span class="crest-line"></span>
            <div class="crest-badge">
                <img src="assets/images/swans_only.png" alt="Orah Emblem" class="crest-swans">
                <span class="crest-tagline">EST. 2024 &bull; NASHIK</span>
            </div>
            <span class="crest-line"></span>
        </div>

        <div class="about-container">
            <!-- Main Section Header -->
            <div class="about-header">
                <span class="about-eyebrow">OUR HERITAGE &amp; PHILOSOPHY</span>
                <h2 class="about-main-title">A SANCTUARY FOR SLOW LIVING &amp; MEANINGFUL CONNECTIONS</h2>
                <p class="about-subtitle">
                    Born in the heart of Nashik, Orah House was envisioned as a timeless retreat where specialty coffee, honest culinary craft, and architectural serenity converge.
                </p>
            </div>

            <!-- 2-Column Editorial Story Showcase -->
            <div class="about-editorial-grid">
                <!-- Left Column: Rich Storytelling -->
                <div class="about-story-col">
                    <div class="story-card-inner">
                        <span class="story-chapter">CHAPTER I &mdash; THE VISION</span>
                        <h3 class="story-heading">More Than A Café. A Gathering Place for the Senses.</h3>
                        
                        <p class="story-lead">
                            In a world rushing forward, Orah is an invitation to pause. From the first pour of single-origin espresso at daybreak to evening conversations over freshly spun pasta, every detail is curated with stillness in mind.
                        </p>
                        
                        <p class="story-body">
                            Our coffee journey begins with micro-lot beans roasted to accentuate natural terroir, while our kitchen champions artisanal techniques &mdash; long-fermentation sourdoughs, handmade sauces, and seasonal produce rooted in Maharashtra's fertile soils.
                        </p>

                        <!-- Quote Block -->
                        <blockquote class="about-quote">
                            &ldquo;Food that nourishes the spirit, coffee that awakens the mind, and spaces that invite you to stay a little longer.&rdquo;
                            <cite>&mdash; The Curators of Orah House</cite>
                        </blockquote>

                        <!-- Signature Metrics Row -->
                        <div class="about-signature-row">
                            <div class="signature-item">
                                <span class="sig-number">100%</span>
                                <span class="sig-label">Specialty Grade Arabica</span>
                            </div>
                            <div class="signature-divider"></div>
                            <div class="signature-item">
                                <span class="sig-number">Daily</span>
                                <span class="sig-label">Hand-Rolled Artisanal Fare</span>
                            </div>
                            <div class="signature-divider"></div>
                            <div class="signature-item">
                                <span class="sig-number">Nashik</span>
                                <span class="sig-label">Our Home &amp; Inspiration</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Visual Showcase Frame -->
                <div class="about-visual-col">
                    <div class="about-visual-frame">
                        <div class="frame-arch-image">
                            <img src="assets/images/hero_cafe.png" alt="Orah House Ambiance" class="about-hero-img">
                            <div class="frame-overlay-glow"></div>
                        </div>
                        
                        <!-- Floating Architectural Badges -->
                        <div class="floating-about-badge badge-top-right">
                            <span class="badge-accent">&bull; SPECIALTY ROASTS &bull;</span>
                            <span class="badge-title">ARTISANAL BATCHES</span>
                        </div>

                        <div class="floating-about-card card-bottom-left">
                            <div class="floating-card-icon">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>
                            </div>
                            <div class="floating-card-text">
                                <span class="fcard-title">Crafted with Intention</span>
                                <span class="fcard-desc">Every cup brewed with calibrated temperature &amp; grind profile.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3 Pillars of Orah Experience -->
            <div class="about-pillars-wrapper">
                <div class="pillars-header">
                    <span class="pillars-eyebrow">THE EXPERIENCE</span>
                    <h3 class="pillars-title">THREE PILLARS OF ORAH</h3>
                    <div class="pillars-line"></div>
                </div>

                <div class="about-pillars-grid">
                    <!-- Pillar 1 -->
                    <div class="pillar-card">
                        <div class="pillar-num">01</div>
                        <h4 class="pillar-name">Artisanal Brews</h4>
                        <p class="pillar-desc">
                            Sourced from high-altitude estates, our coffees are pulled on precision machines and hand-dripped through V60 pour-overs to celebrate distinct botanical and cocoa notes.
                        </p>
                        <span class="pillar-tag">POUR-OVER &bull; ESPRESSO &bull; COLD DRIP</span>
                    </div>

                    <!-- Pillar 2 -->
                    <div class="pillar-card">
                        <div class="pillar-num">02</div>
                        <h4 class="pillar-name">Culinary Devotion</h4>
                        <p class="pillar-desc">
                            From hand-stretched mozzarella flatbreads and slow-infused beetroot pasta to golden brioche toasts, every dish is an ode to fresh ingredients and European culinary traditions.
                        </p>
                        <span class="pillar-tag">HANDMADE PASTAS &bull; FLATBREADS &bull; SALADS</span>
                    </div>

                    <!-- Pillar 3 -->
                    <div class="pillar-card">
                        <div class="pillar-num">03</div>
                        <h4 class="pillar-name">Architectural Calm</h4>
                        <p class="pillar-desc">
                            High archways, soothing earth tones, and warm natural lighting create an unhurried atmosphere tailored for peaceful solo work, quiet reading, or heartfelt gatherings.
                        </p>
                        <span class="pillar-tag">CURATED AMBIANCE &bull; TIMELESS DESIGN</span>
                    </div>
                </div>
            </div>

            <!-- Call to Action Banner leading into Catalog -->
            <div class="about-cta-banner">
                <div class="cta-banner-content">
                    <span class="cta-banner-eyebrow">TASTE THE PASSION</span>
                    <h3 class="cta-banner-title">READY TO EXPERIENCE ORAH?</h3>
                    <p class="cta-banner-desc">Explore our seasonal selection of artisanal coffees, savory delicacies, and sweet creations.</p>
                    <a href="#catalog" class="about-explore-btn">
                        <span>EXPLORE FULL MENU</span>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17l9.2-9.2M17 17V8H8"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="container" id="catalog">
        <div class="store-catalog-grid">
            <?php foreach ($catalog as $index => $product): ?>
                <?= $renderer->render($product, $index) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Luxury Burgundy Architectural Footer -->
    <footer class="store-footer" id="footer">
        <!-- Top Arch Transition Accent -->
        <div class="footer-top-accent">
            <div class="footer-arch-divider">
                <span class="f-arch-line"></span>
                <div class="f-arch-badge">
                    <img src="assets/images/swans_only.png" alt="Orah Emblem" class="f-swans">
                    <span class="f-badge-text">ORAH HOUSE &bull; NASHIK</span>
                </div>
                <span class="f-arch-line"></span>
            </div>
        </div>

        <div class="footer-main-container">
            <div class="footer-grid">
                <!-- Column 1: Brand Essence -->
                <div class="footer-col footer-col-brand">
                    <div class="footer-brand-header">
                        <h3 class="footer-brand-title">ORAH HOUSE</h3>
                        <span class="footer-brand-subtitle">CAFÉ &bull; ROASTERY &bull; PATISSERIE</span>
                    </div>
                    <p class="footer-brand-desc">
                        A sanctuary where slow-brewed specialty coffee, hand-rolled artisanal fare, and serene architectural design come together in Nashik.
                    </p>
                    <div class="footer-timings">
                        <span class="timings-label">HOURS OF SANCTUARY</span>
                        <span class="timings-time">Monday &ndash; Sunday: 8:00 AM &ndash; 11:30 PM</span>
                        <span class="timings-note">Breakfast &bull; All-Day Dining &bull; Late Coffee</span>
                    </div>
                </div>

                <!-- Column 2: Navigation -->
                <div class="footer-col footer-col-nav">
                    <h4 class="footer-heading">NAVIGATION</h4>
                    <ul class="footer-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#story">Our Story &amp; Philosophy</a></li>
                        <li><a href="#catalog">Artisanal Menu</a></li>
                        <li><a href="#experiences">The Roastery Experience</a></li>
                        <li><a href="#reserve">Reserve A Table</a></li>
                        <li><a href="#contact">Private Gatherings</a></li>
                    </ul>
                </div>

                <!-- Column 3: The Sanctuary (Location) -->
                <div class="footer-col footer-col-location">
                    <h4 class="footer-heading">LOCATION &amp; CONTACT</h4>
                    <address class="footer-address">
                        <p class="address-line">Gangapur Road, Near Serene Enclave,</p>
                        <p class="address-line">Anandwalli, Nashik, Maharashtra 422013</p>
                    </address>
                    <div class="footer-contact-details">
                        <a href="tel:+919876543210" class="contact-link">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <span>+91 98765 43210</span>
                        </a>
                        <a href="mailto:hello@orahhouse.com" class="contact-link">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <span>hello@orahhouse.com</span>
                        </a>
                    </div>
                    <a href="https://maps.google.com" target="_blank" rel="noopener" class="footer-directions-btn">
                        <span>GET DIRECTIONS &rarr;</span>
                    </a>
                </div>

                <!-- Column 4: Newsletter & Club -->
                <div class="footer-col footer-col-club">
                    <h4 class="footer-heading">THE ORAH CIRCLE</h4>
                    <p class="club-desc">
                        Subscribe for invitations to private coffee cupping sessions, seasonal chef tastings, and architectural stories.
                    </p>
                    <form class="footer-newsletter-form" onsubmit="event.preventDefault(); this.querySelector('button').innerHTML='JOINED &check;';">
                        <div class="input-wrap">
                            <input type="email" placeholder="Your email address..." required aria-label="Email address for newsletter">
                            <button type="submit" aria-label="Subscribe to newsletter">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </form>
                    <div class="footer-social-cluster">
                        <a href="#" aria-label="Instagram" class="f-social-btn">
                            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="1.6" fill="none"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                        </a>
                        <a href="#" aria-label="Spotify" class="f-social-btn">
                            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="1.6" fill="none"><circle cx="12" cy="12" r="10"></circle><path d="M8 11.5c3.5-1 7.5-.5 10.5 1.5"></path><path d="M9 14.5c2.5-.7 5.5-.3 8 1"></path><path d="M7 8.5c4.5-1.2 9.5-.7 13.5 1.5"></path></svg>
                        </a>
                        <a href="#" aria-label="Facebook" class="f-social-btn">
                            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="1.6" fill="none"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bottom Legal & Hallmark Bar -->
            <div class="footer-bottom-bar">
                <div class="f-bottom-left">
                    <p class="copyright-text">&copy; <?= date('Y') ?> Orah House Café &amp; Roastery. All Rights Reserved.</p>
                </div>
                <div class="f-bottom-center">
                    <span class="hallmark-mantra">EAT &bull; SIP &bull; GATHER</span>
                </div>
                <div class="f-bottom-right">
                    <a href="#privacy">Privacy</a>
                    <span class="bullet-dot">&bull;</span>
                    <a href="#terms">Terms</a>
                    <span class="bullet-dot">&bull;</span>
                    <a href="#home" class="back-to-top">Back to Top &uarr;</a>
                </div>
            </div>
        </div>
    </footer>

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
