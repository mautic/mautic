<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Exception;

final class FileNotFoundException extends \Exception
{
    public function __construct(string $message = 'File not found.', int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
