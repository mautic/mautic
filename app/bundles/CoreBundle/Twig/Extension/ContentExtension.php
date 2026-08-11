<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\ContentHelper;
use Twig\Attribute\AsTwigFunction;

final readonly class ContentExtension
{
    public function __construct(
        private ContentHelper $contentHelper,
    ) {
    }

    /**
     * Dispatch an event to collect custom content.
     *
     * @param ?mixed              $context  Context of the content requested for the viewName
     * @param array<string,mixed> $vars     twig vars
     * @param ?string             $viewName The main identifier for the content requested. Will be etracted from $vars if get_defined
     */
    #[AsTwigFunction(name: 'customContent', isSafe: ['all'])]
    public function getCustomContent($context = null, array $vars = [], ?string $viewName = null): string
    {
        return $this->contentHelper->getCustomContent($context, $vars, $viewName);
    }

    /**
     * Replaces HTML script tags with non HTML tags so the JS inside them won't
     * execute and will be readable.
     */
    #[AsTwigFunction(name: 'showScriptTags', isSafe: ['all'])]
    public function showScriptTags(string $html): string
    {
        return $this->contentHelper->showScriptTags($html);
    }

    /**
     * @param array<mixed> $fonts
     *
     * @return array<mixed>
     */
    #[AsTwigFunction(name: 'getSortedEditorFonts')]
    public function sortEditorFonts(array $fonts): array
    {
        usort($fonts, static function (array $fontA, array $fontB): int {
            $fontAName = $fontA['name'] ?? '';
            $fontBName = $fontB['name'] ?? '';

            return strcasecmp($fontAName, $fontBName);
        });

        return $fonts;
    }
}
