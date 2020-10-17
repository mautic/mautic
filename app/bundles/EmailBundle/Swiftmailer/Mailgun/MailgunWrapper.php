<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Mailgun;

use Mailgun\Mailgun;

class MailgunWrapper
{
    /**
     * @var Mailgun
     */
    private $mailGun;

    public function __construct(Mailgun $mailGun)
    {
        $this->mailGun = $mailGun;
    }

    /**
     * @return Response
     */
    public function send()
    {
        return $this->mailGun->client->mail()->send()->post($mail);
    }

    /**
     * @return Response
     */
    public function checkConnection(string $domain)
    {
        return $this->mailGun->domains()->show($domain);
    }
}
