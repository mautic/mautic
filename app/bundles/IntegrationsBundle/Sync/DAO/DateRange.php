<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Sync\DAO;

use DateTimeInterface;

class DateRange
{
    /**
     * @var DateTimeInterface|null
     */
    private $fromDate;

    /**
     * @var DateTimeInterface|null
     */
    private $toDate;

    public function __construct(?DateTimeInterface $fromDate, ?DateTimeInterface $toDate)
    {
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
    }

    /**
     * Get the value of fromDate.
     */
    public function getFromDate()
    {
        return $this->fromDate;
    }

    /**
     * Get the value of toDate.
     */
    public function getToDate()
    {
        return $this->toDate;
    }
}
