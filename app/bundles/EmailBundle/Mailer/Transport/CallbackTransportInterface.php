<?php

namespace Mautic\EmailBundle\Mailer\Transport;

use Symfony\Component\HttpFoundation\Request;

interface CallbackTransportInterface
{
    /**
     * Processes the response.
     */
    public function processCallbackRequest(Request $request): void;
}
