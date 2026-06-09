/* ================================================================
   DeutschLernen — script.js
   Features: Navbar scroll | Hero Slider | Scroll Reveal |
             Mobile Menu | Smooth Scroll | Parallax | Particles
================================================================ */

(function () {
    'use strict';

    // ================================================================
    // 1. NAVBAR — scroll-triggered glass effect
    // ================================================================
    const navbar = document.getElementById('navbar');

    function handleNavbarScroll() {
        if (!navbar) return;
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }

    window.addEventListener('scroll', handleNavbarScroll, { passive: true });
    handleNavbarScroll(); // run on load


    // ================================================================
    // 2. MOBILE MENU
    // ================================================================
    const hamburger   = document.getElementById('hamburger');
    const mobileMenu  = document.getElementById('mobileMenu');
    const mobileClose = document.getElementById('mobileClose');

    function openMobileMenu() {
        if (!mobileMenu || !hamburger) return;
        mobileMenu.classList.add('open');
        hamburger.classList.add('active');
        hamburger.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileMenu() {
        if (!mobileMenu || !hamburger) return;
        mobileMenu.classList.remove('open');
        hamburger.classList.remove('active');
        hamburger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    if (hamburger)   hamburger.addEventListener('click', openMobileMenu);
    if (mobileClose) mobileClose.addEventListener('click', closeMobileMenu);

    // Close when a mobile link is clicked
    if (mobileMenu) {
        mobileMenu.querySelectorAll('.mobile-link, .btn').forEach(function (link) {
            link.addEventListener('click', closeMobileMenu);
        });
    }

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMobileMenu();
    });


    // ================================================================
    // 3. HERO IMAGE SLIDER
    // ================================================================
    const slides = document.querySelectorAll('.hero-slider .slide');
    const dots   = document.querySelectorAll('.slider-dots .dot');
    let current  = 0;
    let sliderTimer = null;

    function goToSlide(index) {
        if (!slides.length) return;

        // Remove active from current
        slides[current].classList.remove('active');
        if (dots[current]) {
            dots[current].classList.remove('active');
            dots[current].setAttribute('aria-selected', 'false');
        }

        // Set new current
        current = (index + slides.length) % slides.length;
        slides[current].classList.add('active');
        if (dots[current]) {
            dots[current].classList.add('active');
            dots[current].setAttribute('aria-selected', 'true');
        }
    }

    function nextSlide() {
        goToSlide(current + 1);
    }

    function startSlider() {
        if (!slides.length) return;
        sliderTimer = setInterval(nextSlide, 5500);
    }

    function resetSliderTimer() {
        clearInterval(sliderTimer);
        startSlider();
    }

    // Dot click navigation
    dots.forEach(function (dot, i) {
        dot.addEventListener('click', function () {
            goToSlide(i);
            resetSliderTimer();
        });
    });

    // Keyboard support for dots
    dots.forEach(function (dot, i) {
        dot.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                goToSlide(i);
                resetSliderTimer();
            }
        });
    });

    // Touch/swipe support on hero
    var touchStartX = 0;
    var heroEl = document.getElementById('hero');
    if (heroEl) {
        heroEl.addEventListener('touchstart', function (e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        heroEl.addEventListener('touchend', function (e) {
            var diff = touchStartX - e.changedTouches[0].screenX;
            if (Math.abs(diff) > 50) {
                goToSlide(diff > 0 ? current + 1 : current - 1);
                resetSliderTimer();
            }
        }, { passive: true });
    }

    startSlider();


    // ================================================================
    // 4. SCROLL REVEAL — IntersectionObserver
    // ================================================================
    var revealEls = document.querySelectorAll('.reveal');

    if ('IntersectionObserver' in window) {
        var revealObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -40px 0px'
        });

        revealEls.forEach(function (el) {
            revealObserver.observe(el);
        });
    } else {
        // Fallback: show all immediately
        revealEls.forEach(function (el) {
            el.classList.add('visible');
        });
    }


    // ================================================================
    // 5. SMOOTH SCROLL for anchor links
    // ================================================================
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            var targetId = this.getAttribute('href');
            if (targetId === '#') return;
            var target = document.querySelector(targetId);
            if (!target) return;
            e.preventDefault();
            var offset = 80; // navbar height
            var top = target.getBoundingClientRect().top + window.scrollY - offset;
            window.scrollTo({ top: top, behavior: 'smooth' });
        });
    });


    // ================================================================
    // 6. LEVEL CARDS — animate progress bars on scroll
    // ================================================================
    var lpFills = document.querySelectorAll('.lp-fill');

    if (lpFills.length && 'IntersectionObserver' in window) {
        var progressObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var fill = entry.target;
                    var targetWidth = fill.style.width;
                    fill.style.width = '0%';
                    // Re-trigger transition
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () {
                            fill.style.width = targetWidth;
                        });
                    });
                    progressObserver.unobserve(fill);
                }
            });
        }, { threshold: 0.5 });

        lpFills.forEach(function (fill) {
            progressObserver.observe(fill);
        });
    }


    // ================================================================
    // 7. NAVBAR active link highlight on scroll
    // ================================================================
    var sections = document.querySelectorAll('section[id]');
    var navAnchors = document.querySelectorAll('.nav-links a');

    function updateActiveNav() {
        var scrollPos = window.scrollY + 120;
        sections.forEach(function (section) {
            if (
                section.offsetTop <= scrollPos &&
                section.offsetTop + section.offsetHeight > scrollPos
            ) {
                navAnchors.forEach(function (a) {
                    a.style.color = '';
                });
                var activeAnchor = document.querySelector('.nav-links a[href="#' + section.id + '"]');
                if (activeAnchor) {
                    activeAnchor.style.color = '#fff';
                }
            }
        });
    }

    window.addEventListener('scroll', updateActiveNav, { passive: true });


    // ================================================================
    // 8. HERO PARALLAX on mouse move
    // ================================================================
    var heroSection = document.getElementById('hero');
    var heroSlider  = document.querySelector('.hero-slider');

    if (heroSection && heroSlider) {
        heroSection.addEventListener('mousemove', function (e) {
            var rect = heroSection.getBoundingClientRect();
            var xRatio = (e.clientX - rect.left) / rect.width - 0.5;
            var yRatio = (e.clientY - rect.top)  / rect.height - 0.5;
            heroSlider.style.transform = 'translate(' + (xRatio * 12) + 'px, ' + (yRatio * 8) + 'px)';
        }, { passive: true });

        heroSection.addEventListener('mouseleave', function () {
            heroSlider.style.transform = 'translate(0, 0)';
        });
    }


    // ================================================================
    // 9. MOTIVATION section — slow parallax background panels
    // ================================================================
    var motPanels = document.querySelectorAll('.mot-panel');

    if (motPanels.length) {
        window.addEventListener('scroll', function () {
            var motSection = document.getElementById('motivation');
            if (!motSection) return;
            var rect = motSection.getBoundingClientRect();
            var visible = rect.top < window.innerHeight && rect.bottom > 0;
            if (!visible) return;
            var progress = 1 - (rect.bottom / (window.innerHeight + rect.height));
            var shift = progress * 40;
            motPanels.forEach(function (panel, i) {
                var dir = i % 2 === 0 ? 1 : -1;
                panel.style.transform = 'translateY(' + (shift * dir * 0.5) + 'px) scale(1.05)';
            });
        }, { passive: true });
    }


    // ================================================================
    // 10. YEAR in footer (fallback if PHP not present)
    // ================================================================
    var yearEl = document.querySelector('.footer-year');
    if (yearEl) {
        yearEl.textContent = new Date().getFullYear();
    }

})();
