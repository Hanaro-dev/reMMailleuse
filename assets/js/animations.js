/**
 * Module Animations - Remmailleuse
 * Gestion des animations et interactions visuelles
 */

class AnimationsManager {
    constructor(app) {
        this.app = app;
        this.observers = [];
        this.init();
    }

    init() {
        this.setupScrollReveal();
        this.setupNavbarScrollEffect();
        this.setupThemeToggle();
        this.setupSmoothScroll();
        this.setupMobileMenu();
    }

    setupScrollReveal() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    // Optionnel: arrêter d'observer l'élément une fois révélé
                    // observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observer tous les éléments scroll-reveal
        document.querySelectorAll('.scroll-reveal').forEach(el => {
            observer.observe(el);
        });

        // Stocker l'observer pour pouvoir le réutiliser
        this.observers.push(observer);
    }

    setupNavbarScrollEffect() {
        let lastScrollY = window.scrollY;
        let ticking = false;

        const updateNavbar = () => {
            const navbar = document.getElementById('navbar');
            if (!navbar) return;

            const currentScrollY = window.scrollY;

            if (currentScrollY > 100) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
                navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
                navbar.style.backdropFilter = 'blur(10px)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
                navbar.style.boxShadow = 'none';
                navbar.style.backdropFilter = 'blur(5px)';
            }

            // Hide/show navbar on scroll direction
            if (currentScrollY > lastScrollY && currentScrollY > 200) {
                navbar.style.transform = 'translateY(-100%)';
            } else {
                navbar.style.transform = 'translateY(0)';
            }

            lastScrollY = currentScrollY;
            ticking = false;
        };

        const onScroll = () => {
            if (!ticking) {
                requestAnimationFrame(updateNavbar);
                ticking = true;
            }
        };

        window.addEventListener('scroll', onScroll, { passive: true });
    }


    setupSmoothScroll() {
        // Smooth scroll pour tous les liens d'ancre
        document.addEventListener('click', (e) => {
            const anchor = e.target.closest('a[href^="#"]');
            if (!anchor) return;

            e.preventDefault();
            const targetId = anchor.getAttribute('href');
            const target = document.querySelector(targetId);
            
            if (target) {
                const navbar = document.getElementById('navbar');
                const offset = navbar ? navbar.offsetHeight : 0;
                
                const targetPosition = target.offsetTop - offset - 20;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });

                // Animation de focus temporaire
                this.highlightElement(target);
            }
        });
    }

    setupMobileMenu() {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const navMenu = document.querySelector('.nav-menu');
        
        if (!mobileMenuBtn || !navMenu) return;

        mobileMenuBtn.addEventListener('click', () => {
            const isOpen = navMenu.classList.contains('mobile-open');
            
            if (isOpen) {
                this.closeMobileMenu();
            } else {
                this.openMobileMenu();
            }
        });

        // Fermer le menu si on clique sur un lien
        navMenu.addEventListener('click', (e) => {
            if (e.target.tagName === 'A') {
                this.closeMobileMenu();
            }
        });

        // Fermer le menu si on clique en dehors
        document.addEventListener('click', (e) => {
            if (!mobileMenuBtn.contains(e.target) && !navMenu.contains(e.target)) {
                this.closeMobileMenu();
            }
        });
    }

    openMobileMenu() {
        const navMenu = document.querySelector('.nav-menu');
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        
        if (navMenu) {
            navMenu.classList.add('mobile-open');
            document.body.style.overflow = 'hidden';
        }
        
        if (mobileMenuBtn) {
            mobileMenuBtn.classList.add('active');
            mobileMenuBtn.textContent = '✕';
        }
    }

    closeMobileMenu() {
        const navMenu = document.querySelector('.nav-menu');
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        
        if (navMenu) {
            navMenu.classList.remove('mobile-open');
            document.body.style.overflow = '';
        }
        
        if (mobileMenuBtn) {
            mobileMenuBtn.classList.remove('active');
            mobileMenuBtn.textContent = '☰';
        }
    }

    highlightElement(element) {
        const originalBackground = element.style.backgroundColor;
        const originalTransition = element.style.transition;
        
        element.style.transition = 'background-color 0.3s ease';
        element.style.backgroundColor = 'rgba(212, 137, 107, 0.1)';
        
        setTimeout(() => {
            element.style.backgroundColor = originalBackground;
            setTimeout(() => {
                element.style.transition = originalTransition;
            }, 300);
        }, 1000);
    }

    // Animation de chargement personnalisée
    showLoadingAnimation() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.classList.remove('hidden');
            loadingOverlay.classList.add('fade-in');
        }
    }

    hideLoadingAnimation() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.classList.add('fade-out');
            setTimeout(() => {
                loadingOverlay.classList.add('hidden');
                loadingOverlay.classList.remove('fade-in', 'fade-out');
            }, 300);
        }
    }

    // Animation pour les toasts
    animateToast(toastElement, type = 'show') {
        if (!toastElement) return;

        if (type === 'show') {
            toastElement.style.transform = 'translateY(100px)';
            toastElement.style.opacity = '0';
            
            setTimeout(() => {
                toastElement.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
                toastElement.style.transform = 'translateY(0)';
                toastElement.style.opacity = '1';
            }, 10);
        } else if (type === 'hide') {
            toastElement.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
            toastElement.style.transform = 'translateY(100px)';
            toastElement.style.opacity = '0';
        }
    }

    // Animation pour les éléments de galerie
    animateGalleryFilter(items, category) {
        items.forEach((item, index) => {
            const itemCategory = item.dataset.category;
            const shouldShow = category === 'tous' || itemCategory === category;
            
            if (shouldShow) {
                // Animation d'apparition
                item.style.opacity = '0';
                item.style.transform = 'scale(0.8) translateY(20px)';
                item.style.display = 'block';
                
                setTimeout(() => {
                    item.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'scale(1) translateY(0)';
                }, index * 50);
            } else {
                // Animation de disparition
                item.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                item.style.opacity = '0';
                item.style.transform = 'scale(0.8)';
                
                setTimeout(() => {
                    item.style.display = 'none';
                }, 200);
            }
        });
    }

    // Animation de typing effect pour les titres
    typeWriterEffect(element, text, speed = 50) {
        if (!element) return;
        
        element.textContent = '';
        let i = 0;
        
        const type = () => {
            if (i < text.length) {
                element.textContent += text.charAt(i);
                i++;
                setTimeout(type, speed);
            }
        };
        
        type();
    }

    // Animation de compteur numérique
    animateCounter(element, start, end, duration = 2000) {
        if (!element) return;
        
        const startTime = performance.now();
        const range = end - start;
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function (ease-out)
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const current = Math.round(start + (range * easeOut));
            
            element.textContent = current;
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }

    // Nettoyage des observers
    cleanup() {
        this.observers.forEach(observer => {
            observer.disconnect();
        });
        this.observers = [];
    }

    // Re-initialiser les animations pour les nouveaux éléments
    refreshAnimations() {
        this.cleanup();
        this.setupScrollReveal();
    }

    // Méthodes publiques pour l'API
    addScrollReveal(selector) {
        const elements = document.querySelectorAll(selector);
        const observer = this.observers[0]; // Réutiliser le premier observer
        
        elements.forEach(el => {
            if (observer) observer.observe(el);
        });
    }

    removeScrollReveal(selector) {
        const elements = document.querySelectorAll(selector);
        const observer = this.observers[0];
        
        elements.forEach(el => {
            if (observer) observer.unobserve(el);
        });
    }
}

// Export pour utilisation externe
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AnimationsManager;
}