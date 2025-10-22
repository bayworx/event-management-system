# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

Event Management System built with **Symfony 6.4** and **MariaDB 11.8.3**. A multi-tenant system supporting multiple events with attendee registration, email-based authentication, admin management, and secure file downloads.

**Tech Stack:** PHP 8.1+, Doctrine ORM, Twig, Bootstrap 5, VichUploaderBundle, FOSCKEditorBundle, KnpPaginatorBundle, DomPDF

## Essential Commands

### Development Server
```bash
# Start development server (suppresses PHP 8.4 deprecation warnings)
./serve.sh

# Or standard PHP server
php -S localhost:8000 -t public/

# Or using composer script
composer serve
```

### Database & Migrations
```bash
# Create database
bin/console doctrine:database:create

# Run migrations
bin/console doctrine:migrations:migrate

# Create new migration after entity changes
bin/console doctrine:migrations:diff

# Validate schema against mapping
bin/console doctrine:schema:validate
```

### User Management
```bash
# Create super admin
bin/console app:create-admin admin@example.com --name="Admin Name" --password="password" --super-admin

# Create regular admin (for specific events)
bin/console app:create-admin user@example.com --name="Admin Name" --password="password"

# Create sample data for testing
bin/console app:create-sample-data

# Generate test data
bin/console app:generate-test-data
```

### Testing
```bash
# Run all tests
bin/phpunit

# Run specific test directory
bin/phpunit tests/Controller/

# Note: Test suite is currently minimal - check tests/bootstrap.php for setup
```

### Cache Management
```bash
# Clear all caches
bin/console cache:clear

# Warm up cache
bin/console cache:warmup

# Clear specific cache pool
bin/console cache:pool:clear cache.app

# Production cache clear
bin/console cache:clear --env=prod
```

### Configuration & Debugging
```bash
# Initialize application configuration
bin/console app:init-config

# View all routes
bin/console debug:router

# View specific route details
bin/console debug:router event_show

# View container services
bin/console debug:container

# View firewall configuration
bin/console debug:firewall

# View all event listeners
bin/console debug:event-dispatcher
```

### Maintenance
```bash
# Clean old log files
bin/console app:log-cleanup

# Test database connection
bin/console app:test-connection
```

### Linting & Validation
```bash
# Lint Twig templates
bin/console lint:twig templates/

# Lint YAML files
bin/console lint:yaml config/

# Validate container configuration
bin/console lint:container
```

## Architecture Overview

### Entity Relationships (Core Data Model)

**Event** (central entity)
- Has many `Attendees` (one-to-many)
- Has many `EventFiles` (one-to-many, cascade persist/remove)
- Has many `AgendaItems` (one-to-many, cascade persist/remove, ordered by startTime/sortOrder)
- Has many `EventPresenters` (one-to-many, cascade persist/remove, ordered by sortOrder)
- Many-to-many with `Administrators` (join table: event_administrators)

**Administrator** (password-based auth)
- Many-to-many with `Events` via managedEvents
- Has `isSuperAdmin` flag - super admins access ALL events, regular admins only assigned events
- Separate authentication firewall at `/admin` with form login

**Attendee** (email-based passwordless auth)
- Belongs to one `Event`
- Has email verification token/status (`emailVerificationToken`, `isVerified`)
- Has check-in status (`isCheckedIn`, `checkedInAt`)
- Can download event files only after email verification

**EventFile**
- Uses VichUploaderBundle for file uploads (mapping: `event_files`)
- Tracks download count, has sortOrder for display ordering
- Stores metadata: originalName, mimeType, fileSize

**AgendaItem**
- Represents schedule items for an event
- Types: session, break, lunch, keynote, workshop, networking, other
- Can optionally link to a `Presenter`
- Ordered by startTime then sortOrder

### Authentication Architecture

**Two Separate Firewalls:**

