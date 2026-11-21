<?php

declare(strict_types=1);

namespace App\Core;

/**
 * 统一的线性 SVG 图标库。
 *
 * 所有图标：24x24 viewBox、fill=none、stroke=currentColor、
 * 1.6px 圆角端点 —— 跟随主题文字色 / 激活强调色，风格统一。
 */
final class Icons
{
    /**
     * key => 内层 path 片段（不含外层 <svg>）。
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            // —— 通用 ——
            'all' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',

            // —— 开发 ——
            'frontend' => '<path d="M8 8l-4 4 4 4"/><path d="M16 8l4 4-4 4"/><path d="M13 6l-2 12"/>',
            'backend' => '<rect x="4" y="4" width="16" height="5" rx="1.5"/><rect x="4" y="10" width="16" height="5" rx="1.5"/><rect x="4" y="16" width="16" height="4" rx="1.5"/><circle cx="7.5" cy="6.5" r=".7" fill="currentColor" stroke="none"/><circle cx="7.5" cy="12.5" r=".7" fill="currentColor" stroke="none"/>',
            'ai' => '<rect x="7" y="7" width="10" height="10" rx="1.5"/><path d="M10 7V4M14 7V4M10 20v-3M14 20v-3M7 10H4M7 14H4M20 10h-3M20 14h-3"/><circle cx="12" cy="12" r="2"/>',
            'web' => '<rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="M3 9h18"/><circle cx="12" cy="14" r="3.5"/><path d="M12 10.5v7M8.5 14h7"/>',
            'mobile' => '<rect x="7" y="3" width="10" height="18" rx="2"/><path d="M11 18h2"/>',

            // —— 建站 ——
            'cms' => '<rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M8 20h8M12 16v4"/><path d="M7 8h6M7 11h8"/>',
            'forum' => '<path d="M21 11.5a8.5 8.5 0 0 1-12.3 7.6L4 20l.9-4.6A8.5 8.5 0 1 1 21 11.5Z"/><path d="M8.5 10.5h6M8.5 13.5h4"/>',
            'blog' => '<path d="M6 3h9l3 3v15H6z"/><path d="M15 3v3h3"/><path d="M9 11h6M9 14h6M9 17h4"/>',
            'image-host' => '<rect x="3" y="4" width="18" height="16" rx="1.5"/><circle cx="9" cy="10" r="1.8"/><path d="m4 17 4-4 3 3 3-3 6 6"/>',
            'cloud-disk' => '<path d="M7 18a4 4 0 0 1-.5-7.97A5.5 5.5 0 0 1 17 10.5a3.5 3.5 0 0 1 .5 6.96"/><path d="M12 13v4m-2-2 2 2 2-2"/>',
            'download' => '<path d="M12 3v11m0 0 4-4m-4 4-4-4"/><path d="M5 20h14"/>',
            'nav' => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>',

            // —— 娱乐 / 工具 ——
            'game' => '<path d="M7 8h10a4 4 0 0 1 4 4v1a3 3 0 0 1-5.5 1.7L14.5 13h-5L8.5 14.7A3 3 0 0 1 3 13v-1a4 4 0 0 1 4-4Z"/><path d="M8 11v2M7 12h2M15 11.5h.01M16.5 12.5h.01"/>',
            'entertainment' => '<path d="M9 18V6l9-2v12"/><circle cx="7" cy="18" r="2.5"/><circle cx="16" cy="16" r="2.5"/>',
            'plugin' => '<path d="M6 4h6a2 2 0 0 1 2 2v1h2a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-2v1a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/><path d="M10 9v4M8 11h4"/>',
            'industrial' => '<circle cx="12" cy="12" r="3.2"/><path d="M12 3v2.5M12 18.5V21M3 12h2.5M18.5 12H21M5.5 5.5l1.8 1.8M16.7 16.7l1.8 1.8M18.5 5.5l-1.8 1.8M7.3 16.7l-1.8 1.8"/>',

            // —— 兜底 ——
            'other' => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',

            // —— 仓库操作 ——
            'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
            'star' => '<path d="m12 3 2.7 5.6 6.3.9-4.5 4.4 1 6.1-5.5-2.9-5.5 2.9 1-6.1L3 9.5l6.3-.9L12 3Z"/>',
            'star-filled' => '<path fill="currentColor" stroke="none" d="m12 3 2.7 5.6 6.3.9-4.5 4.4 1 6.1-5.5-2.9-5.5 2.9 1-6.1L3 9.5l6.3-.9L12 3Z"/>',
            'fork' => '<circle cx="6" cy="5" r="2"/><circle cx="18" cy="5" r="2"/><circle cx="12" cy="19" r="2"/><path d="M6 7v2a4 4 0 0 0 4 4h4a4 4 0 0 0 4-4V7M12 13v4"/>',
        ];
    }

    /**
     * 取单个图标的完整 <svg> 字符串。未知 key 回退到 other。
     */
    public static function svg(string $key, int $size = 16): string
    {
        $map = self::all();
        $inner = $map[$key] ?? $map['other'];
        $s = $size;
        return "<svg class=\"icon\" width=\"{$s}\" height=\"{$s}\" viewBox=\"0 0 24 24\" fill=\"none\""
            . " stroke=\"currentColor\" stroke-width=\"1.6\" stroke-linecap=\"round\""
            . " stroke-linejoin=\"round\" aria-hidden=\"true\">{$inner}</svg>";
    }
}
