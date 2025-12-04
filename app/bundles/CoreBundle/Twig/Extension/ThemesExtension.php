<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

final class ThemesExtension
{
    public function __construct(private CoreParametersHelper $coreParametersHelper)
    {
    }

    #[\Twig\Attribute\AsTwigFunction('getBrandPrimaryColor')]
    public function getBrandPrimaryColor(): string
    {
        return $this->coreParametersHelper->get('primary_brand_color', '000000');
    }

    #[\Twig\Attribute\AsTwigFunction('getTextOnBrandColor')]
    public function getTextOnBrandColor(): string
    {
        $primaryColor = $this->getBrandPrimaryColor();

        $r = hexdec(substr($primaryColor, 0, 2));
        $g = hexdec(substr($primaryColor, 2, 2));
        $b = hexdec(substr($primaryColor, 4, 2));

        // Calculate perceived brightness
        $brightness = ($r * 299 + $g * 587 + $b * 114) / 1000;

        // Determine text color based on brightness threshold
        return $brightness > 125 ? '000000' : 'ffffff';
    }

    #[\Twig\Attribute\AsTwigFunction('getTextOnBrandHelperColor')]
    public function getTextOnBrandHelperColor(): string
    {
        return '000000' === $this->getTextOnBrandColor() ? '6d6d6d' : 'b3b3b3';
    }

    #[\Twig\Attribute\AsTwigFunction('getRoundedCorners')]
    public function getRoundedCorners(string $size = 'lg'): int
    {
        $baseRadius = (int) $this->coreParametersHelper->get('rounded_corners', 0);

        $radiusMapping = [
            8  => ['md' => 4, 'sm' => 3],
            16 => ['md' => 6, 'sm' => 4],
            32 => ['md' => 8, 'sm' => 5],
        ];

        $roundedCorners = [
            'lg' => $baseRadius,
            'md' => $radiusMapping[$baseRadius]['md'] ?? 0,
            'sm' => $radiusMapping[$baseRadius]['sm'] ?? 0,
        ];

        return $roundedCorners[$size] ?? 0;
    }
}
