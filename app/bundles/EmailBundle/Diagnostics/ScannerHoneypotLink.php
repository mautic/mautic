<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Diagnostics;

final class ScannerHoneypotLink
{
   
    public const URL_TOKEN = '{scanner_honeypot_url}';
    private const HTML = '<a href="'.self::URL_TOKEN.'" aria-hidden="true" tabindex="-1" style="display:none!important;mso-hide:all;font-size:0;line-height:0;max-height:0;overflow:hidden"></a>';
    public static function getHtml(): string
    {
        return self::HTML;
    }

    public static function removeFrom(string $content): string
    {
        if (!str_contains($content, self::URL_TOKEN)) {
            return $content;
        }

        return preg_replace('/<a\s[^>]*\bhref=(["\'])'.preg_quote(self::URL_TOKEN, '/').'\1[^>]*>\s*<\/a>/is', '', $content) ?? $content;
    }
}
