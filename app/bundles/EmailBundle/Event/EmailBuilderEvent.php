<?php

namespace Mautic\EmailBundle\Event;

use Mautic\CoreBundle\Event\BuilderEvent;
use Mautic\EmailBundle\Entity\Email;

class EmailBuilderEvent extends BuilderEvent
{
    /**
     * @return Email|null
     */
    public function getEmail()
    {
        return $this->entity;
    }
}