1. **Admin Firewall** (`/admin` pattern)
   - Provider: `administrators` entity
   - Form login at `admin_login`
   - Logout at `admin_logout`
   - Required role: `ROLE_ADMIN` or `ROLE_SUPER_ADMIN`

2. **Main Firewall** (`/` pattern)
   - Provider: `all_users` (chains attendees + administrators)
   - Form login at `app_login`
   - Supports both Attendees and Administrators
   - Required roles: `ROLE_ATTENDEE` for `/attendee/*` routes

**Authorization Pattern:**
- Super admins: Access all events system-wide
- Regular admins: Access only events in their `managedEvents` collection
- Attendees: Access only their registered event via `Attendee->event` relationship

### Service Layer

**ConfigurationService** (`src/Service/ConfigurationService.php`)
- Manages app configuration with caching (1-hour TTL)
- Grouped settings: company info, app preferences, email settings, event settings, footer settings
- Use `get()` and `set()` methods, cache auto-clears on update

**ApplicationLogger** (`src/Service/ApplicationLogger.php`)
- Specialized logging channels: security, audit, event management, user management, app errors
- Use `logSecurityEvent()`, `logAuditEvent()`, `logEventOperation()`, etc.

**EventImportService** (`src/Service/EventImportService.php`)
- Handles bulk event imports
- Check this service for import logic patterns

**FeaturedEventService** (`src/Service/FeaturedEventService.php`)
- Manages featured events on homepage

### Controllers Organization

**Public Controllers** (`src/Controller/`)
- `HomepageController` - Landing page
- `EventController` - Event listing, details, registration
- `AttendeeController` - Attendee dashboard
- `SecurityController` - Login/logout for attendees

**Admin Controllers** (`src/Controller/Admin/`)
- `AdminDashboardController` - Admin overview
- `AdminEventController` - Event CRUD + file management
- `AdminAttendeeController` - Attendee management, check-in, CSV export
- `AdminUserController` - Administrator user management
- `AdminSecurityController` - Admin login/logout
- `AdminAgendaController` - Agenda item management
- `AdminPresenterController` - Presenter management
- `AdminMessageController` - Attendee messaging system
- `AdminConfigController` - System configuration UI
- `AdminLogController` - View application logs

### File Upload Configuration

VichUploaderBundle mappings (see `config/packages/vich_uploader.yaml`):
- `event_banners` - Event banner images
- `event_files` - Event materials (PDFs, presentations, etc.)

**Important:** File entities use `VichUploaderBundle` annotations. When uploading:
1. Set the `File` property (e.g., `setBannerFile()`)
2. VichUploader auto-populates filename properties
3. Always call `flush()` after setting files to trigger upload

### Form Patterns

Forms in `src/Form/`:
- Entity forms follow pattern: `{Entity}Type.php`
- Use `DataTransformer/JsonToArrayTransformer.php` for JSON field transformations
- Collection forms use `{Entity}CollectionType.php` pattern
- Filter forms use `{Context}FilterType.php` pattern

### Repository Pattern

All repositories extend `ServiceEntityRepository`:
- Custom query methods live in respective Repository classes
- Use QueryBuilder for complex queries
- Pagination support via KnpPaginatorBundle

### Key Business Rules

1. **Email Verification Required:** Attendees must verify email before downloading event files
2. **Slug Uniqueness:** Event slugs are unique and used in URLs (`/event/{slug}`)
3. **Administrator Scope:** Regular admins see only assigned events; super admins see all
4. **File Access Control:** Only verified attendees of an event can download its files
5. **Cascade Deletions:** Deleting an Event cascades to Attendees, EventFiles, AgendaItems, EventPresenters
6. **Sort Order Fields:** AgendaItems and EventFiles have `sortOrder` for display ordering

## Development Workflow

### Creating New Features

1. **Entities:** Create in `src/Entity/`, then run `doctrine:migrations:diff`
2. **Forms:** Create in `src/Form/`, reference entity
3. **Controllers:** 
   - Public features: `src/Controller/`
   - Admin features: `src/Controller/Admin/`
   - Use `#[Route]` attributes for routing
