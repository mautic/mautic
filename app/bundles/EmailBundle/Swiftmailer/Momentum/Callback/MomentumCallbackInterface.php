<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Callback;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface MomentumCallbackInterface.
 */
interface MomentumCallbackInterface
{
    public function processCallbackRequest(Request $request);

    /**
     * @return mixed
     */
    public function processImmediateFeedback(\Swift_Mime_SimpleMessage $message, array $response);
}
