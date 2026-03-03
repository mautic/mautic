<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\ProcessSignal\Exception;

class InvalidStateException extends \InvalidArgumentException
{
    public function __construct(string $stateString, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Could not parse the state from: "%s"', $stateString), 0, $previous);
    }
}
