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
        $this->response           = $this->createMock(ResponseInterface::class);
        $this->domain             = $this->getMockBuilder(Domain::class)
        ->disableOriginalConstructor()
        ->setMethods(['getDomain', 'getState'])
        ->getMock();
    }

    public function testClientException()
    {
        $this->response->method('getBody')->willReturn(new \Exception('Error'));
        $this->response->method('getHeaderLine')->willReturn('application/json');

        $this->mailgunWrapper->expects($this->once())
            ->method('checkConnection')
            ->will($this->throwException(new HttpClientException('error', 404, $this->response)));

        $mailgunFacade = new MailgunFacade($this->mailgunWrapper, $this->mailgunMessage, $this->logger);
        $this->expectException(\Swift_TransportException::class);
        $mailgunFacade->checkConnection('domain.com');
    }

    public function testCheckConnection()
    {
        $this->domain->expects($this->once())->method('getDomain')->willReturn($this->domain);
        $this->domain->expects($this->once())->method('getState')->willReturn('active');

        $this->mailgunWrapper->expects($this->once())
            ->method('checkConnection')
            ->willReturn($this->domain);

        $mailgunFacade = new MailgunFacade($this->mailgunWrapper, $this->mailgunMessage, $this->logger);

        $this->assertTrue($mailgunFacade->checkConnection('domain.com'));
    }
}
