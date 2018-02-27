<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\EventListener;

use Mautic\CoreBundle\EventListener\RequestSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\Translation\TranslatorInterface;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;

class RequestSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestSubscriber
     */
    private $subscriber;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $getResponseEventMock;

    protected function setUp()
    {
        $aCsrfTokenId = 45;
        $aCsrfTokenValue = 'csrf-token-value';

        $csrfTokenManagerMock = $this->createMock(CsrfTokenManagerInterface::class);

        $csrfTokenManagerMock
            ->method('getToken')
            ->willReturn(new CsrfToken($aCsrfTokenId, $aCsrfTokenValue));

        $this->request = new Request;

        $this->getResponseEventMock = $this->getMockBuilder(GetResponseEvent::class)
            ->setConstructorArgs([
                $this->createMock(HttpKernelInterface::class),
                $this->request,
                HttpKernelInterface::MASTER_REQUEST,
            ])
            ->getMock();

        $this->getResponseEventMock
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);

        $templatingHelper = $this->createMock(TemplatingHelper::class);

        $templatingHelper
            ->method('getTemplating')
            ->willReturn($this->createMock(DelegatingEngine::class));

        $this->subscriber = new RequestSubscriber($csrfTokenManagerMock);
        $this->subscriber->setTranslator($this->createMock(TranslatorInterface::class));
        $this->subscriber->setTemplating($templatingHelper);
    }

    public function testTheValidateCsrfTokenForAjaxPostMethodAsRegularPost()
    {
        $this->getResponseEventMock
            ->expects($this->never())
            ->method('setResponse');

        $this->request->server->set('REQUEST_METHOD', 'POST');

        $this->subscriber->validateCsrfTokenForAjaxPost($this->getResponseEventMock);
    }

    public function testTheValidateCsrfTokenForAjaxPostMethodAsAjaxGet()
    {
        $this->getResponseEventMock
            ->expects($this->never())
            ->method('setResponse');

        $this->request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->request->server->set('REQUEST_METHOD', 'GET');

        $this->subscriber->validateCsrfTokenForAjaxPost($this->getResponseEventMock);
    }

    public function testTheValidateCsrfTokenForAjaxPostMethodAsAjaxPostOnPublicRoute()
    {
        $this->getResponseEventMock
            ->expects($this->never())
            ->method('setResponse');

        $this->request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->request->server->set('REQUEST_METHOD', 'POST');
        $this->request->server->set('REQUEST_URI', '/some-public-page');

        $this->subscriber->validateCsrfTokenForAjaxPost($this->getResponseEventMock);
    }

    public function testTheValidateCsrfTokenForAjaxPostMethodAsAjaxPostOnSecureRouteWithMissingCsrf()
    {
        $this->getResponseEventMock
            ->expects($this->once())
            ->method('setResponse');

        $this->request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->request->server->set('REQUEST_METHOD', 'POST');
        $this->request->server->set('REQUEST_URI', '/s/some-secure-page');

        $this->subscriber->validateCsrfTokenForAjaxPost($this->getResponseEventMock);
    }

    public function testTheValidateCsrfTokenForAjaxPostMethodAsAjaxPostOnSecureRouteWithInvalidCsrf()
    {
        $this->getResponseEventMock
            ->expects($this->once())
            ->method('setResponse');

        $this->request->headers->set('X-CSRF-Token', 'invalid-csrf-token-value');
        $this->request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->request->server->set('REQUEST_METHOD', 'POST');
        $this->request->server->set('REQUEST_URI', '/s/some-secure-page');

        $this->subscriber->validateCsrfTokenForAjaxPost($this->getResponseEventMock);
    }

    public function testTheValidateCsrfTokenForAjaxPostMethodAsAjaxPostOnSecureRouteWithMatchingCsrf()
    {
        $this->getResponseEventMock
            ->expects($this->never())
            ->method('setResponse');

        $this->request->headers->set('X-CSRF-Token', 'csrf-token-value');
        $this->request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->request->server->set('REQUEST_METHOD', 'POST');
        $this->request->server->set('REQUEST_URI', '/s/some-secure-page');

        $this->subscriber->validateCsrfTokenForAjaxPost($this->getResponseEventMock);
    }
}
