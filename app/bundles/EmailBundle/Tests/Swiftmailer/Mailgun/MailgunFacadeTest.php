<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Swiftmailer\Mailgun;

use Mailgun\Api\Domain;
use Mautic\EmailBundle\Swiftmailer\Mailgun\MailgunFacade;
use Mautic\EmailBundle\Swiftmailer\Mailgun\MailgunMessage;
use Mautic\EmailBundle\Swiftmailer\Mailgun\MailgunWrapper;
//use Mailgun\Model\Domain\Domain;
use Monolog\Logger;

class MailgunFacadeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MailgunWrapper
     */
    private $mailgunWrapper;

    /**
     * @var MailgunMessage
     */
    private $mailgunMessage;

    /**
     * @var LoggerInterface
     */
    private $logger;

    protected function setUp()
    {
        parent::setUp();
        $this->mailgunWrapper = $this->getMockBuilder(MailgunWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mailgunMessage = $this->getMockBuilder(MailgunMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger             = $this->createMock(Logger::class);
    }

    public function testCheckConnection()
    {
        $domain             = $this->getMockBuilder(Domain::class)
        ->disableOriginalConstructor()
        ->setMethods(['getDomain', 'getState'])
        ->getMock();
        $domain->expects($this->once())->method('getDomain')->willReturn($domain);
        $domain->expects($this->once())->method('getState')->willReturn('active');

        $this->mailgunWrapper->expects($this->once())
            ->method('checkConnection')
            ->willReturn($domain);

        $mailgunFacade = new MailgunFacade($this->mailgunWrapper, $this->mailgunMessage, $this->logger);

        $mailgunFacade->checkConnection('domain.com');
    }
}
