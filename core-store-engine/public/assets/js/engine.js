document.addEventListener('DOMContentLoaded', () => {
    // 1. Scroll-triggered Entry Animations
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.anim-entry').forEach(el => {
        observer.observe(el);
    });

    // 2. Interactive 3D Tilt Hover Effect
    const tiltElements = document.querySelectorAll('.hover-tilt_3d');
    
    tiltElements.forEach(el => {
        el.addEventListener('mousemove', (e) => {
            const rect = el.getBoundingClientRect();
            const x = e.clientX - rect.left; // x position within the element.
            const y = e.clientY - rect.top;  // y position within the element.
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = ((y - centerY) / centerY) * -10; // max 10 deg rotation
            const rotateY = ((x - centerX) / centerX) * 10;
            
            el.style.transform = `perspective(700px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        });
        
        el.addEventListener('mouseleave', () => {
            el.style.transform = `perspective(700px) rotateX(0deg) rotateY(0deg)`;
            el.style.transition = 'transform 0.3s ease'; // smooth return
            
            setTimeout(() => {
                el.style.transition = ''; // reset transition for next hover
            }, 300);
        });
    });

    // 3. Header Scroll & Burgundy Transition Engine
    const header = document.querySelector('.store-header');
    const aboutSection = document.querySelector('.about-section');
    const domeTransition = document.querySelector('.hero-dome-transition');

    function updateHeaderState() {
        if (!header) return;
        const scrollY = window.scrollY || window.pageYOffset;
        
        // Base scrolled state (glassmorphism over cream hero)
        if (scrollY > 20) {
            header.classList.add('is-scrolled');
        } else {
            header.classList.remove('is-scrolled');
        }

        // Burgundy header & docked dome when reaching About Us section
        if (aboutSection) {
            const headerHeight = header.offsetHeight || 70;
            const aboutRect = aboutSection.getBoundingClientRect();
            
            // Only allow docking when user has scrolled past the hero threshold
            // and the top of About Us has reached the header
            const minScrollPastHero = (window.innerHeight || 700) * 0.45;
            const isScrolledPastHero = scrollY > minScrollPastHero;
            const isAtHeader = aboutRect.top <= headerHeight + 5;
            const isBeforeAboutEnd = aboutRect.bottom > headerHeight;

            const shouldBeBurgundy = isScrolledPastHero && isAtHeader && isBeforeAboutEnd;

            if (shouldBeBurgundy) {
                header.classList.add('header-burgundy');
                document.body.classList.add('in-about-section');
            } else {
                header.classList.remove('header-burgundy');
                document.body.classList.remove('in-about-section');
            }
        }
    }

    if (header) {
        window.addEventListener('scroll', updateHeaderState, { passive: true });
        window.addEventListener('resize', updateHeaderState, { passive: true });
        updateHeaderState();
    }
    
    // 4. Architectural & Editorial Parallax Scroll Engine
    (function initLuxuryParallax() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const heroArch = document.querySelector('.hero-arch-wrapper');
        const heroStamp = document.querySelector('.hero-stamp-badge');
        const heroTopLeft = document.querySelector('.corner-top-left');
        const heroTopRight = document.querySelector('.corner-top-right');
        const heroBottomLeft = document.querySelector('.corner-bottom-left');
        const heroBottomRight = document.querySelector('.corner-bottom-right');
        const botanicalSvg = document.querySelector('.dome-botanical-svg');

        const aboutSection = document.querySelector('.about-section');
        const aboutTopCrest = document.querySelector('.about-top-crest');
        const aboutStoryCol = document.querySelector('.about-story-col');
        const aboutVisualCol = document.querySelector('.about-visual-col');
        const aboutImg = document.querySelector('.about-hero-img');
        const floatingBadge = document.querySelector('.floating-about-badge');
        const floatingCard = document.querySelector('.floating-about-card');
        const pillarCards = document.querySelectorAll('.pillar-card');

        let isTicking = false;

        function updateParallax() {
            const scrollY = window.scrollY || window.pageYOffset;
            const vh = window.innerHeight;

            // --- A. Hero Section: Stays static in background without moving ---
            // (Curtain overlay parallax is created by the Burgundy About section scrolling over it)

            // --- B. About Us Section Parallax ---
            if (aboutSection) {
                const rect = aboutSection.getBoundingClientRect();
                // Check if section is currently intersecting viewport
                if (rect.top < vh && rect.bottom > 0) {
                    // Center offset: -0.5 when entering, 0 at screen center, +0.5 when leaving
                    const progress = (vh - rect.top) / (vh + rect.height);
                    const centerOffset = progress - 0.5;

                    // Arched Window Photograph Parallax (Smooth glide inside arched frame)
                    if (aboutImg) {
                        const imgY = centerOffset * -55;
                        aboutImg.style.transform = `translate3d(0, ${imgY}px, 0) scale(1.12)`;
                    }

                    // Floating Badges (Contrasting multi-plane 3D drift)
                    if (floatingBadge) {
                        const badgeY = centerOffset * -35;
                        floatingBadge.style.transform = `translate3d(0, ${badgeY}px, 0)`;
                    }
                    if (floatingCard) {
                        const cardY = centerOffset * 30;
                        floatingCard.style.transform = `translate3d(0, ${cardY}px, 0)`;
                    }

                    // Crest elevation
                    if (aboutTopCrest) {
                        const crestY = centerOffset * -15;
                        aboutTopCrest.style.transform = `translate3d(0, ${crestY}px, 0)`;
                    }

                    // Subtle editorial column differential float
                    if (window.innerWidth > 1024) {
                        if (aboutStoryCol) {
                            aboutStoryCol.style.transform = `translate3d(0, ${centerOffset * -12}px, 0)`;
                        }
                        if (aboutVisualCol) {
                            aboutVisualCol.style.transform = `translate3d(0, ${centerOffset * 16}px, 0)`;
                        }

                        // Staggered pillar cards parallax float
                        pillarCards.forEach((card, idx) => {
                            const staggerRate = (idx === 1 ? 22 : -15);
                            card.style.transform = `translate3d(0, ${centerOffset * staggerRate}px, 0)`;
                        });
                    }
                }
            }

            isTicking = false;
        }

        window.addEventListener('scroll', () => {
            if (!isTicking) {
                requestAnimationFrame(updateParallax);
                isTicking = true;
            }
        }, { passive: true });

        // Run once initially to set baseline
        updateParallax();
    })();

    // 5. Luxury Hamburger Menu & Fullscreen Overlay Toggle
    const coffeeToggle = document.getElementById('coffeeToggle');
    const closeSidebar = document.getElementById('closeSidebar');
    const coffeeOverlay = document.getElementById('coffeeOverlay');

    function openNavMenu() {
        document.body.classList.add('coffee-menu-open');
        coffeeToggle?.classList.add('is-active');
        document.body.style.overflow = 'hidden';
    }

    function closeNavMenu() {
        document.body.classList.remove('coffee-menu-open');
        coffeeToggle?.classList.remove('is-active');
        document.body.style.overflow = '';
    }

    function toggleNavMenu() {
        if (document.body.classList.contains('coffee-menu-open')) {
            closeNavMenu();
        } else {
            openNavMenu();
        }
    }

    if (coffeeToggle) {
        coffeeToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleNavMenu();
        });
    }

    if (closeSidebar) {
        closeSidebar.addEventListener('click', (e) => {
            e.stopPropagation();
            closeNavMenu();
        });
    }

    // Close on click outside menu contents
    if (coffeeOverlay) {
        coffeeOverlay.addEventListener('click', (e) => {
            if (e.target === coffeeOverlay || e.target.classList.contains('overlay-bg-image') || e.target.classList.contains('overlay-blur')) {
                closeNavMenu();
            }
        });
    }

    // Close when clicking any menu link
    document.querySelectorAll('.overlay-nav-menu a').forEach(link => {
        link.addEventListener('click', () => {
            closeNavMenu();
        });
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && document.body.classList.contains('coffee-menu-open')) {
            closeNavMenu();
        }
    });

    // 6. Seamless Curved Arch Marquee Animation (Endless Loop)
    const marqueePath = document.getElementById('domeMarqueePath');
    const marqueeItem = document.getElementById('marqueeItem');
    if (marqueePath) {
        let offset = 0;
        let repeatWidth = 0;

        function updateRepeatWidth() {
            try {
                if (marqueeItem) {
                    const itemLen = marqueeItem.getComputedTextLength();
                    if (itemLen > 50) {
                        repeatWidth = itemLen;
                        return;
                    }
                }
                const totalLen = marqueePath.getComputedTextLength();
                if (totalLen > 0) {
                    repeatWidth = totalLen / 8;
                }
            } catch (e) {
                repeatWidth = 0;
            }
        }

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(updateRepeatWidth);
        }
        window.addEventListener('resize', updateRepeatWidth);
        setTimeout(updateRepeatWidth, 200);
        setTimeout(updateRepeatWidth, 600);
        setTimeout(updateRepeatWidth, 1500);

        let lastTime = performance.now();
        const speed = 48; // Smooth luxury marquee speed in SVG units per second

        function animateMarquee(now) {
            const delta = Math.min((now - lastTime) / 1000, 0.1);
            lastTime = now;

            if (repeatWidth <= 0) {
                updateRepeatWidth();
            }

            if (repeatWidth > 0) {
                offset -= speed * delta;
                while (offset <= -repeatWidth) {
                    offset += repeatWidth;
                }
                marqueePath.setAttribute('startOffset', offset);
            }

            requestAnimationFrame(animateMarquee);
        }

        requestAnimationFrame(animateMarquee);
    }

    // 7. Hero Scroll Down Indicator Click Action
    const heroScrollIndicator = document.getElementById('heroScrollIndicator');
    if (heroScrollIndicator) {
        heroScrollIndicator.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.getElementById('story') || document.getElementById('catalog');
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
});
