<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Exception;

final class FileExistsException extends \Exception
{
    public function __construct(string $message = 'File exists.', int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
