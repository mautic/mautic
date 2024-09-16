<?php

namespace Mautic\EmailBundle\Swiftmailer\Transport;

class SendgridTransport extends \Swift_SmtpTransport
{
    public function __construct($host = 'smtp.sendgrid.net', $port = 587, $security = 'tls')
    {
        parent::__construct($host, $port, $security);

        $this->setAuthMode('login');
    }
}
