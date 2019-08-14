<?php

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Callback;

use Symfony\Component\HttpFoundation\Request;

interface CallbackDeliveryInterface extends CallbackInterface
{
    const CALLBACK_TYPE = 'delivery';

    /**
     * @return DeliveryStatusDAO
     */
    public function getDeliveryStatus(Request $request);
}
