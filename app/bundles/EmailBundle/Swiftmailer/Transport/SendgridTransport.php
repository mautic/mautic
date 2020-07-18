<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

class SendgridTransport extends \Swift_SmtpTransport
{
    public function __construct($host = 'smtp.sendgrid.net', $port = 587, $security = 'tls')
    {
        parent::__construct($host, $port, $security);

        $this->setAuthMode('login');
    }
}
