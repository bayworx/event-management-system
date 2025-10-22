<?php

// Suppress PHP warnings and deprecations in development to prevent headers already sent issues
if (($_ENV['APP_ENV'] ?? 'prod') === 'dev') {
    // Set custom error handler for PHP 8.4 compatibility
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        // Suppress E_STRICT constant deprecation warnings
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
        
        return false;
    }, E_ALL);
    
    // PHP 8.4+ compatibility - E_STRICT constant is deprecated
    if (PHP_VERSION_ID >= 80400) {
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_WARNING);
    } else {
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_STRICT & ~E_WARNING);
    }
}

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}