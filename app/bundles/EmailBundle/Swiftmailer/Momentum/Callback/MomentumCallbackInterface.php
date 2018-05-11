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
     * @param string $emailAddress
     * @param array  $response
     */
    public function processImmediateFeedback($emailAddress, array $response);
}
