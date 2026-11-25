<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Twig\Helper;

use Twig\Environment;

final readonly class ListRowTemplateHelper
{
    private const TEMPLATE = '@MauticLead/List/_list_row.html.twig';

    private const DEFAULT_PATH = __DIR__.'/../../Resources/views/List/_list_row.html.twig';

    public function __construct(
        private Environment $twig,
    ) {
    }

    public function isOverridden(): bool
    {
        $resolvedPath = $this->twig->getLoader()->getSourceContext(self::TEMPLATE)->getPath();

        return realpath($resolvedPath) !== realpath(self::DEFAULT_PATH);
    }
}
