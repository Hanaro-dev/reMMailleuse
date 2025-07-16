/**
 * ===== MONITEUR DE PERFORMANCE - SITE REMMAILLEUSE =====
 * Système de monitoring des performances en temps réel
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

class PerformanceMonitor {
    constructor(options = {}) {
        this.options = {
            enableRealTimeMonitoring: options.enableRealTimeMonitoring !== false,
            enableWebVitals: options.enableWebVitals !== false,
            enableResourceTiming: options.enableResourceTiming !== false,
            enableUserTiming: options.enableUserTiming !== false,
            enableNavigationTiming: options.enableNavigationTiming !== false,
            enableMemoryMonitoring: options.enableMemoryMonitoring !== false,
            enableNetworkMonitoring: options.enableNetworkMonitoring !== false,
            reportingInterval: options.reportingInterval || 30000, // 30 secondes
            thresholds: {
                fcp: options.thresholds?.fcp || 1800, // First Contentful Paint
                lcp: options.thresholds?.lcp || 2500, // Largest Contentful Paint
                fid: options.thresholds?.fid || 100,  // First Input Delay
                cls: options.thresholds?.cls || 0.1,  // Cumulative Layout Shift
                ttfb: options.thresholds?.ttfb || 600, // Time to First Byte
                ...options.thresholds
            },
            debug: options.debug || false
        };
        
        this.metrics = {
            webVitals: {},
            timing: {},
            resources: [],
            memory: {},
            network: {},
            user: {},
            errors: []
        };
        
        this.observers = new Map();
        this.startTime = performance.now();
        this.reportingTimer = null;
        
        this.init();
    }
    
    init() {
        if (!this.isPerformanceAPISupported()) {
            this.log('Performance API non supportée');
            return;
        }
        
        this.setupWebVitals();
        this.setupResourceTiming();
        this.setupUserTiming();
        this.setupNavigationTiming();
        this.setupMemoryMonitoring();
        this.setupNetworkMonitoring();
        this.setupErrorMonitoring();
        this.setupReporting();
        
        this.log('Performance monitor initialisé');
    }
    
    /**
     * Vérifier le support de l'API Performance
     */
    isPerformanceAPISupported() {
        return 'performance' in window && 'PerformanceObserver' in window;
    }
    
    /**
     * Configurer le monitoring des Web Vitals
     */
    setupWebVitals() {
        if (!this.options.enableWebVitals) return;
        
        // First Contentful Paint (FCP)
        this.observeMetric('paint', (entries) => {
            entries.forEach(entry => {
                if (entry.name === 'first-contentful-paint') {
                    this.metrics.webVitals.fcp = entry.startTime;
                    this.evaluateMetric('fcp', entry.startTime);
                }
            });
        });
        
        // Largest Contentful Paint (LCP)
        this.observeMetric('largest-contentful-paint', (entries) => {
            entries.forEach(entry => {
                this.metrics.webVitals.lcp = entry.startTime;
                this.evaluateMetric('lcp', entry.startTime);
            });
        });
        
        // First Input Delay (FID)
        this.observeMetric('first-input', (entries) => {
            entries.forEach(entry => {
                this.metrics.webVitals.fid = entry.processingStart - entry.startTime;
                this.evaluateMetric('fid', this.metrics.webVitals.fid);
            });
        });
        
        // Cumulative Layout Shift (CLS)
        this.observeMetric('layout-shift', (entries) => {
            let clsScore = 0;
            entries.forEach(entry => {
                if (!entry.hadRecentInput) {
                    clsScore += entry.value;
                }
            });
            this.metrics.webVitals.cls = clsScore;
            this.evaluateMetric('cls', clsScore);
        });
        
        // Time to First Byte (TTFB)
        this.calculateTTFB();
    }
    
    /**
     * Calculer le Time to First Byte
     */
    calculateTTFB() {
        const navigationEntry = performance.getEntriesByType('navigation')[0];
        if (navigationEntry) {
            const ttfb = navigationEntry.responseStart - navigationEntry.fetchStart;
            this.metrics.webVitals.ttfb = ttfb;
            this.evaluateMetric('ttfb', ttfb);
        }
    }
    
    /**
     * Configurer le monitoring des ressources
     */
    setupResourceTiming() {
        if (!this.options.enableResourceTiming) return;
        
        this.observeMetric('resource', (entries) => {
            entries.forEach(entry => {
                const resource = {
                    name: entry.name,
                    type: entry.initiatorType,
                    duration: entry.duration,
                    size: entry.transferSize,
                    cached: entry.transferSize === 0 && entry.decodedBodySize > 0,
                    startTime: entry.startTime,
                    endTime: entry.responseEnd
                };
                
                this.metrics.resources.push(resource);
                this.analyzeResource(resource);
            });
        });
    }
    
    /**
     * Analyser une ressource
     */
    analyzeResource(resource) {
        // Détecter les ressources lentes
        if (resource.duration > 1000) {
            this.recordIssue('slow-resource', {
                resource: resource.name,
                duration: resource.duration,
                type: resource.type
            });
        }
        
        // Détecter les ressources volumineuses
        if (resource.size > 1000000) { // 1MB
            this.recordIssue('large-resource', {
                resource: resource.name,
                size: resource.size,
                type: resource.type
            });
        }
    }
    
    /**
     * Configurer le monitoring des timings utilisateur
     */
    setupUserTiming() {
        if (!this.options.enableUserTiming) return;
        
        this.observeMetric('measure', (entries) => {
            entries.forEach(entry => {
                this.metrics.user[entry.name] = entry.duration;
            });
        });
    }
    
    /**
     * Configurer le monitoring de la navigation
     */
    setupNavigationTiming() {
        if (!this.options.enableNavigationTiming) return;
        
        const navigationEntry = performance.getEntriesByType('navigation')[0];
        if (navigationEntry) {
            this.metrics.timing = {
                domainLookup: navigationEntry.domainLookupEnd - navigationEntry.domainLookupStart,
                connection: navigationEntry.connectEnd - navigationEntry.connectStart,
                request: navigationEntry.responseStart - navigationEntry.requestStart,
                response: navigationEntry.responseEnd - navigationEntry.responseStart,
                domLoading: navigationEntry.domContentLoadedEventEnd - navigationEntry.domContentLoadedEventStart,
                domComplete: navigationEntry.domComplete - navigationEntry.domContentLoadedEventEnd,
                loadEvent: navigationEntry.loadEventEnd - navigationEntry.loadEventStart
            };
        }
    }
    
    /**
     * Configurer le monitoring de la mémoire
     */
    setupMemoryMonitoring() {
        if (!this.options.enableMemoryMonitoring || !('memory' in performance)) return;
        
        const updateMemoryMetrics = () => {
            const memory = performance.memory;
            this.metrics.memory = {
                used: memory.usedJSHeapSize,
                total: memory.totalJSHeapSize,
                limit: memory.jsHeapSizeLimit,
                usagePercent: (memory.usedJSHeapSize / memory.jsHeapSizeLimit) * 100
            };
            
            // Alerte si utilisation mémoire > 80%
            if (this.metrics.memory.usagePercent > 80) {
                this.recordIssue('high-memory-usage', {
                    usage: this.metrics.memory.usagePercent,
                    used: this.metrics.memory.used,
                    limit: this.metrics.memory.limit
                });
            }
        };
        
        // Mettre à jour toutes les 5 secondes
        setInterval(updateMemoryMetrics, 5000);
        updateMemoryMetrics();
    }
    
    /**
     * Configurer le monitoring réseau
     */
    setupNetworkMonitoring() {
        if (!this.options.enableNetworkMonitoring || !('connection' in navigator)) return;
        
        const updateNetworkMetrics = () => {
            const connection = navigator.connection;
            this.metrics.network = {
                type: connection.effectiveType,
                downlink: connection.downlink,
                rtt: connection.rtt,
                saveData: connection.saveData
            };
        };
        
        // Surveiller les changements de connexion
        navigator.connection.addEventListener('change', updateNetworkMetrics);
        updateNetworkMetrics();
    }
    
    /**
     * Configurer le monitoring des erreurs
     */
    setupErrorMonitoring() {
        // Erreurs JavaScript
        window.addEventListener('error', (event) => {
            this.recordError({
                type: 'javascript',
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                stack: event.error?.stack,
                timestamp: Date.now()
            });
        });
        
        // Promesses rejetées
        window.addEventListener('unhandledrejection', (event) => {
            this.recordError({
                type: 'promise',
                message: event.reason?.message || 'Unhandled promise rejection',
                stack: event.reason?.stack,
                timestamp: Date.now()
            });
        });
        
        // Erreurs de ressources
        window.addEventListener('error', (event) => {
            if (event.target !== window) {
                this.recordError({
                    type: 'resource',
                    message: `Failed to load: ${event.target.src || event.target.href}`,
                    element: event.target.tagName,
                    timestamp: Date.now()
                });
            }
        }, true);
    }
    
    /**
     * Observer une métrique
     */
    observeMetric(type, callback) {
        try {
            const observer = new PerformanceObserver(callback);
            observer.observe({ entryTypes: [type] });
            this.observers.set(type, observer);
        } catch (error) {
            this.log(`Erreur observation ${type}:`, error);
        }
    }
    
    /**
     * Évaluer une métrique
     */
    evaluateMetric(metric, value) {
        const threshold = this.options.thresholds[metric];
        if (!threshold) return;
        
        const status = value <= threshold ? 'good' : value <= threshold * 1.5 ? 'needs-improvement' : 'poor';
        
        this.log(`${metric.toUpperCase()}: ${value.toFixed(2)}ms (${status})`);
        
        if (status === 'poor') {
            this.recordIssue(`poor-${metric}`, {
                metric,
                value,
                threshold,
                status
            });
        }
        
        this.dispatchEvent('metricEvaluated', {
            metric,
            value,
            status,
            threshold
        });
    }
    
    /**
     * Enregistrer un problème
     */
    recordIssue(type, details) {
        const issue = {
            type,
            details,
            timestamp: Date.now(),
            url: window.location.href,
            userAgent: navigator.userAgent
        };
        
        this.metrics.errors.push(issue);
        
        this.log(`Problème détecté: ${type}`, details);
        
        this.dispatchEvent('issueDetected', issue);
    }
    
    /**
     * Enregistrer une erreur
     */
    recordError(error) {
        this.metrics.errors.push(error);
        
        this.log('Erreur enregistrée:', error);
        
        this.dispatchEvent('errorRecorded', error);
    }
    
    /**
     * Configurer le reporting
     */
    setupReporting() {
        if (!this.options.enableRealTimeMonitoring) return;
        
        this.reportingTimer = setInterval(() => {
            this.generateReport();
        }, this.options.reportingInterval);
        
        // Rapport au déchargement de la page
        window.addEventListener('beforeunload', () => {
            this.generateFinalReport();
        });
    }
    
    /**
     * Générer un rapport
     */
    generateReport() {
        const report = {
            timestamp: Date.now(),
            sessionDuration: Date.now() - this.startTime,
            url: window.location.href,
            metrics: this.getMetrics(),
            issues: this.getIssues(),
            performance: this.getPerformanceScore()
        };
        
        this.log('Rapport généré:', report);
        
        this.dispatchEvent('reportGenerated', report);
        
        // Envoyer le rapport si configuré
        if (this.options.reportingEndpoint) {
            this.sendReport(report);
        }
        
        return report;
    }
    
    /**
     * Générer le rapport final
     */
    generateFinalReport() {
        const report = this.generateReport();
        report.final = true;
        
        // Utiliser navigator.sendBeacon pour l'envoi fiable
        if (this.options.reportingEndpoint && navigator.sendBeacon) {
            navigator.sendBeacon(
                this.options.reportingEndpoint,
                JSON.stringify(report)
            );
        }
        
        return report;
    }
    
    /**
     * Envoyer un rapport
     */
    async sendReport(report) {
        try {
            const response = await fetch(this.options.reportingEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(report)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            this.log('Rapport envoyé avec succès');
            
        } catch (error) {
            this.log('Erreur envoi rapport:', error);
        }
    }
    
    /**
     * Obtenir les métriques
     */
    getMetrics() {
        return {
            webVitals: { ...this.metrics.webVitals },
            timing: { ...this.metrics.timing },
            memory: { ...this.metrics.memory },
            network: { ...this.metrics.network },
            user: { ...this.metrics.user },
            resourceCount: this.metrics.resources.length,
            errorCount: this.metrics.errors.length
        };
    }
    
    /**
     * Obtenir les problèmes
     */
    getIssues() {
        return this.metrics.errors.filter(error => error.type !== 'javascript' && error.type !== 'promise' && error.type !== 'resource');
    }
    
    /**
     * Calculer le score de performance
     */
    getPerformanceScore() {
        const scores = {
            fcp: this.calculateScore('fcp', this.metrics.webVitals.fcp),
            lcp: this.calculateScore('lcp', this.metrics.webVitals.lcp),
            fid: this.calculateScore('fid', this.metrics.webVitals.fid),
            cls: this.calculateScore('cls', this.metrics.webVitals.cls),
            ttfb: this.calculateScore('ttfb', this.metrics.webVitals.ttfb)
        };
        
        const validScores = Object.values(scores).filter(score => score !== null);
        const average = validScores.length > 0 ? 
            validScores.reduce((sum, score) => sum + score, 0) / validScores.length : 0;
        
        return {
            overall: Math.round(average),
            breakdown: scores
        };
    }
    
    /**
     * Calculer le score d'une métrique
     */
    calculateScore(metric, value) {
        if (value === undefined || value === null) return null;
        
        const threshold = this.options.thresholds[metric];
        if (!threshold) return null;
        
        if (value <= threshold) return 100;
        if (value <= threshold * 1.5) return 75;
        if (value <= threshold * 2) return 50;
        return 25;
    }
    
    /**
     * Marquer un timing personnalisé
     */
    mark(name) {
        if ('mark' in performance) {
            performance.mark(name);
        }
    }
    
    /**
     * Mesurer entre deux marques
     */
    measure(name, startMark, endMark) {
        if ('measure' in performance) {
            performance.measure(name, startMark, endMark);
        }
    }
    
    /**
     * Obtenir les statistiques détaillées
     */
    getDetailedStats() {
        return {
            webVitals: this.metrics.webVitals,
            timing: this.metrics.timing,
            resources: this.metrics.resources.map(r => ({
                name: r.name,
                type: r.type,
                duration: r.duration,
                size: r.size,
                cached: r.cached
            })),
            memory: this.metrics.memory,
            network: this.metrics.network,
            user: this.metrics.user,
            errors: this.metrics.errors,
            performance: this.getPerformanceScore(),
            sessionDuration: Date.now() - this.startTime
        };
    }
    
    /**
     * Réinitialiser les métriques
     */
    reset() {
        this.metrics = {
            webVitals: {},
            timing: {},
            resources: [],
            memory: {},
            network: {},
            user: {},
            errors: []
        };
        
        this.startTime = Date.now();
        
        this.log('Métriques réinitialisées');
    }
    
    /**
     * Démarrer le monitoring d'une opération
     */
    startOperation(operationName) {
        this.mark(`${operationName}-start`);
        
        return {
            end: () => {
                this.mark(`${operationName}-end`);
                this.measure(operationName, `${operationName}-start`, `${operationName}-end`);
            }
        };
    }
    
    /**
     * Méthodes utilitaires
     */
    
    dispatchEvent(eventName, detail) {
        const event = new CustomEvent(`performanceMonitor.${eventName}`, {
            detail,
            bubbles: true
        });
        document.dispatchEvent(event);
    }
    
    log(...args) {
        if (this.options.debug) {
            console.log('[PerformanceMonitor]', ...args);
        }
    }
    
    /**
     * Détruire le monitor
     */
    destroy() {
        // Arrêter tous les observers
        this.observers.forEach(observer => {
            observer.disconnect();
        });
        this.observers.clear();
        
        // Arrêter le reporting
        if (this.reportingTimer) {
            clearInterval(this.reportingTimer);
        }
        
        // Générer le rapport final
        this.generateFinalReport();
        
        this.log('Performance monitor détruit');
    }
}

// Initialisation automatique
document.addEventListener('DOMContentLoaded', () => {
    window.performanceMonitor = new PerformanceMonitor({
        enableRealTimeMonitoring: true,
        enableWebVitals: true,
        enableResourceTiming: true,
        enableUserTiming: true,
        enableNavigationTiming: true,
        enableMemoryMonitoring: true,
        enableNetworkMonitoring: true,
        reportingInterval: 30000,
        debug: false
    });
    
    // Marquer le démarrage de l'app
    window.performanceMonitor.mark('app-start');
});

// Nettoyage avant déchargement
window.addEventListener('beforeunload', () => {
    if (window.performanceMonitor) {
        window.performanceMonitor.destroy();
    }
});

// Exposer les méthodes utiles globalement
window.perf = {
    mark: (name) => window.performanceMonitor?.mark(name),
    measure: (name, start, end) => window.performanceMonitor?.measure(name, start, end),
    startOperation: (name) => window.performanceMonitor?.startOperation(name),
    getStats: () => window.performanceMonitor?.getDetailedStats(),
    getReport: () => window.performanceMonitor?.generateReport()
};