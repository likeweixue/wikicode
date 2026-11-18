<?php

declare(strict_types=1);

namespace App\Core;

final class I18n
{
    private static string $locale = 'zh-CN';
    private static array $lines = [];

    public static function boot(): void
    {
        $user = Auth::user();
        $locale = $_SESSION['locale'] ?? ($user['locale'] ?? Setting::get('default_language', 'zh-CN'));
        self::setLocale(is_string($locale) ? $locale : 'zh-CN');
    }

    public static function setLocale(string $locale): void
    {
        self::$locale = $locale === 'en' ? 'en' : 'zh-CN';
        $_SESSION['locale'] = self::$locale;
        $file = dirname(__DIR__, 2) . '/lang/' . self::$locale . '.php';
        self::$lines = is_file($file) ? require $file : [];
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function get(string $key, array $replace = []): string
    {
        $line = self::$lines[$key] ?? $key;
        foreach ($replace as $name => $value) {
            $line = str_replace(':' . $name, (string) $value, $line);
        }
        return $line;
    }
}
