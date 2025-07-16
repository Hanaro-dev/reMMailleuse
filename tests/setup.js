/**
 * Configuration Jest pour les tests JavaScript
 */

// Imports
import '@testing-library/jest-dom';

// Configuration globale
global.console = {
  ...console,
  // Désactiver les logs pendant les tests
  log: jest.fn(),
  warn: jest.fn(),
  error: jest.fn()
};

// Mock des APIs du navigateur
Object.defineProperty(window, 'localStorage', {
  value: {
    getItem: jest.fn(),
    setItem: jest.fn(),
    removeItem: jest.fn(),
    clear: jest.fn()
  },
  writable: true
});

Object.defineProperty(window, 'sessionStorage', {
  value: {
    getItem: jest.fn(),
    setItem: jest.fn(),
    removeItem: jest.fn(),
    clear: jest.fn()
  },
  writable: true
});

// Mock de fetch
global.fetch = jest.fn();

// Mock de XMLHttpRequest
global.XMLHttpRequest = jest.fn(() => ({
  open: jest.fn(),
  send: jest.fn(),
  setRequestHeader: jest.fn(),
  addEventListener: jest.fn(),
  status: 200,
  responseText: JSON.stringify({ success: true })
}));

// Mock des fonctions de navigation
Object.defineProperty(window, 'location', {
  value: {
    href: 'http://localhost:8000',
    protocol: 'http:',
    hostname: 'localhost',
    port: '8000',
    pathname: '/',
    search: '',
    hash: '',
    reload: jest.fn(),
    assign: jest.fn()
  },
  writable: true
});

// Mock du Service Worker
Object.defineProperty(navigator, 'serviceWorker', {
  value: {
    register: jest.fn(() => Promise.resolve()),
    ready: Promise.resolve(),
    controller: null
  },
  writable: true
});

// Mock des notifications
Object.defineProperty(window, 'Notification', {
  value: {
    permission: 'granted',
    requestPermission: jest.fn(() => Promise.resolve('granted'))
  },
  writable: true
});

// Fonctions d'aide pour les tests
global.TestHelpers = {
  // Créer un événement de test
  createEvent: (type, options = {}) => {
    const event = new Event(type, { bubbles: true, cancelable: true });
    Object.assign(event, options);
    return event;
  },
  
  // Créer un élément DOM de test
  createElement: (tag, attributes = {}, textContent = '') => {
    const element = document.createElement(tag);
    Object.keys(attributes).forEach(key => {
      element.setAttribute(key, attributes[key]);
    });
    if (textContent) {
      element.textContent = textContent;
    }
    return element;
  },
  
  // Simuler un délai
  delay: (ms) => new Promise(resolve => setTimeout(resolve, ms)),
  
  // Nettoyer le DOM
  cleanupDOM: () => {
    document.body.innerHTML = '';
    document.head.innerHTML = '';
  },
  
  // Simuler des données de formulaire
  mockFormData: (data) => {
    const formData = new FormData();
    Object.keys(data).forEach(key => {
      formData.append(key, data[key]);
    });
    return formData;
  },
  
  // Simuler une réponse fetch
  mockFetchResponse: (data, status = 200) => {
    return Promise.resolve({
      ok: status >= 200 && status < 300,
      status,
      json: () => Promise.resolve(data),
      text: () => Promise.resolve(JSON.stringify(data))
    });
  }
};

// Configuration des timeouts
jest.setTimeout(10000);

// Nettoyage après chaque test
afterEach(() => {
  // Nettoyer les mocks
  jest.clearAllMocks();
  
  // Nettoyer localStorage
  localStorage.clear();
  sessionStorage.clear();
  
  // Nettoyer le DOM
  TestHelpers.cleanupDOM();
  
  // Réinitialiser les variables globales
  delete window.csrfManager;
  delete window.analyticsManager;
  delete window.imageUploadManager;
  delete window.backupManager;
});

// Configuration avant tous les tests
beforeAll(() => {
  // Désactiver les animations CSS
  const style = document.createElement('style');
  style.textContent = `
    *, *::before, *::after {
      animation-duration: 0s !important;
      animation-delay: 0s !important;
      transition-duration: 0s !important;
      transition-delay: 0s !important;
    }
  `;
  document.head.appendChild(style);
});

// Nettoyage final
afterAll(() => {
  // Nettoyer les ressources
  jest.clearAllTimers();
  jest.useRealTimers();
});