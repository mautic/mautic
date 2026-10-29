<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Helper;

use MjmlPHP\Mjml;
use MjmlPHP\MjmlException;
use MjmlPHP\MjmlOptions;
use MjmlPHP\Validation\ValidationLevel;

final class MjmlContentHelper
{
    public static function isMjml(string $content): bool
    {
        return str_contains($content, '<mjml');
    }

    public static function toHtml(string $content): ?string
    {
        if (!self::isMjml($content)) {
            return null;
        }

        try {
            $result = Mjml::render($content, new MjmlOptions(
                validationLevel: ValidationLevel::Soft,
                beautify: true,
            ));

            $html = trim($result->html);

            return '' !== $html ? $html : null;
        } catch (MjmlException) {
            return null;
        }
    }
}
