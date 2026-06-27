<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Helper;

final class MjmlContentHelper
{
    public static function isMjml(string $content): bool
    {
        return str_contains($content, '<mjml');
    }
}
