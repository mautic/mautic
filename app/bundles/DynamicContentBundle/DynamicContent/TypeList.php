<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\DynamicContent;

final class TypeList
{
    public const HTML = 'html';
    public const TEXT = 'text';

    /**
     * @return string[]
     */
    public function getChoices(): array
    {
        return [
            'mautic.dynamic.content.type.html' => self::HTML,
            'mautic.dynamic.content.type.text' => self::TEXT,
        ];
    }
}
