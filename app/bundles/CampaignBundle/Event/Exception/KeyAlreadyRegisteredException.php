<?php

namespace Mautic\CampaignBundle\Event\Exception;

use Symfony\Component\Process\Exception\InvalidArgumentException;

/**
 * Class KeyAlreadyRegisteredException.
 *
 * Extends Symfony\Component\Process\Exception\InvalidArgumentException to keep BC
 */
class KeyAlreadyRegisteredException extends InvalidArgumentException
{
}
