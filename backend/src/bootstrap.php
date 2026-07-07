<?php

declare(strict_types=1);

use App\Core\Config;

// Application bootstrap: registers autoloading and loads configuration.
// On completion the App\ namespace is autoloadable and the config is loaded.

$root = dirname(__DIR__);

// Prefer Composer's autoloader; fall back to a minimal PSR-4 loader so the
// API also runs on shared hosting where `composer install` is not available.
$composerAutoload = $root . '/vendor/autoload.php';

if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    spl_autoload_register(static function (string $class) use ($root): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file     = $root . '/src/' . $relative . '.php';

        if (is_file($file)) {
            require $file;
        }
    });
}

Config::load($root . '/config/config.php');
