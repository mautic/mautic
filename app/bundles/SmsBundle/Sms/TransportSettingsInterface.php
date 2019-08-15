<?php

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Sms;

interface TransportSettingsInterface
{
    /**
     * @return bool
     */
    public function hasDelivered();

    /**
     * @return bool
     */
    public function hasRead();

    /**
     * @return bool
     */
    public function hasFailed();
}
