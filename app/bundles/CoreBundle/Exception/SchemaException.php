<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Exception;

final class SchemaException extends \Exception
{
    public function __construct(string $message = 'Could not perform schema change.', int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
