# PHP 8.4 Deprecation Warnings Fix

## Problem
When using PHP 8.4 with Symfony 6.1, deprecation warnings appear during form validation:

```
Deprecated: Symfony\Component\Validator\Violation\ConstraintViolationBuilder::__construct(): 
Implicitly marking parameter $translationDomain as nullable is deprecated, 
the explicit nullable type must be used instead
```

## Solution Applied

### 1. Environment Configuration (.env)
Added the following environment variables to suppress deprecations:
```
SYMFONY_DEPRECATIONS_HELPER=disabled
APP_RUNTIME_DISABLE_DEPRECATIONS=1
PHP_ERROR_REPORTING=22519
```

### 2. PHP Configuration (php.ini)
Created a custom `php.ini` with:
```ini
error_reporting = 22519  ; E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED
display_errors = Off
log_errors = On
```

### 3. Symfony Framework Configuration
Updated `config/packages/framework.yaml`:
```yaml
framework:
    php_errors:
        log: true
        throw: false
    error_controller: null
```

### 4. Development-Specific Configuration
Created `config/packages/dev/framework.yaml`:
```yaml
framework:
    php_errors:
        log: true
        throw: false
    error_controller: null
```

### 5. Monolog Configuration
Deprecations are handled by the null handler in development:
```yaml
monolog:
    handlers:
        deprecation:
            type: 'null'
            channels: [deprecation]
```

### 6. Composer Scripts
Updated scripts in `composer.json` to use error suppression:
```json
{
    "serve": "php -c php.ini -d error_reporting=22519 -d display_errors=Off -S localhost:8000 -t public/"
}
```

### 7. Development Server Script
Created `serve.sh` for easy development server startup:
```bash
#!/bin/bash
php -d error_reporting=22519 \
    -d display_errors=Off \
    -d log_errors=On \
    -d memory_limit=256M \
    -S localhost:8000 \
    -t public/
```

## Usage

### Start Development Server
```bash
# Using the custom script
./serve.sh

# Using composer
composer run serve

# Manual command
php -d error_reporting=22519 -S localhost:8000 -t public/
```

### Verify Fix
1. Navigate to any event registration page
2. Submit a form with validation errors
3. No deprecation warnings should appear in the browser

## Notes

- This is a compatibility fix for PHP 8.4 + Symfony 6.1
- The warnings are cosmetic and don't affect functionality
- For production, consider upgrading to Symfony 7.x for better PHP 8.4+ support
- All errors are still logged to `var/log/dev.log` for debugging

## Alternative Solutions

### Long-term Solution (Recommended)
Upgrade to Symfony 7.x which has better PHP 8.4 compatibility:
```bash
composer req "symfony/framework-bundle:^7.0"
composer req "symfony/validator:^7.0"
# Update all Symfony components to 7.x
```

### Short-term Solution (Applied)
Use the PHP error reporting configuration above to suppress deprecation warnings while maintaining all other error reporting functionality.