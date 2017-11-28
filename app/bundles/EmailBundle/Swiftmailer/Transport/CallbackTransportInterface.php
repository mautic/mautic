<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Symfony\Component\HttpFoundation\Request;

interface CallbackTransportInterface
{
    /**
     * Returns a "transport" string to match the URL path /mailer/{transport}/callback.
     *
     * @return mixed
     */
    public function getCallbackPath();

    /**
     * Processes the response.
     *
     * @param Request $request
     */
    public function processCallbackResponse(Request $request);
}
