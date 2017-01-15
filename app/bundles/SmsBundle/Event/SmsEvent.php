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

/**
 * Class SmsEvent.
 */
class SmsEvent extends CommonEvent
{
    /**
     * @param Sms  $sms
     * @param bool $isNew
     */
    public function __construct(Sms $sms, $isNew = false)
    {
        $this->entity = $sms;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Sms entity.
     *
     * @return Sms
     */
    public function getSms()
    {
        return $this->entity;
    }

    /**
     * Sets the Sms entity.
     *
     * @param Sms $sms
     */
    public function setSms(Sms $sms)
    {
        $this->entity = $sms;
    }
}
