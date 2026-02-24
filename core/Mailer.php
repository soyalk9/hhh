<?php
namespace Core;

class Mailer
{
    public static function send(array $config, string $to, string $subject, string $body): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: ' . ($config['mail_from_name'] ?? 'DevBuzz') . ' <' . ($config['mail_from'] ?? 'noreply@example.com') . '>',
        ];
        return mail($to, $subject, $body, implode("\r\n", $headers));
    }
}
