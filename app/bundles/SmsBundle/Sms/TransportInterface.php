<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Sms;

use Mautic\LeadBundle\Entity\Lead;

interface TransportInterface
{
    /**
     * @param Lead   $lead
     * @param string $content
     *
     * @return bool
     */
    public function sendSms(Lead $lead, $content);
}
