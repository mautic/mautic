<?php

namespace Mautic\EmailBundle\Swiftmailer\Transport;

class PostmarkTransport extends \Swift_SmtpTransport
{
    public function __construct($host = 'smtp.postmarkapp.com', $port = 587, $security = 'tls')
    {
        parent::__construct($host, $port, $security);

        $this->setAuthMode('login');
    }
}
