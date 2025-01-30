<?php

declare(strict_types=1);

namespace Mautic\DashboardBundle\Exception;

class CouldNotFormatDateTimeException extends \Exception
{
    public function __construct(
        string $message = 'Can\'t format date object to string',
        int $code = 0,
        \Throwable $throwable = null,
    ) {
        parent::__construct($message, $code, $throwable);
    }
}
