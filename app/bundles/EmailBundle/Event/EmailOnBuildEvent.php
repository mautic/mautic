<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Event;

use Mautic\CoreBundle\Event\BuilderEvent;
use Mautic\EmailBundle\Entity\Email;

final class EmailOnBuildEvent extends BuilderEvent
{
    public function getEmail(): ?Email
    {
        return $this->entity;
    }
}
