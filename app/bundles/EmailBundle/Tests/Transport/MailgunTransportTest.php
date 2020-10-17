<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Transport;

use Mautic\EmailBundle\Swiftmailer\Mailgun\Callback\MailgunCallback;
use Mautic\EmailBundle\Swiftmailer\Mailgun\MailgunFacade;
use Mautic\EmailBundle\Swiftmailer\Mailgun\MailgunWrapper;
use Mautic\EmailBundle\Swiftmailer\Transport\MailgunTransport;

class MailgunTransportTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|MailgunCallback
     */
    private $mailgunCallback;

    /**
     * @var MockObject|MailgunFacade
     */
    private $mailgunFacade;

    /**
     * @var MailgunTransport
     */
    private $transport;

    protected function setUp()
    {
        parent::setUp();

        $this->mailgunCallback   = $this->createMock(MailgunCallback::class);
        $this->mailgunFacade     = $this->createMock(MailgunFacade::class);
        $this->transport         = new MailgunTransport($this->mailgunFacade, $this->mailgunCallback);
        $this->transport->setDomain('domain.com');
    }

    public function testStart()
    {
        $this->assertIsInt($this->transport->start());
    }
}
