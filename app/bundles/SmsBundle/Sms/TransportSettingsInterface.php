<?php

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Sms;

interface TransportSettingsInterface
{
    const STAT_DELIVERED  = 'delivered';
    const STAT_READ       = 'read';
    const STAT_FAILED     = 'failed';

    /**
     *  Define which settings your transport support.
     *
     * @return array
     */
    public function enabledSettings();
}
