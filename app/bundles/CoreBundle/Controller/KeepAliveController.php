<?php

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

class KeepAliveController
{
    public function keepAliveAction(): Response
    {
        return new Response('', 200);
    }
}
