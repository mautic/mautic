<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Exception;

/**
 * A concrete database exception.
 *
 * DBAL 4 turned Doctrine\DBAL\Exception from a base class into an interface, so it can
 * no longer be instantiated. This implements it, which keeps the existing
 * catch (\Doctrine\DBAL\Exception) sites catching what they always did.
 */
final class DbalException extends \Exception implements \Doctrine\DBAL\Exception
{
}
