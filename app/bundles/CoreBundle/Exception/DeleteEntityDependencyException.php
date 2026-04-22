<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class DeleteEntityDependencyException extends \Exception
{
    /**
     * @param string[] $errors
     */
    public function __construct(
        private array $errors = [],
        string $message = '',
        int $code = Response::HTTP_CONFLICT,
    ) {
        parent::__construct($message, $code);
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
