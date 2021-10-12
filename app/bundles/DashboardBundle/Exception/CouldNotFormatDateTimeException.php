<?php

declare(strict_types=1);

namespace Mautic\DashboardBundle\Exception;

use Exception;
use Throwable;

class CouldNotFormatDateTimeException extends Exception
{
    /**
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(
        $message = 'Can\'t format date object to string',
        $code = 0,
        Throwable $throwable = null
    ) {
        parent::__construct($message, $code, $throwable);
    }
}
