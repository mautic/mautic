<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 *
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Exception;

use Exception;
use Throwable;

class CouldNotFormatDateTimeException extends Exception
{
    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = 'Can\'t format date object to string',
        int $code = 0,
        ?Throwable $throwable = null
    )
    {
        parent::__construct($message, $code, $throwable);
    }
}