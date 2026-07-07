<?php

/**
 * Application configuration template.
 *
 * Copy this file to `config.php` and fill in the real values.
 * `config.php` is git-ignored and must never be committed.
 */

return [
    'database' => [
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => (int) (getenv('DB_PORT') ?: 3306),
        'name'     => getenv('DB_NAME') ?: 'api4api',
        'user'     => getenv('DB_USER') ?: 'api4api',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset'  => 'utf8mb4',
    ],

    'mail' => [
        // Recipient that receives threshold-exceeded alerts.
        'alert_recipient' => getenv('ALERT_RECIPIENT') ?: 'alerts@example.com',
        'sender'          => getenv('MAIL_SENDER') ?: 'noreply@example.com',
    ],
];
