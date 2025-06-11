<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\ColorHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ColorsExtension extends AbstractExtension
{
    public function __construct(
        private ColorHelper $colorHelper
    ) {
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
        // Remove # if present and ensure it's a valid hex color
        $hexColor = ltrim($hexColor, '#');
        
        // If the color is in short format (e.g., FED), expand it to full format (e.g., FFEEFF)
        if (3 === strlen($hexColor)) {
            $hexColor = str_repeat($hexColor[0], 2) . str_repeat($hexColor[1], 2) . str_repeat($hexColor[2], 2);
        }
        
        // Validate hex color
        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $hexColor)) {
            return 'black'; // Default to black if invalid color
        }
        
        try {
            // Parse hex color to RGB
            $r = hexdec(substr($hexColor, 0, 2));
            $g = hexdec(substr($hexColor, 2, 2));
            $b = hexdec(substr($hexColor, 4, 2));
            
            // Calculate perceived brightness (same as in ThemesExtension)
            $brightness = ($r * 299 + $g * 587 + $b * 114) / 1000;
            
            // Return black for light colors, white for dark colors
            return $brightness > 125 ? 'black' : 'white';
        } catch (\Exception $e) {
            // Return black as default if color parsing fails
            return 'black';
        }
    }
}