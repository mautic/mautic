<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\EventListener;

use LightSaml\Context\Profile\MessageContext;
use LightSaml\Context\Profile\ProfileContext;
use LightSaml\Error\LightSamlContextException;
use LightSaml\Model\Protocol\Response as LightSamlResponse;
use LightSaml\Model\Protocol\Status;
use LightSaml\Model\Protocol\StatusCode;
use Mautic\CoreBundle\EventListener\ExceptionListener;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Router;

class LightSAMLExceptionListenerTest extends MauticMysqlTestCase
{
    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    protected function setUp(): void
    {
        parent::setup();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->router = $this->createMock(Router::class);
        $this->router->expects($this->once())->method('generate')->willReturn('saml/login_retry');
    }

    public function testSamlRoutesAreRedirectedToDefaultLoginIfSamlIsDisabled(): void
    {
        // creating a success status
        $statusCode = new StatusCode('urn:oasis:names:tc:SAML:2.0:status:Success');
        $status     = new Status($statusCode);

        // creating a saml response which will return above status
        $lightSAMLResponse = $this->createMock(LightSamlResponse::class);
        $lightSAMLResponse->expects($this->any())->method('getStatus')->willReturn($status);

        // creating inbound context which will return lightsaml response
        $inboundContext = $this->createMock(MessageContext::class);
        $inboundContext->expects($this->exactly(2))->method('getMessage')->willReturn($lightSAMLResponse);

        // creating context which will return inbound context
        $context = $this->createMock(ProfileContext::class);
        $context->expects($this->exactly(2))->method('getInboundContext')->willReturn($inboundContext);

        // creating exception which will requires context
        $exception = new LightSamlContextException($context, 'Unknown Inresponse');

        $request = new Request();
        $session = $this->createMock(Session::class);
        $request->attributes->set('_session', $session);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event  = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $subscriber = new ExceptionListener($this->router, 'MauticCoreBundle:Exception:show', $this->logger);

        $subscriber->onKernelException($event);
    }
}
