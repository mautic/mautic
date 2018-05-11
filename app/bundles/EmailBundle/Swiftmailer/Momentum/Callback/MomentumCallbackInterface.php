<?php

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
