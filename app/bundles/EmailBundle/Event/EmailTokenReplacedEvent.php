<?php

namespace Mautic\EmailBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\EmailBundle\Helper\MailHelper;

class EmailTokenReplacedEvent extends CommonEvent
{
    protected MailHelper $mailHelper;

    public function __construct($mailHelper)
    {
        $this->mailHelper      = $mailHelper;
    }

    public function getMailHelper(): MailHelper
    {
        return $this->mailHelper;
    }
}
