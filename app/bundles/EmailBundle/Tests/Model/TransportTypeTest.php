<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Test\Model;

use Mautic\EmailBundle\Model\TransportType;

class TransportTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTransportTypes()
    {
        $transportType = new TransportType();

        $expected = [
            'mautic.transport.amazon'       => 'mautic.email.config.mailer_transport.amazon',
            'mautic.transport.elasticemail' => 'mautic.email.config.mailer_transport.elasticemail',
            'gmail'                         => 'mautic.email.config.mailer_transport.gmail',
            'mautic.transport.mandrill'     => 'mautic.email.config.mailer_transport.mandrill',
            'mautic.transport.mailjet'      => 'mautic.email.config.mailer_transport.mailjet',
            'smtp'                          => 'mautic.email.config.mailer_transport.smtp',
            'mail'                          => 'mautic.email.config.mailer_transport.mail',
            'mautic.transport.postmark'     => 'mautic.email.config.mailer_transport.postmark',
            'mautic.transport.sendgrid'     => 'mautic.email.config.mailer_transport.sendgrid',
            'mautic.transport.sendgrid_api' => 'mautic.email.config.mailer_transport.sendgrid_api',
            'sendmail'                      => 'mautic.email.config.mailer_transport.sendmail',
            'mautic.transport.sparkpost'    => 'mautic.email.config.mailer_transport.sparkpost',
        ];

        $this->assertSame($expected, $transportType->getTransportTypes());
    }

    public function testSmtpService()
    {
        $transportType = new TransportType();

        $expected = '"smtp"';

        $this->assertSame($expected, $transportType->getSmtpService());
    }

    public function testAmazonService()
    {
        $transportType = new TransportType();

        $expected = '"mautic.transport.amazon"';

        $this->assertSame($expected, $transportType->getAmazonService());
    }

    public function testMailjetService()
    {
        $transportType = new TransportType();

        $expected = '"mautic.transport.mailjet"';

        $this->assertSame($expected, $transportType->getMailjetService());
    }

    public function testRequiresLogin()
    {
        $transportType = new TransportType();

        $expected = '"mautic.transport.mandrill",
                "mautic.transport.mailjet",
                "mautic.transport.sendgrid",
                "mautic.transport.elasticemail",
                "mautic.transport.amazon",
                "mautic.transport.postmark",
                "gmail"';

        $this->assertSame($expected, $transportType->getServiceRequiresLogin());
    }

    public function testDoNotNeedLogin()
    {
        $transportType = new TransportType();

        $expected = '"mail",
                "sendmail",
                "mautic.transport.sparkpost",
                "mautic.transport.sendgrid_api"';

        $this->assertSame($expected, $transportType->getServiceDoNotNeedLogin());
    }

    public function testRequiresPassword()
    {
        $transportType = new TransportType();

        $expected = '"mautic.transport.elasticemail",
                "mautic.transport.sendgrid",
                "mautic.transport.amazon",
                "mautic.transport.postmark",
                "mautic.transport.mailjet",
                "gmail"';

        $this->assertSame($expected, $transportType->getServiceRequiresPassword());
    }

    public function testDoNotNeedPassword()
    {
        $transportType = new TransportType();

        $expected = '"mail",
                "sendmail",
                "mautic.transport.sparkpost",
                "mautic.transport.mandrill",
                "mautic.transport.sendgrid_api"';

        $this->assertSame($expected, $transportType->getServiceDoNotNeedPassword());
    }

    public function testRequiresApiKey()
    {
        $transportType = new TransportType();

        $expected = '"mautic.transport.sparkpost",
                "mautic.transport.mandrill",
                "mautic.transport.sendgrid_api"';

        $this->assertSame($expected, $transportType->getServiceRequiresApiKey());
    }
}
