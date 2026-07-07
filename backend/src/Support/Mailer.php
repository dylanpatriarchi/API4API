<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Config;

/**
 * Sends threshold-exceeded alert e-mails.
 */
final class Mailer
{
    /**
     * Notify the configured recipient that a metric crossed its threshold.
     */
    public static function sendThresholdAlert(string $metric, float $value, float $threshold): void
    {
        $recipient = (string) Config::get('mail.alert_recipient', '');
        $sender    = (string) Config::get('mail.sender', '');

        if ($recipient === '') {
            return;
        }

        $subject = sprintf('[API4API] Threshold exceeded: %s', $metric);
        $body    = sprintf(
            "The metric \"%s\" reached %.2f, exceeding its threshold of %.2f.\n"
            . "Please check the beehive status.",
            $metric,
            $value,
            $threshold
        );

        $headers = sprintf("From: %s\r\nContent-Type: text/plain; charset=utf-8", $sender);

        mail($recipient, $subject, $body, $headers);
    }
}
