<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Exception;

class FieldNotFoundException extends \Exception
{
    /**
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct($field, $object, $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('The %s field is not mapped for the %s object.', $field, $object), $code, $previous);
    }
}
