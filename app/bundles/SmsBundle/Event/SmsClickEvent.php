<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\Stat;

/**
 * Class SmsClickEvent.
 */
class SmsClickEvent extends CommonEvent
{
    private $request;

    private $sms;

    /**
     * @param Stat $stat
     * @param $request
     */
    public function __construct(Stat $stat, $request)
    {
        $this->entity  = $stat;
        $this->sms     = $stat->getSms();
        $this->request = $request;
    }

    /**
     * Returns the Sms entity.
     *
     * @return Sms
     */
    public function getSms()
    {
        return $this->sms;
    }

    /**
     * Get sms request.
     *
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Stat
     */
    public function getStat()
    {
        return $this->entity;
    }
}
