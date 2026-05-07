/**
 * PipoCine — Main Landing Page JavaScript
 * Interativo • Minimalista • Profissional
 */

(function() {
    'use strict';

    // ═════════════════════════════════════════════════════════════════════════════
    // CONFIGURATION
    // ═════════════════════════════════════════════════════════════════════════════
    const CONFIG = {
        scrollOffset: 60,
        revealThreshold: 0.15,
        revealMargin: '-50px 0px',
        parallaxIntensity: 0.3,
        cursorTimeout: 100
    };

    // ═════════════════════════════════════════════════════════════════════════════
    // DOM ELEMENTS
    // ═════════════════════════════════════════════════════════════════════════════
    const elements = {
        nav: document.getElementById('main-nav'),
        hero: document.getElementById('main-hero'),
        heroBg: document.querySelector('.main-hero__bg img'),
        revealElements: document.querySelectorAll('[data-reveal]'),
        featureCards: document.querySelectorAll('.feature-card'),
        platformCards: document.querySelectorAll('.platform-card, .platform-orbital-card')
    };

    // ═════════════════════════════════════════════════════════════════════════════
    // NAVBAR SCROLL EFFECT
    // ═════════════════════════════════════════════════════════════════════════════
    function initNavbar() {
        if (!elements.nav) return;

        let lastScroll = 0;
        let ticking = false;

        function updateNavbar() {
            const currentScroll = window.scrollY;
            
            // Add/remove scrolled class
            elements.nav.classList.toggle('scrolled', currentScroll > CONFIG.scrollOffset);
            
            // Hide/show navbar on scroll direction (mobile)
            if (window.innerWidth <= 768) {
                if (currentScroll > lastScroll && currentScroll > 100) {
                    elements.nav.style.transform = 'translateY(-100%)';
                } else {
                    elements.nav.style.transform = 'translateY(0)';
                }
            }
            
            lastScroll = currentScroll;
            ticking = false;
        }

        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(updateNavbar);
                ticking = true;
            }
        }, { passive: true });
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // SCROLL REVEAL ANIMATION
    // ═════════════════════════════════════════════════════════════════════════════
    function initScrollReveal() {
        const observerOptions = {
            threshold: CONFIG.revealThreshold,
            rootMargin: CONFIG.revealMargin
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    // Stagger delay for multiple elements
                    const delay = entry.target.dataset.delay 
                        ? parseInt(entry.target.dataset.delay) 
                        : index * 100;
                    
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, delay);
                    
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        elements.revealElements.forEach(el => observer.observe(el));
        
        // Also observe section headers and standalone elements
        document.querySelectorAll('.section-header, .feature-card, .stat-item, .showcase-visual, .showcase-content, .cta-content, .platforms-grid')
            .forEach(el => observer.observe(el));
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // PARALLAX HERO EFFECT
    // ═════════════════════════════════════════════════════════════════════════════
    function initParallax() {
        if (!elements.heroBg || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        
        let ticking = false;
        
        function updateParallax() {
            const scrollY = window.scrollY;
            const heroHeight = elements.hero?.offsetHeight || window.innerHeight;
            
            if (scrollY < heroHeight) {
                const translateY = scrollY * CONFIG.parallaxIntensity;
                elements.heroBg.style.transform = `translateY(${translateY}px) scale(1.1)`;
            }
            
            ticking = false;
        }
        
        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(updateParallax);
                ticking = true;
            }
        }, { passive: true });
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // ═════════════════════════════════════════════════════════════════════════════
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                
                if (target) {
                    const navHeight = elements.nav?.offsetHeight || 0;
                    const targetPosition = target.getBoundingClientRect().top + window.scrollY - navHeight - 20;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // FEATURE CARDS INTERACTION
    // ═════════════════════════════════════════════════════════════════════════════
    function initFeatureCards() {
        elements.featureCards.forEach(card => {
            // 3D tilt effect on mouse move
            card.addEventListener('mousemove', (e) => {
                if (window.innerWidth <= 768) return;
                
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;
                
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-8px)`;
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // INTERACTIVE FEATURES TABS
    // ═════════════════════════════════════════════════════════════════════════════
    function initFeatureTabs() {
        const tabs = document.querySelectorAll('.feature-tab');
        const panels = document.querySelectorAll('.feature-panel');
        
        if (!tabs.length || !panels.length) return;
        
        // Tab click handler
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const targetTab = tab.dataset.tab;
                
                // Remove active from all tabs
                tabs.forEach(t => t.classList.remove('active'));
                
                // Add active to clicked tab
                tab.classList.add('active');
                
                // Hide all panels with animation
                panels.forEach(panel => {
                    if (panel.classList.contains('active')) {
                        panel.style.animation = 'fadeOut 0.2s ease forwards';
                        setTimeout(() => {
                            panel.classList.remove('active');
                            panel.style.animation = '';
                        }, 200);
                    }
                });
                
                // Show target panel with delay for smooth transition
                setTimeout(() => {
                    const targetPanel = document.getElementById(`panel-${targetTab}`);
                    if (targetPanel) {
                        targetPanel.classList.add('active');
                    }
                }, 200);
            });
        });
        
        // Auto-rotate tabs every 6 seconds
        let currentTabIndex = 0;
        let autoRotateInterval;
        
        function rotateTabs() {
            currentTabIndex = (currentTabIndex + 1) % tabs.length;
            tabs[currentTabIndex].click();
        }
        
        function startAutoRotate() {
            autoRotateInterval = setInterval(rotateTabs, 6000);
        }
        
        function stopAutoRotate() {
            clearInterval(autoRotateInterval);
        }
        
        // Start auto-rotate
        startAutoRotate();
        
        // Pause on hover over tabs or panels
        const tabsContainer = document.querySelector('.features-tabs');
        const panelsContainer = document.querySelector('.features-panels');
        
        if (tabsContainer) {
            tabsContainer.addEventListener('mouseenter', stopAutoRotate);
            tabsContainer.addEventListener('mouseleave', startAutoRotate);
        }
        
        if (panelsContainer) {
            panelsContainer.addEventListener('mouseenter', stopAutoRotate);
            panelsContainer.addEventListener('mouseleave', startAutoRotate);
        }
        
        // Add keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' && document.querySelector('.interactive-features:hover')) {
                e.preventDefault();
                currentTabIndex = (currentTabIndex + 1) % tabs.length;
                tabs[currentTabIndex].click();
            } else if (e.key === 'ArrowUp' && document.querySelector('.interactive-features:hover')) {
                e.preventDefault();
                currentTabIndex = (currentTabIndex - 1 + tabs.length) % tabs.length;
                tabs[currentTabIndex].click();
            }
        });
    }

    // Add fadeOut keyframes
    const fadeOutStyle = document.createElement('style');
    fadeOutStyle.textContent = `
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }
    `;
    document.head.appendChild(fadeOutStyle);

    // ═════════════════════════════════════════════════════════════════════════════
    // PLATFORM CARDS HOVER EFFECT
    // ═════════════════════════════════════════════════════════════════════════════
    function initPlatformCards() {
        elements.platformCards.forEach((card, index) => {
            card.addEventListener('mouseenter', () => {
                // Dim other cards
                elements.platformCards.forEach((otherCard, otherIndex) => {
                    if (otherIndex !== index) {
                        otherCard.style.opacity = '0.5';
                        otherCard.style.transform = 'scale(0.95)';
                    }
                });
            });
            
            card.addEventListener('mouseleave', () => {
                // Restore all cards
                elements.platformCards.forEach(otherCard => {
                    otherCard.style.opacity = '';
                    otherCard.style.transform = '';
                });
            });
        });
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // TYPING EFFECT FOR HERO TITLE (optional enhancement)
    // ═════════════════════════════════════════════════════════════════════════════
    function initTypingEffect() {
        const title = document.querySelector('.main-hero__title');
        if (!title || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        
        const originalHTML = title.innerHTML;
        const text = title.textContent;
        
        // Simple fade in instead of typing for better performance
        title.style.opacity = '0';
        title.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            title.style.transition = 'all 0.8s cubic-bezier(0.16, 1, 0.3, 1)';
            title.style.opacity = '1';
            title.style.transform = 'translateY(0)';
        }, 300);
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // MOUSE CURSOR GLOW EFFECT (desktop only)
    // ═════════════════════════════════════════════════════════════════════════════
    function initCursorGlow() {
        if (window.innerWidth <= 1024 || window.matchMedia('(pointer: coarse)').matches) return;
        
        const cursor = document.createElement('div');
        cursor.className = 'cursor-glow';
        cursor.style.cssText = `
            position: fixed;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(229, 9, 20, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            transform: translate(-50%, -50%);
            transition: opacity 0.3s ease;
            opacity: 0;
        `;
        document.body.appendChild(cursor);
        
        let mouseX = 0, mouseY = 0;
        let cursorX = 0, cursorY = 0;
        let isMoving = false;
        let moveTimeout;
        let rafId;
        
        function updateCursor() {
            if (!isMoving) return;
            
            cursorX += (mouseX - cursorX) * 0.1;
            cursorY += (mouseY - cursorY) * 0.1;
            
            cursor.style.left = cursorX + 'px';
            cursor.style.top = cursorY + 'px';
            
            rafId = requestAnimationFrame(updateCursor);
        }
        
        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
            
            if (!isMoving) {
                isMoving = true;
                cursor.style.opacity = '1';
                updateCursor();
            }
            
            clearTimeout(moveTimeout);
            moveTimeout = setTimeout(() => {
                isMoving = false;
                cursor.style.opacity = '0';
            }, CONFIG.cursorTimeout);
        }, { passive: true });
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // LAZY LOADING FOR IMAGES
    // ═════════════════════════════════════════════════════════════════════════════
    function initLazyLoading() {
        const images = document.querySelectorAll('img[loading="lazy"]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.classList.add('loaded');
                        imageObserver.unobserve(img);
                    }
                });
            }, { rootMargin: '50px' });
            
            images.forEach(img => imageObserver.observe(img));
        }
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // BUTTON RIPPLE EFFECT
    // ═════════════════════════════════════════════════════════════════════════════
    function initRippleEffect() {
        document.querySelectorAll('.btn-primary, .btn-secondary, .nav-cta').forEach(button => {
            button.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const ripple = document.createElement('span');
                ripple.style.cssText = `
                    position: absolute;
                    background: rgba(255, 255, 255, 0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                    left: ${x}px;
                    top: ${y}px;
                    width: 20px;
                    height: 20px;
                    margin-left: -10px;
                    margin-top: -10px;
                `;
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
        });
        
        // Add ripple animation keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // PERFORMANCE: Cleanup on page hide
    // ═════════════════════════════════════════════════════════════════════════════
    function initPerformanceOptimizations() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Pause expensive animations when tab is hidden
                document.body.classList.add('tab-hidden');
            } else {
                document.body.classList.remove('tab-hidden');
            }
        });
    }

    // ═════════════════════════════════════════════════════════════════════════════
    // INITIALIZATION
    // ═════════════════════════════════════════════════════════════════════════════
    function init() {
        initNavbar();
        initScrollReveal();
        initParallax();
        initSmoothScroll();
        initFeatureTabs();
        initFeatureCards();
        initPlatformCards();
        initTypingEffect();
        initCursorGlow();
        initLazyLoading();
        initRippleEffect();
        initPerformanceOptimizations();
        
        console.log('%c🎬 PipoCine', 'color: #e50914; font-size: 20px; font-weight: bold;', '— Landing Page Loaded');
    }

    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
