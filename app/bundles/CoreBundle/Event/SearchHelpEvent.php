<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

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
