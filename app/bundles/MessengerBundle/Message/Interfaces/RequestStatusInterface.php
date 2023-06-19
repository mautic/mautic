<?php declare(strict_types=1);

namespace Mautic\MessengerBundle\Message\Interfaces;

interface RequestStatusInterface
{
    public function setIsSynchronousRequest(bool $isSynchronous = true): self;
}