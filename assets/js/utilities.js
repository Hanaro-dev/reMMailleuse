/**
 * Utilitaires pour le site ReMmailleuse
 * Fonctions helper et utilitaires divers
 */

class Utilities {
    /**
     * Debounce function pour limiter les appels
     */
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Throttle function pour limiter les appels
     */
    static throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Validation d'email
     */
    static isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Validation de téléphone
     */
    static isValidPhone(phone) {
        const phoneRegex = /^[\+]?[0-9\s\-\(\)\.]{8,}$/;
        return phoneRegex.test(phone);
    }

    /**
     * Formatage de la taille des fichiers
     */
    static formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Vérification du type de fichier
     */
    static isValidImageFile(file) {
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        return allowedTypes.includes(file.type);
    }

    /**
     * Génération d'un ID unique
     */
    static generateId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    }

    /**
     * Smooth scroll vers un élément
     */
    static scrollToElement(selector, offset = 0) {
        const element = document.querySelector(selector);
        if (element) {
            const elementPosition = element.offsetTop - offset;
            window.scrollTo({
                top: elementPosition,
                behavior: 'smooth'
            });
        }
    }

    /**
     * Vérification si un élément est visible
     */
    static isElementInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    /**
     * Création d'un élément DOM avec attributs
     */
    static createElement(tag, attributes = {}, content = '') {
        const element = document.createElement(tag);
        
        Object.entries(attributes).forEach(([key, value]) => {
            if (key === 'className') {
                element.className = value;
            } else if (key === 'dataset') {
                Object.entries(value).forEach(([dataKey, dataValue]) => {
                    element.dataset[dataKey] = dataValue;
                });
            } else {
                element.setAttribute(key, value);
            }
        });

        if (content) {
            if (typeof content === 'string') {
                element.textContent = content;
            } else {
                element.appendChild(content);
            }
        }

        return element;
    }

    /**
     * Copie de texte dans le presse-papiers
     */
    static async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            // Fallback pour les navigateurs plus anciens
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                document.body.removeChild(textArea);
                return true;
            } catch (err) {
                document.body.removeChild(textArea);
                return false;
            }
        }
    }

    /**
     * Détection des features du navigateur
     */
    static getBrowserFeatures() {
        return {
            serviceWorker: 'serviceWorker' in navigator,
            webp: this.supportsWebP(),
            intersection: 'IntersectionObserver' in window,
            fetch: 'fetch' in window,
            promises: 'Promise' in window,
            localStorage: this.supportsLocalStorage(),
            sessionStorage: this.supportsSessionStorage()
        };
    }

    /**
     * Support WebP
     */
    static supportsWebP() {
        const canvas = document.createElement('canvas');
        canvas.width = 1;
        canvas.height = 1;
        return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
    }

    /**
     * Support localStorage
     */
    static supportsLocalStorage() {
        try {
            const test = '__localStorage_test__';
            localStorage.setItem(test, test);
            localStorage.removeItem(test);
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * Support sessionStorage
     */
    static supportsSessionStorage() {
        try {
            const test = '__sessionStorage_test__';
            sessionStorage.setItem(test, test);
            sessionStorage.removeItem(test);
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * Calcul du prix estimé
     */
    static calculateEstimatedPrice(garmentType, damageType, size) {
        const basePrices = {
            pull: { min: 20, max: 40 },
            bas: { min: 15, max: 25 },
            robe: { min: 25, max: 50 },
            autre: { min: 15, max: 40 }
        };

        const damageMultipliers = {
            mite: 1.0,
            accroc: 1.2,
            usure: 1.5,
            autre: 1.1
        };

        const sizeMultipliers = {
            small: 1.0,
            medium: 1.2,
            large: 1.5
        };

        const basePrice = basePrices[garmentType] || basePrices.autre;
        const damageMultiplier = damageMultipliers[damageType] || 1.0;
        
        // Calcul de la taille
        let sizeMultiplier = 1.0;
        if (size) {
            const sizeNum = parseFloat(size);
            if (sizeNum < 5) sizeMultiplier = 1.0;
            else if (sizeNum < 10) sizeMultiplier = 1.2;
            else sizeMultiplier = 1.5;
        }

        const minPrice = Math.round(basePrice.min * damageMultiplier * sizeMultiplier);
        const maxPrice = Math.round(basePrice.max * damageMultiplier * sizeMultiplier);

        return {
            min: minPrice,
            max: maxPrice,
            display: `${minPrice}-${maxPrice}€`
        };
    }
}

// Export global
window.Utilities = Utilities;