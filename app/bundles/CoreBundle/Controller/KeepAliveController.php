<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class KeepAliveController
{
    #[Route(
        '/s/keep-alive',
        name: 'mautic_core_keep_alive',
        priority: -625
    )]
    public function keepAliveAction(): Response
    {
        return new Response('', Response::HTTP_OK);
    }
}
