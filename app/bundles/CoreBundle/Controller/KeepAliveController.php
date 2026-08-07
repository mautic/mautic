<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

final class KeepAliveController
{
    public function keepAliveAction(): Response
    {
<<<<<<< HEAD
<<<<<<< HEAD
        return new Response('', Response::HTTP_OK);
=======
        return new Response('', \Symfony\Component\HttpFoundation\Response::HTTP_OK);
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        return new Response('', Response::HTTP_OK);
>>>>>>> 222589fde5 (cs)
    }
}
