<?php

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
     */
    public function processCallbackRequest(Request $request);
}
