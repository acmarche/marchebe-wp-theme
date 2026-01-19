<?php

namespace AcMarche\Theme\Lib;

class Mailer
{
    public static function sendError(string $subject, string $message): void
    {
        $to = $_ENV['WEBMASTER_EMAIL'];
        wp_mail($to, $subject, $message);
    }
}