4. **Templates:**
   - Admin: `templates/admin/`
   - Public: `templates/event/`, `templates/attendee/`
   - Base template: `templates/base.html.twig`
5. **Services:** Create in `src/Service/`, auto-wired via constructor injection

### Making Entity Changes

```bash
# 1. Modify entity in src/Entity/
# 2. Generate migration
bin/console doctrine:migrations:diff

# 3. Review migration in migrations/
# 4. Run migration
bin/console doctrine:migrations:migrate

# 5. Validate
bin/console doctrine:schema:validate
```

### Working with Logs

Application logs are stored in `var/log/` directory:
- `dev.log` - Development environment
- `prod.log` - Production environment
- Custom channels defined in `config/packages/monolog.yaml`

Use `ApplicationLogger` service for structured logging with context.

### Environment Configuration

`.env` file contains:
- `DATABASE_URL` - MariaDB connection string
- `APP_ENV` - Environment (dev/prod/test)
- `APP_SECRET` - Symfony secret for CSRF tokens
- `MAILER_DSN` - Email configuration for verification emails

**Never commit `.env.local` or files with credentials**

## Common Patterns

### Checking Admin Permissions in Controllers

```php
// Check if super admin
$isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');

// Get accessible events for current admin
$admin = $this->getUser();
if (!$isSuperAdmin) {
    $events = $admin->getManagedEvents();
}
```

### Using ConfigurationService

```php
public function __construct(
    private ConfigurationService $configService
) {}

// Get single value
$companyName = $this->configService->get('company.name', 'Default Name');

// Get grouped values
$emailSettings = $this->configService->getEmailSettings();

// Set value
$this->configService->set('company.name', 'New Name', 'company', 'Company name');
```

### Using ApplicationLogger

```php
public function __construct(
    private ApplicationLogger $appLogger
) {}

// Log security events
$this->appLogger->logSecurityEvent('login_success', $user, $request);

// Log audit trail
$this->appLogger->logAuditEvent('event_updated', $admin, ['event_id' => 123]);
```

### File Downloads with Access Control

Check `EventController::downloadFile()` for the pattern:
1. Verify attendee is logged in
2. Check attendee belongs to event
3. Verify email is confirmed
4. Increment download counter
5. Return file response with proper headers

## Database Schema Notes

- **Join Table:** `event_administrators` (many-to-many Event ↔ Administrator)
- **Unique Constraints:** Event.slug, Attendee.email, Administrator.email
- **Timestamps:** Most entities have createdAt/updatedAt
- **Soft Deletes:** Not implemented - uses hard deletes with cascades
- **JSON Fields:** Administrator.roles, Attendee.roles store arrays as JSON

## Configuration Management

System uses `AppConfig` entity for dynamic configuration:
- Keys use dot notation: `company.name`, `email.from_email`
- Categories: company, app, email, events, footer
- Access via `ConfigurationService` (cached)
- Initialize defaults with `bin/console app:init-config`
- Admin UI available at `/admin/config`

## Twig Extensions

Custom extensions in `src/Twig/`:
- `ConfigExtension` - Access config values in templates
- `DateTimeExtension` - Date/time formatting helpers
- `FormatExtension` - Text formatting utilities
- `MessageExtension` - Flash message rendering

## Known Gotchas

1. **PHP 8.4 Deprecations:** Use `./serve.sh` to suppress warnings in development
2. **VichUploader Timing:** Set `updatedAt = new \DateTime()` when setting files to trigger upload
3. **Email Verification:** Check `Attendee->isVerified` before allowing file downloads
4. **Super Admin Check:** Always check `ROLE_SUPER_ADMIN` before filtering events by administrator
5. **Form CSRF:** All forms have CSRF enabled - include `{{ form_row(form._token) }}` in custom form rendering
6. **Event Slug URLs:** Use slug in routes, not ID: `/event/{slug}` not `/event/{id}`
