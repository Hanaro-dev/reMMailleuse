# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

ReMmailleuse is a professional website for an artisan specializing in traditional knitting repair ("remaillage"). It's a Progressive Web App (PWA) built with vanilla HTML/CSS/JavaScript frontend and PHP backend, using JSON files for data storage (no database required).

**Status**: ✅ Production-ready (Sécurisé et optimisé - Juillet 2025)

## Development Commands

### Frontend Development
```bash
# Start development server
npm run dev                  # Runs http-server on port 8000

# Build commands
npm run build               # Production build (clean + validate + minify + optimize)
npm run build:dev           # Development build (minify only)
npm run build:prod          # Full production build with validation

# Run tests
npm run test                # Runs both PHP and JS tests
npm run test:js             # Jest tests only
npm run test:coverage       # Jest with coverage report

# Code quality
npm run validate            # Runs all validators (HTML, CSS, JS)
npm run lint                # Alias for validate

# Asset optimization
npm run optimize:images     # Optimizes images with imagemin
npm run clean               # Clean minified files and bundles
```

### Backend Development
```bash
# PHP tests
composer test               # Run PHPUnit tests
composer test:unit          # Unit tests only
composer test:integration   # Integration tests only
composer test:coverage      # Generate coverage report

# Code quality
composer analyse            # PHPStan static analysis
composer cs                 # PHP CodeSniffer check
composer cs:fix            # Auto-fix code style issues
```

## Architecture Overview

### Data Flow
1. **Frontend** (index.html) → Makes API calls to `/api/` endpoints
2. **API Layer** (PHP) → Validates requests, manages data operations
3. **Data Storage** (`/data/*.json`) → JSON files store all content
4. **Admin Interface** (`/admin/`) → Protected area for content management

### Key API Endpoints
- `/api/contact.php` - Contact form submission with file upload support
- `/api/admin-data.php` - CRUD operations for content management
- `/api/auth.php` - Admin authentication
- `/api/upload.php` - Image upload handling
- `/api/backup.php` - Automated backup management

### Security Layers ✅ Updated July 2025
1. **CSRF Protection** - All forms use CSRF tokens
2. **Rate Limiting** - API endpoints have rate limits
3. **Input Validation** - Server-side validation on all inputs
4. **File Upload Security** - Strict MIME type and size validation
5. **Admin Protection** - Secure password hash configuration
6. **XSS Protection** - HTMLSanitizer.js implementation across all JS files
7. **Authentication Security** - Session management with proper expiration

### Content Management
All content is stored in JSON files under `/data/`:
- `content.json` - Main site content
- `services.json` - Services and pricing
- `gallery.json` - Portfolio items
- `settings.json` - Site configuration

Backups are automatically created in `/data/backups/` with timestamps.

### Frontend Architecture ✅ Modularized July 2025
- **Single Page Application** with vanilla JavaScript
- **Service Worker** for offline functionality and caching
- **Lazy Loading** for images and non-critical resources
- **Modular Architecture**: 
  - `admin.js` (597 lines, orchestrator)
  - `/admin/` modules: auth-manager, content-manager, data-manager, form-manager, image-manager, render-manager, ui-manager, utilities
- **Bundle Optimization** for production builds

### Testing Strategy
- **JavaScript**: Jest with jsdom, 80% coverage threshold
- **PHP**: PHPUnit 10.5 with unit and integration tests
- **E2E Tests**: Available via `npm run test:e2e`

### Performance Optimization ✅ Enhanced July 2025
- Service Worker caching strategy
- Minified assets in production (CSS + JS)
- Lazy-loaded images and components
- Preloaded critical resources
- Cache managers for API responses
- **Modular bundling** for admin interface
- **Automated cleanup** of temporary files and logs
- **Build validation** pipeline with pre/post hooks

## Recent Improvements (July 2025)

### Security Enhancements
- ✅ Removed hardcoded passwords from documentation
- ✅ Implemented consistent XSS protection with HTMLSanitizer.js
- ✅ Updated authentication configuration with secure password hashing
- ✅ Added comprehensive .gitignore for security-sensitive files

### Performance Optimizations  
- ✅ Modularized large JavaScript files (admin.js: 1218 → 597 lines)
- ✅ Created 5 specialized admin modules for better maintainability
- ✅ Enhanced build process with development and production modes
- ✅ Implemented automated cleanup for temporary files and logs

### Configuration Updates
- ✅ Updated package.json with proper dependency management
- ✅ Fixed repository URLs and metadata
- ✅ Added bundle optimization scripts
- ✅ Implemented build validation pipeline

## Important Notes for Future Development

1. **Security**: All innerHTML usage has been replaced with HTMLSanitizer.setHTML() or DOM manipulation
2. **Authentication**: Change the password hash in `/api/auth.php` for production deployment
3. **Build Process**: Use `npm run build` for production deployments
4. **Testing**: Run `npm run validate` before committing changes
5. **Cleanup**: Automated cleanup runs periodically, but manual cleanup available via scripts