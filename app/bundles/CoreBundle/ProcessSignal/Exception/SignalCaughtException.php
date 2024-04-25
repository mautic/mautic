<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\ProcessSignal\Exception;

use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;

class SignalCaughtException extends \Exception
{
    public function __construct(int $signal = ProcessSignalService::SIGTERM)
    {
        parent::__construct(sprintf('Signal received: "%d"', $signal), $signal);
    }
}
