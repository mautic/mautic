<?php declare(strict_types=1);

namespace Mautic\CoreBundle\Exception;

class UnexpectedTypeException extends \InvalidArgumentException
{

    /**
     * @param mixed $value
     */
    public function __construct($value, string $expectedType)
    {
        parent::__construct(sprintf('Expected argument of type "%s", "%s" given', $expectedType, \is_object($value) ? \get_class($value) : \gettype($value)));
    }
}
