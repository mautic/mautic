<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\EmailBundle\Model\TransportType;

class TransportTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTransportTypes()
    {
        $transportType = new TransportType();

        $expected = [
            'ses+api'  => 'mautic.email.config.mailer_transport.amazon_api',
            'smtp'     => 'mautic.email.config.mailer_transport.smtp',
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

        $expected = '"ses+api"';

        $this->assertSame($expected, $transportType->getAmazonService());
    }

    public function testRequiresPassword()
    {
        $transportType = new TransportType();

        $expected = '"mautic.transport.mailjet","mautic.transport.sendgrid","mautic.transport.pepipost","mautic.transport.elasticemail","ses+smtp","ses+api","mautic.transport.postmark","gmail"';

        $this->assertSame($expected, $transportType->getServiceRequiresPassword());
    }
}
