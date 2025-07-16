/**
 * Système de sanitisation pour prévenir les attaques XSS
 * Alternative sécurisée à innerHTML
 */

class HTMLSanitizer {
    constructor() {
        this.allowedTags = ['p', 'br', 'strong', 'em', 'span', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        this.allowedAttributes = ['class', 'id'];
    }

    /**
     * Sanitise le HTML en supprimant les éléments dangereux
     * @param {string} html - Le HTML à sanitiser
     * @returns {string} - Le HTML sanitisé
     */
    sanitize(html) {
        if (typeof html !== 'string') {
            return '';
        }

        // Créer un élément temporaire pour parser le HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;

        // Nettoyer récursivement tous les éléments
        this.cleanElement(tempDiv);

        return tempDiv.innerHTML;
    }

    /**
     * Nettoie un élément DOM de façon récursive
     * @param {Element} element - L'élément à nettoyer
     */
    cleanElement(element) {
        const children = Array.from(element.children);
        
        children.forEach(child => {
            // Vérifier si la balise est autorisée
            if (!this.allowedTags.includes(child.tagName.toLowerCase())) {
                // Remplacer par son contenu textuel
                const textNode = document.createTextNode(child.textContent);
                element.replaceChild(textNode, child);
                return;
            }

            // Nettoyer les attributs
            this.cleanAttributes(child);

            // Nettoyer récursivement les enfants
            this.cleanElement(child);
        });
    }

    /**
     * Nettoie les attributs d'un élément
     * @param {Element} element - L'élément à nettoyer
     */
    cleanAttributes(element) {
        const attributes = Array.from(element.attributes);
        
        attributes.forEach(attr => {
            if (!this.allowedAttributes.includes(attr.name.toLowerCase())) {
                element.removeAttribute(attr.name);
            }
        });
    }

    /**
     * Méthode statique pour utilisation rapide
     * @param {string} html - Le HTML à sanitiser
     * @returns {string} - Le HTML sanitisé
     */
    static clean(html) {
        const sanitizer = new HTMLSanitizer();
        return sanitizer.sanitize(html);
    }

    /**
     * Définit le contenu HTML sécurisé d'un élément
     * @param {Element} element - L'élément cible
     * @param {string} html - Le HTML à insérer
     */
    static setHTML(element, html) {
        if (!element) return;
        
        const sanitizedHTML = HTMLSanitizer.clean(html);
        element.innerHTML = sanitizedHTML;
    }

    /**
     * Définit le contenu texte d'un élément (sécurisé par défaut)
     * @param {Element} element - L'élément cible
     * @param {string} text - Le texte à insérer
     */
    static setText(element, text) {
        if (!element) return;
        element.textContent = text;
    }
}

// Fonctions utilitaires globales
window.safeHTML = HTMLSanitizer.setHTML;
window.safeText = HTMLSanitizer.setText;
window.sanitizeHTML = HTMLSanitizer.clean;