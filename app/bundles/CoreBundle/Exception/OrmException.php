<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Exception;

/**
 * A concrete ORM exception.
 *
 * ORM 3 moved ORMException to Doctrine\ORM\Exception\ORMException and made it an
 * interface, so it can no longer be instantiated. This implements it, which keeps
 * existing catch (ORMException) sites catching what they always did - including the
 * ORM's own exceptions, which implement the same interface.
 */
final class OrmException extends \Exception implements \Doctrine\ORM\Exception\ORMException
{
}
