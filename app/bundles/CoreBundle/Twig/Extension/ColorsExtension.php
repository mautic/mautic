<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ColorsExtension extends AbstractExtension
{
    public function __construct()
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('contrast_color', [$this, 'getContrastColor'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Returns 'black' or 'white' depending on which provides better contrast against the given color.
     *
     * @param string $hexColor Color in hex format (with or without #)
     */
    public function getContrastColor(string $hexColor): string
    {
        $hexColor = ltrim($hexColor, '#');

        if (3 === strlen($hexColor)) {
            $hexColor = str_repeat($hexColor[0], 2).str_repeat($hexColor[1], 2).str_repeat($hexColor[2], 2);
        }

        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $hexColor)) {
            return 'black';
        }

        try {
            $r = hexdec(substr($hexColor, 0, 2));
            $g = hexdec(substr($hexColor, 2, 2));
            $b = hexdec(substr($hexColor, 4, 2));

            $brightness = ($r * 299 + $g * 587 + $b * 114) / 1000;

            return $brightness > 125 ? 'black' : 'white';
        } catch (\Exception) {
            return 'black';
        }
    }
}
