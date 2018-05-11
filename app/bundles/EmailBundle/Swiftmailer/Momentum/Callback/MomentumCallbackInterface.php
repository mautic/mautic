<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Callback;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface MomentumCallbackInterface.
 */
interface MomentumCallbackInterface
{
    /**
     * @param Request $request
     */
    public function processCallbackRequest(Request $request);

    /**
     * @param \Swift_Mime_Message $message
     * @param array               $response
     *
     * @return mixed
     */
    public function processImmediateFeedback(\Swift_Mime_Message $message, array $response);
}
