<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class RespondingAuthenticationException extends AuthenticationException
{
    public function __construct(
        private Response $response,
        string $message = 'Authentication failed.',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
