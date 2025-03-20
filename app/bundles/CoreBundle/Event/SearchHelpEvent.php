<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class SearchHelpEvent extends Event
{
    use SearchEventTrait;

    public function __construct(private string $help, private string $context)
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
