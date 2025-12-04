<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\ContentHelper;

class ContentExtension
{
    public function __construct(
        protected ContentHelper $contentHelper,
    ) {
    }

    /**
     * Dispatch an event to collect custom content.
     *
     * @param ?mixed              $context  Context of the content requested for the viewName
     * @param array<string,mixed> $vars     twig vars
     * @param ?string             $viewName The main identifier for the content requested. Will be etracted from $vars if get_defined
     */
    #[\Twig\Attribute\AsTwigFunction('customContent', isSafe: ['all'])]
    public function getCustomContent($context = null, array $vars = [], ?string $viewName = null): string
    {
        return $this->contentHelper->getCustomContent($context, $vars, $viewName);
    }

    /**
     * Replaces HTML script tags with non HTML tags so the JS inside them won't
     * execute and will be readable.
     */
    #[\Twig\Attribute\AsTwigFunction('showScriptTags', isSafe: ['all'])]
    public function showScriptTags(string $html): string
    {
        return $this->contentHelper->showScriptTags($html);
    }

    /**
     * @param array<mixed> $fonts
     *
     * @return array<mixed>
     */
    #[\Twig\Attribute\AsTwigFunction('getSortedEditorFonts')]
    public function sortEditorFonts(array $fonts): array
    {
        usort($fonts, static function ($fontA, $fontB): int {
            $fontAName = $fontA['name'] ?? '';
            $fontBName = $fontB['name'] ?? '';

            return strcasecmp($fontAName, $fontBName);
        });

        return $fonts;
    }
}
