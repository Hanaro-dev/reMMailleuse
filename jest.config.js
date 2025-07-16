module.exports = {
  // Environnement de test
  testEnvironment: 'jsdom',
  
  // Répertoires de test
  testMatch: [
    '<rootDir>/tests/**/*.test.js',
    '<rootDir>/tests/**/*.spec.js'
  ],
  
  // Fichiers à ignorer
  testPathIgnorePatterns: [
    '<rootDir>/node_modules/',
    '<rootDir>/tests/fixtures/'
  ],
  
  // Configuration de couverture
  collectCoverage: true,
  coverageDirectory: 'coverage',
  coverageReporters: ['text', 'lcov', 'html'],
  collectCoverageFrom: [
    'assets/js/**/*.js',
    '!assets/js/**/*.min.js'
  ],
  coverageThreshold: {
    global: {
      branches: 80,
      functions: 80,
      lines: 80,
      statements: 80
    }
  },
  
  // Configuration setup
  setupFilesAfterEnv: ['<rootDir>/tests/setup.js'],
  
  // Mocks
  moduleNameMapping: {
    '^@/(.*)$': '<rootDir>/assets/js/$1'
  },
  
  // Transformation
  transform: {
    '^.+\\.js$': 'babel-jest'
  },
  
  // Timeouts
  testTimeout: 10000,
  
  // Reporters
  reporters: [
    'default',
    ['jest-junit', {
      outputDirectory: 'coverage',
      outputName: 'jest-junit.xml'
    }]
  ],
  
  // Variables d'environnement
  globals: {
    'APP_ENV': 'testing'
  }
};