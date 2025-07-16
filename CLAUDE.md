# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

ReMmailleuse is a professional website for an artisan specializing in traditional knitting repair ("remaillage"). It's a Progressive Web App (PWA) built with vanilla HTML/CSS/JavaScript frontend and PHP backend, using JSON files for data storage (no database required).

## Development Commands

### Frontend Development
```bash
# Start development server
npm run dev                  # Runs http-server on port 8000

# Build for production
npm run build               # Minifies CSS and JS files

# Run tests
npm run test                # Runs both PHP and JS tests
npm run test:js             # Jest tests only
npm run test:coverage       # Jest with coverage report

# Code quality
npm run validate            # Runs all validators (HTML, CSS, JS)
npm run lint                # Alias for validate

# Image optimization
npm run optimize:images     # Optimizes images with imagemin
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

### Security Layers
1. **CSRF Protection** - All forms use CSRF tokens
2. **Rate Limiting** - API endpoints have rate limits
3. **Input Validation** - Server-side validation on all inputs
4. **File Upload Security** - Strict MIME type and size validation
5. **Admin Protection** - HTTP Basic Auth via .htaccess

### Content Management
All content is stored in JSON files under `/data/`:
- `content.json` - Main site content
- `services.json` - Services and pricing
- `gallery.json` - Portfolio items
- `settings.json` - Site configuration

Backups are automatically created in `/data/backups/` with timestamps.

### Frontend Architecture
- **Single Page Application** with vanilla JavaScript
- **Service Worker** for offline functionality and caching
- **Lazy Loading** for images and non-critical resources
- **Module Pattern** for JavaScript organization

### Testing Strategy
- **JavaScript**: Jest with jsdom, 80% coverage threshold
- **PHP**: PHPUnit 10.5 with unit and integration tests
- **E2E Tests**: Available via `npm run test:e2e`

### Performance Optimization
- Service Worker caching strategy
- Minified assets in production
- Lazy-loaded images
- Preloaded critical resources
- Cache managers for API responses