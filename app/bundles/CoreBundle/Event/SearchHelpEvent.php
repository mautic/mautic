<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

/**
 * @deprecated Search help is no longer displayed. This event will be removed in Mautic 8.0.
 */
final class SearchHelpEvent extends AbstractSearchEvent
{
    public function __construct(private string $help, protected string $context)
    {
    }

    public function getHelp(): string
    {
        return $this->help;
    }

    public function setHelp(string $help): void
    {
        $this->help = $help;
    }
}
