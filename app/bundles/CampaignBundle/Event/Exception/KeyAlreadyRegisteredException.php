<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event\Exception;

use Symfony\Component\Process\Exception\InvalidArgumentException;

/**
 * Extends Symfony\Component\Process\Exception\InvalidArgumentException to keep BC.
 */
final class KeyAlreadyRegisteredException extends InvalidArgumentException
{
}
