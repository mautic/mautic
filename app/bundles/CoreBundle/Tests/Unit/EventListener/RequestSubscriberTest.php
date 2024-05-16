<?php

namespace Mautic\CoreBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\EventListener\RequestSubscriber;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class RequestSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private RequestSubscriber $subscriber;

    private Request $request;

    /**
     * @var MockObject&RequestEvent
     */
    private MockObject $requestEvent;

    private ResponseEvent $responseEvent;

    protected function setUp(): void
    {
        $aCsrfTokenId    = 45;
        $aCsrfTokenValue = 'csrf-token-value';

        $csrfTokenManagerMock = $this->createMock(CsrfTokenManagerInterface::class);

        $csrfTokenManagerMock
            ->method('getToken')
            ->willReturn(new CsrfToken($aCsrfTokenId, $aCsrfTokenValue));

        $csrfTokenManagerMock
          ->method('isTokenValid')
          ->will($this->returnCallback(fn (CsrfToken $token) => $token->getValue() === $aCsrfTokenValue));

        $this->request = new Request();

        $response = new Response();

        $this->requestEvent = $this->getMockBuilder(RequestEvent::class)
            ->setConstructorArgs([
                $this->createMock(HttpKernelInterface::class),
                $this->request,
                HttpKernelInterface::MASTER_REQUEST,
            ])
            ->getMock();

        $this->requestEvent
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->responseEvent = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $twig = $this->createMock(Environment::class);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $this->subscriber = new RequestSubscriber(
            $csrfTokenManagerMock,
            $this->createMock(TranslatorInterface::class),
            $twig,
            $coreParametersHelper
        );
    }

    public function testCsrfCookieSetForPublicPath(): void
    {
        $this->request->server->set('REQUEST_URI', '/some-public-page');

        $this->subscriber->onResponseSetCsrfCookie($this->responseEvent);

        $cookies = $this->responseEvent->getResponse()->headers->getCookies();

        $this->assertCount(0, $cookies);
    }

    public function testCsrfCookieSetForSecurePath(): void
    {
        $this->request->server->set('REQUEST_URI', '/s/some-secure-page');

        $this->subscriber->onResponseSetCsrfCookie($this->responseEvent);

        $cookies = $this->responseEvent->getResponse()->headers->getCookies();

        $this->assertCount(1, $cookies);

        /** @var \Symfony\Component\HttpFoundation\Cookie $cookie */
        $cookie = reset($cookies);

        $this->assertEquals('csrf-mautic_ajax_post', $cookie->getName());

        $this->assertEquals('csrf-token-value', $cookie->getValue());
    }

    public function testTheValidateCsrfTokenForAjaxPostMethodAsRegularPost(): void
    {
        $this->requestEvent
            ->expects($this->never())
            ->method('setResponse');

        $this->request->server->set('REQUEST_METHOD', 'POST');

        $this->subscriber->validateCsrfTokenForAjaxPost($this->requestEvent);
    }

    public function testTheValidateCsrfTokenForAjaxPostMethodAsAjaxGet(): void
    {
        $this->requestEvent
            ->expects($this->never())
            ->method('setResponse');

        $this->request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->request->server->set('REQUEST_METHOD', 'GET');

        $this->subscriber->validateCsrfTokenForAjaxPost($this->requestEvent);
    }

    public function testTheValidateCsrfTokenForAjaxPostMethodAsAjaxPostOnPublicRoute(): void
    {
        $this->requestEvent
            ->expects($this->never())
            ->method('setResponse');

        $this->request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->request->server->set('REQUEST_METHOD', 'POST');
        $this->request->server->set('REQUEST_URI', '/some-public-page');

        $this->subscriber->validateCsrfTokenForAjaxPost($this->requestEvent);
    }

    public function testTheValidateCsrfTokenForAjaxPostMethodAsAjaxPostOnSecureRouteWithMissingCsrf(): void
    {
        $this->requestEvent
            ->expects($this->once())
            ->method('setResponse');

        $this->request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->request->server->set('REQUEST_METHOD', 'POST');
        $this->request->server->set('REQUEST_URI', '/s/some-secure-page');

        $this->subscriber->validateCsrfTokenForAjaxPost($this->requestEvent);
    }

    public function testTheValidateCsrfTokenForAjaxPostMethodAsAjaxPostOnSecureRouteWithInvalidCsrf(): void
    {
        $this->requestEvent
            ->expects($this->once())
            ->method('setResponse');

        $this->request->headers->set('X-CSRF-Token', 'invalid-csrf-token-value');
        $this->request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->request->server->set('REQUEST_METHOD', 'POST');
        $this->request->server->set('REQUEST_URI', '/s/some-secure-page');

        $this->subscriber->validateCsrfTokenForAjaxPost($this->requestEvent);
    }

    public function testTheValidateCsrfTokenForAjaxPostMethodAsAjaxPostOnSecureRouteWithMatchingCsrf(): void
    {
        $this->requestEvent
            ->expects($this->never())
            ->method('setResponse');

        $this->request->headers->set('X-CSRF-Token', 'csrf-token-value');
        $this->request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->request->server->set('REQUEST_METHOD', 'POST');
        $this->request->server->set('REQUEST_URI', '/s/some-secure-page');

        $this->subscriber->validateCsrfTokenForAjaxPost($this->requestEvent);
    }
}
