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

use Mailgun\Exception\HttpClientException;
use Mautic\EmailBundle\Swiftmailer\Mailgun\MailgunFacade;
use Mautic\EmailBundle\Swiftmailer\Mailgun\MailgunMessage;
use Mautic\EmailBundle\Swiftmailer\Mailgun\MailgunWrapper;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

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

    /**
     * @var ResponseInterface
     */
    private $response;

    private $domain;

    protected function setUp() : void
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

    public function testRequest()
    {
        $mailgunFacade = new MailgunFacade($this->mailgunWrapper, $this->mailgunMessage, $this->logger);
        $mail = $this->getMockBuilder(Mail::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mailgunMessage->expects($this->once())
        ->method('getMessage')
        ->with($message)
        ->willReturn($mail);

        
    }
}
