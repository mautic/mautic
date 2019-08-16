<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Callback;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

interface CallbackInterface
{
    /**
     * Returns a "transport" string to match the URL path /sms/{transport}/callback.
     *
     * @return string
     */
    public function getTransportName();

    /**
     * Extract the message in the reply from the request.
     *
     * @param Request $request
     *
     * @return string|array
     *
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function getMessage(Request $request);
}
