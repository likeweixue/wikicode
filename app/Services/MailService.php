<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Setting;
use RuntimeException;

final class MailService
{
    private const TIMEOUT = 15;
    private const CIPHER = 'aes-256-gcm';
    private const IV_LENGTH = 12;
    private const TAG_LENGTH = 16;

    public static function encryptPassword(string $password): string
    {
        return self::encrypt($password);
    }

    public static function send(string $recipient, string $subject, string $body): void
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid recipient email address.');
        }

        $host = trim((string) Setting::get('smtp_host', ''));
        $port = filter_var(Setting::get('smtp_port', 587), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);
        $encryption = (string) Setting::get('smtp_encryption', 'tls');
        $username = (string) Setting::get('smtp_username', '');
        $encryptedPassword = (string) Setting::get('smtp_password', '');
        $fromAddress = (string) Setting::get('smtp_from_address', '');
        $fromName = (string) Setting::get('smtp_from_name', Setting::get('site_name', 'wikicode'));

        if ($host === '' || $port === false || !in_array($encryption, ['none', 'tls', 'ssl'], true)) {
            throw new RuntimeException('SMTP server settings are incomplete.');
        }
        if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $fromName)) {
            throw new RuntimeException('SMTP sender settings are invalid.');
        }
        $password = $username !== '' && $encryptedPassword !== '' ? self::decrypt($encryptedPassword) : '';
        $peerName = trim($host, '[]');
        $connectHost = str_contains($host, ':') && !str_starts_with($host, '[') ? '[' . $host . ']' : $host;
        $transport = $encryption === 'ssl' ? 'ssl' : 'tcp';
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'peer_name' => $peerName,
                'SNI_enabled' => true,
            ],
        ]);
        $socket = @stream_socket_client(
            $transport . '://' . $connectHost . ':' . $port,
            $errorNumber,
            $errorMessage,
            self::TIMEOUT,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if ($socket === false) {
            $detail = $errorMessage !== '' ? $errorMessage : 'connection error ' . $errorNumber;
            throw new RuntimeException('Unable to connect to SMTP server: ' . $detail);
        }

        try {
            stream_set_timeout($socket, self::TIMEOUT);
            self::readResponse($socket, [220]);
            self::command($socket, 'EHLO ' . self::clientHostname(), [250]);

            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS', [220]);
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Unable to establish STARTTLS encryption.');
                }
                self::command($socket, 'EHLO ' . self::clientHostname(), [250]);
            }

            if ($username !== '') {
                self::command($socket, 'AUTH LOGIN', [334]);
                self::command($socket, base64_encode($username), [334]);
                self::command($socket, base64_encode($password), [235]);
            }

            self::command($socket, 'MAIL FROM:<' . $fromAddress . '>', [250]);
            self::command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
            self::command($socket, 'DATA', [354]);
            $message = self::message($recipient, $subject, $body, $fromAddress, $fromName);
            $message = self::dotStuff($message);
            $terminator = str_ends_with($message, "\r\n") ? ".\r\n" : "\r\n.\r\n";
            self::write($socket, $message . $terminator);
            self::readResponse($socket, [250]);
            self::command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    private static function message(
        string $recipient,
        string $subject,
        string $body,
        string $fromAddress,
        string $fromName
    ): string {
        $domain = substr($fromAddress, strrpos($fromAddress, '@') + 1);
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . self::encodeHeader($fromName) . ' <' . $fromAddress . '>',
            'To: <' . $recipient . '>',
            'Subject: ' . self::encodeHeader($subject),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $domain . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        $encodedBody = chunk_split(base64_encode($body), 76, "\r\n");
        return implode("\r\n", $headers) . "\r\n\r\n" . $encodedBody;
    }

    private static function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private static function dotStuff(string $message): string
    {
        $message = str_replace("\r\n.", "\r\n..", $message);
        return str_starts_with($message, '.') ? '.' . $message : $message;
    }

    private static function clientHostname(): string
    {
        $hostname = gethostname() ?: 'localhost';
        $hostname = preg_replace('/[^A-Za-z0-9.-]/', '-', $hostname) ?: 'localhost';
        return $hostname;
    }

    private static function command($socket, string $command, array $expected): string
    {
        self::write($socket, $command . "\r\n");
        return self::readResponse($socket, $expected);
    }

    private static function readResponse($socket, array $expected): string
    {
        $response = '';
        do {
            $line = fgets($socket);
            if ($line === false) {
                $metadata = stream_get_meta_data($socket);
                if (!empty($metadata['timed_out'])) {
                    throw new RuntimeException('SMTP server response timed out.');
                }
                throw new RuntimeException('SMTP server closed the connection unexpectedly.');
            }
            $response .= $line;
            if (strlen($response) > 65536 || !preg_match('/^(\d{3})([ -])/', $line, $matches)) {
                throw new RuntimeException('SMTP server returned an invalid response.');
            }
            $code = (int) $matches[1];
            $continued = $matches[2] === '-';
        } while ($continued);

        if (!in_array($code, $expected, true)) {
            throw new RuntimeException('SMTP server returned an unexpected response: ' . trim($response));
        }
        return $response;
    }

    private static function write($socket, string $data): void
    {
        $length = strlen($data);
        $written = 0;
        while ($written < $length) {
            $result = fwrite($socket, substr($data, $written));
            if ($result === false || $result === 0) {
                $metadata = stream_get_meta_data($socket);
                if (!empty($metadata['timed_out'])) {
                    throw new RuntimeException('SMTP server write timed out.');
                }
                throw new RuntimeException('Unable to write to SMTP server.');
            }
            $written += $result;
        }
    }

    private static function encrypt(string $password): string
    {
        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $password,
            self::CIPHER,
            self::encryptionKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            'wikicode-smtp-password',
            self::TAG_LENGTH
        );
        if ($ciphertext === false || strlen($tag) !== self::TAG_LENGTH) {
            throw new RuntimeException('Unable to encrypt SMTP password.');
        }
        return 'v1:' . base64_encode($iv . $tag . $ciphertext);
    }

    private static function decrypt(string $encrypted): string
    {
        if (!str_starts_with($encrypted, 'v1:')) {
            throw new RuntimeException('Stored SMTP password has an invalid format.');
        }
        $payload = base64_decode(substr($encrypted, 3), true);
        if ($payload === false || strlen($payload) < self::IV_LENGTH + self::TAG_LENGTH) {
            throw new RuntimeException('Stored SMTP password is invalid.');
        }
        $iv = substr($payload, 0, self::IV_LENGTH);
        $tag = substr($payload, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($payload, self::IV_LENGTH + self::TAG_LENGTH);
        $password = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            self::encryptionKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            'wikicode-smtp-password'
        );
        if ($password === false) {
            throw new RuntimeException('Unable to decrypt SMTP password.');
        }
        return $password;
    }

    private static function encryptionKey(): string
    {
        $appKey = getenv('APP_KEY');
        if (!is_string($appKey) || $appKey === '') {
            throw new RuntimeException('APP_KEY is required for SMTP password encryption.');
        }
        return hash('sha256', $appKey, true);
    }
}
