<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\ProcessSignal\Exception;

use Mautic\CoreBundle\ProcessSignal\ProcessSignalState;

class SignalCaughtException extends \Exception
{
    public function __construct(int $signal, private ?ProcessSignalState $state = null)
    {
        parent::__construct(sprintf('Signal received: "%d"', $signal), $signal);
    }

    public function getState(): ?ProcessSignalState
    {
        return $this->state;
    }
}
