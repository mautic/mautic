<?php

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\EmailBundle\Model\TransportType;

class TransportTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTransportTypes()
    {
        $transportType = new TransportType();

        $expected = [
            'mautic.transport.amazon'       => 'mautic.email.config.mailer_transport.amazon',
            'mautic.transport.amazon_api'   => 'mautic.email.config.mailer_transport.amazon_api',
            'mautic.transport.elasticemail' => 'mautic.email.config.mailer_transport.elasticemail',
            'gmail'                         => 'mautic.email.config.mailer_transport.gmail',
            'mautic.transport.mandrill'     => 'mautic.email.config.mailer_transport.mandrill',
            'mautic.transport.mailjet'      => 'mautic.email.config.mailer_transport.mailjet',
            'smtp'                          => 'mautic.email.config.mailer_transport.smtp',
            'mautic.transport.postmark'     => 'mautic.email.config.mailer_transport.postmark',
            'mautic.transport.sendgrid'     => 'mautic.email.config.mailer_transport.sendgrid',
            'mautic.transport.pepipost'     => 'mautic.email.config.mailer_transport.pepipost',
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

        $expected = '"mautic.transport.amazon","mautic.transport.amazon_api"';

        $this->assertSame($expected, $transportType->getAmazonService());
    }

    public function testDoNotNeedRegion()
    {
        $transportType = new TransportType();

        $expected = '"mautic.transport.elasticemail","gmail","mautic.transport.mandrill","mautic.transport.mailjet","smtp","mautic.transport.postmark","mautic.transport.sendgrid","mautic.transport.pepipost","mautic.transport.sendgrid_api","sendmail","mautic.transport.sparkpost"';

        $this->assertSame($expected, $transportType->getServiceDoNotNeedAmazonRegion());
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

        $expected = '"mautic.transport.mailjet","mautic.transport.sendgrid","mautic.transport.pepipost","mautic.transport.elasticemail","mautic.transport.amazon","mautic.transport.amazon_api","mautic.transport.postmark","gmail"';

        $this->assertSame($expected, $transportType->getServiceRequiresUser());
    }

    public function testDoNotNeedLogin()
    {
        $transportType = new TransportType();

        $expected = '"mautic.transport.mandrill","mautic.transport.sendgrid_api","sendmail","mautic.transport.sparkpost"';

        $this->assertSame($expected, $transportType->getServiceDoNotNeedUser());
    }

    public function testRequiresPassword()
    {
        $transportType = new TransportType();

        $expected = '"mautic.transport.mailjet","mautic.transport.sendgrid","mautic.transport.pepipost","mautic.transport.elasticemail","mautic.transport.amazon","mautic.transport.amazon_api","mautic.transport.postmark","gmail"';

        $this->assertSame($expected, $transportType->getServiceRequiresPassword());
    }

    public function testDoNotNeedPassword()
    {
        $transportType = new TransportType();

        $expected = '"mautic.transport.mandrill","mautic.transport.sendgrid_api","sendmail","mautic.transport.sparkpost"';

        $this->assertSame($expected, $transportType->getServiceDoNotNeedPassword());
    }

    public function testRequiresApiKey()
    {
        $transportType = new TransportType();

        $expected = '"mautic.transport.sparkpost","mautic.transport.mandrill","mautic.transport.sendgrid_api"';

        $this->assertSame($expected, $transportType->getServiceRequiresApiKey());
    }
}
