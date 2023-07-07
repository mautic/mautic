<?php

namespace Mautic\PageBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Event\RedirectGenerationEvent;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\PageEvents;
use Mautic\PageBundle\Tests\PageTestAbstract;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcher;

class RedirectModelTest extends PageTestAbstract
{
    public function testCreateRedirectEntityWhenCalledReturnsRedirect()
    {
        $redirectModel = $this->getRedirectModel();
        $entity        = $redirectModel->createRedirectEntity('http://some-url.com');

        $this->assertInstanceOf(Redirect::class, $entity);
    }

    public function testGenerateRedirectUrlWhenCalledReturnsValidUrl()
    {
        $redirect = new Redirect();
        $redirect->setUrl('http://some-url.com');
        $redirect->setRedirectId('redirect-id');

        $redirectModel = $this->getRedirectModel();
        $url           = $redirectModel->generateRedirectUrl($redirect);

        $this->assertStringContainsString($url, 'http://some-url.com');
    }

    public function testRedirectGenerationEvent()
    {
        $urlHelper = $this
            ->getMockBuilder(UrlHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = new EventDispatcher();

        $url          = 'https://mautic.org';
        $clickthrough = ['foo' => 'bar'];

        $router = $this->createMock(Router::class);
        $router->expects($this->exactly(2))
            ->method('generate')
            ->willReturn($url);

        $model = new RedirectModel(
            $urlHelper,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(CorePermissions::class),
            $dispatcher,
            $router,
            $this->createMock(Translator::class),
            $this->createMock(UserHelper::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(CoreParametersHelper::class)
        );

        $redirect = new Redirect();
        $redirect->setUrl($url);

        // Add the listener to append something else to the CT
        $dispatcher->addListener(
            PageEvents::ON_REDIRECT_GENERATE,
            function (RedirectGenerationEvent $event) {
                $event->setInClickthrough('bar', 'foo');
            }
        );
        $url = $model->generateRedirectUrl($redirect, $clickthrough);
        $this->assertEquals('https://mautic.org?ct=YToyOntzOjM6ImZvbyI7czozOiJiYXIiO3M6MzoiYmFyIjtzOjM6ImZvbyI7fQ%3D%3D', $url);
    }
}
