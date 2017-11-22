<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Event;

use Mautic\ReportBundle\Entity\Scheduler;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ReportScheduleSendEvent.
 */
class ReportScheduleSendEvent extends Event
{
    /**
     * @var Scheduler
     */
    private $scheduler;

    /**
     * @var string
     */
    private $file;

    /**
     * @param Scheduler $scheduler
     * @param string    $file
     */
    public function __construct(Scheduler $scheduler, $file)
    {
        $this->scheduler = $scheduler;
        $this->file      = $file;
    }

    /**
     * @return Scheduler
     */
    public function getScheduler()
    {
        return $this->scheduler;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }
}
