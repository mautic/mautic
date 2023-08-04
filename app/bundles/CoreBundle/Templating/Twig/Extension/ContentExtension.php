<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Templating\Helper\ContentHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ContentExtension extends AbstractExtension
{
    protected ContentHelper $contentHelper;

    public function __construct(ContentHelper $contentHelper)
    {
        $this->contentHelper = $contentHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('customContent', [$this, 'getCustomContent'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * Dispatch an event to collect custom content.
     *
     * @param ?mixed              $context  Context of the content requested for the viewName
     * @param array<string,mixed> $vars     Templating vars
     * @param ?string             $viewName The main identifier for the content requested. Will be etracted from $vars if get_defined
     */
    public function getCustomContent($context = null, array $vars = [], ?string $viewName = null): string
    {
        return $this->contentHelper->getCustomContent($context, $vars, $viewName);
    }
}
