<?php

// Early error suppression for PHP 8.4 compatibility - must be before any other includes
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

// Set custom error handler to suppress PHP 8.4 compatibility warnings
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Suppress E_STRICT constant deprecation warnings specifically
    if (strpos($errstr, 'Constant E_STRICT is deprecated') !== false) {
        return true;
    }
    
    // Suppress regex compilation warnings in Symfony routing (PHP 8.4 compatibility)
    if (strpos($errstr, 'preg_match(): Compilation failed: length of lookbehind assertion is not limited') !== false) {
        return true;
    }
    
    // Suppress all deprecation warnings
    if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
        return true;
    }
    
    // Suppress warnings in development
    if ($errno === E_WARNING && ($_ENV['APP_ENV'] ?? 'prod') === 'dev') {
        return true;
    }
    
    // Let other errors pass through to default handler
    return false;
}, E_ALL);

// Also set error_reporting for good measure
if (PHP_VERSION_ID >= 80400) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_STRICT);
}

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
