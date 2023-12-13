<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\ContentHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ContentExtension extends AbstractExtension
{
    public function __construct(
        protected ContentHelper $contentHelper
    ) {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('customContent', [$this, 'getCustomContent'], ['is_safe' => ['all']]),
            new TwigFunction('showScriptTags', [$this, 'showScriptTags'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * Dispatch an event to collect custom content.
     *
     * @param ?mixed              $context  Context of the content requested for the viewName
     * @param array<string,mixed> $vars     twig vars
     * @param ?string             $viewName The main identifier for the content requested. Will be etracted from $vars if get_defined
     */
    public function getCustomContent($context = null, array $vars = [], ?string $viewName = null): string
    {
        return $this->contentHelper->getCustomContent($context, $vars, $viewName);
    }

    /**
     * Replaces HTML script tags with non HTML tags so the JS inside them won't
     * execute and will be readable.
     */
    public function showScriptTags(string $html): string
    {
        return $this->contentHelper->showScriptTags($html);
    }
}
