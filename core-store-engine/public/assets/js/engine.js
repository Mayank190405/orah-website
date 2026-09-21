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

    // 3. Glassmorphism Sticky Header on Scroll
    const header = document.querySelector('.store-header');
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                header.classList.add('is-scrolled');
            } else {
                header.classList.remove('is-scrolled');
            }
        }, { passive: true });
    }
    
    // 4. Parallax Typography Background Effect
    const parallaxWords = document.querySelectorAll('#parallaxType .giant-word');
    if (parallaxWords.length > 0) {
        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;
            // Only calculate if within first 1000px of scroll for performance
            if (scrollY < 1000) {
                requestAnimationFrame(() => {
                    parallaxWords.forEach(word => {
                        const speed = parseFloat(word.getAttribute('data-speed')) || 0.2;
                        // Move up on scroll to create depth
                        const yOffset = -(scrollY * speed);
                        word.style.setProperty('--parallax-y', `${yOffset}px`);
                    });
                });
            }
        }, { passive: true });
    }

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
});
