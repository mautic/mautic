<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Unit\EventSubscriber;

use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\EmailBundle\Entity\Email;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilderRepository;
use MauticPlugin\GrapesJsBuilderBundle\EventSubscriber\InjectCustomContentSubscriber;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class InjectCustomContentSubscriberTest extends TestCase
{
    /**
     * @var MockObject&Config
     */
    private MockObject $config;

    /**
     * @var MockObject&Environment
     */
    private MockObject $twig;

    /**
     * @var MockObject&RouterInterface
     */
    private MockObject $router;

    /**
     * @var MockObject&GrapesJsBuilderRepository
     */
    private MockObject $grapesJsBuilderRepository;

    protected function setUp(): void
    {
        $this->config                    = $this->createMock(Config::class);
        $this->twig                      = $this->createMock(Environment::class);
        $this->router                    = $this->createMock(RouterInterface::class);
        $this->grapesJsBuilderRepository = $this->createMock(GrapesJsBuilderRepository::class);
    }

    public function testInjectViewCustomContentExitsWhenPluginNotPublished(): void
    {
        $requestStack = new RequestStack([new Request()]);
        $this->config->expects($this->once())->method('isPublished')->willReturn(false);

        $subscriber = new InjectCustomContentSubscriber($this->config, $this->createStub(GrapesJsBuilderModel::class), $this->twig, $requestStack, $this->router, $this->grapesJsBuilderRepository);
        $event      = new CustomContentEvent('view', 'email.settings.advanced', ['email' => new Email()]);

        $this->twig->expects($this->never())->method('render');

        $subscriber->injectViewCustomContent($event);

        $this->assertSame([], $event->getContent());
    }

    public function testInjectViewCustomContentUsesRequestCustomMjmlOnPost(): void
    {
        $request = new Request([], [
            'grapesjsbuilder' => ['customMjml' => '<mjml>request</mjml>'],
        ]);
        $request->setMethod('POST');

        $requestStack = new RequestStack([$request]);

        $this->grapesJsBuilderRepository->expects($this->once())->method('findOneBy')->willReturn(null);

        $this->config->expects($this->once())->method('isPublished')->willReturn(true);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('@GrapesJsBuilder/Setting/fields.html.twig', ['customMjml' => '<mjml>request</mjml>'])
            ->willReturn('<div>ok</div>');

        $subscriber = new InjectCustomContentSubscriber($this->config, $this->createStub(GrapesJsBuilderModel::class), $this->twig, $requestStack, $this->router, $this->grapesJsBuilderRepository);
        $event      = new CustomContentEvent('view', 'email.settings.advanced', ['email' => new Email()]);

        $subscriber->injectViewCustomContent($event);

        $this->assertSame(['<div>ok</div>'], $event->getContent());
    }

    public function testInjectViewCustomContentUsesStoredMjmlOnGet(): void
    {
        $requestStack = new RequestStack([new Request([], [], [], [], [], ['REQUEST_METHOD' => 'GET'])]);

        $grapesJsBuilder = $this->createMock(GrapesJsBuilder::class);
        $grapesJsBuilder->expects($this->once())->method('getCustomMjml')->willReturn('<mjml>stored</mjml>');

        $this->grapesJsBuilderRepository->expects($this->once())->method('findOneBy')->willReturn($grapesJsBuilder);

        $this->config->expects($this->once())->method('isPublished')->willReturn(true);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('@GrapesJsBuilder/Setting/fields.html.twig', ['customMjml' => '<mjml>stored</mjml>'])
            ->willReturn('<div>stored</div>');

        $subscriber = new InjectCustomContentSubscriber($this->config, $this->createStub(GrapesJsBuilderModel::class), $this->twig, $requestStack, $this->router, $this->grapesJsBuilderRepository);
        $event      = new CustomContentEvent('view', 'email.settings.advanced', ['email' => new Email()]);

        $subscriber->injectViewCustomContent($event);

        $this->assertSame(['<div>stored</div>'], $event->getContent());
    }

    public function testInjectViewCustomContentInjectsPageHeaderVars(): void
    {
        $requestStack = new RequestStack([new Request()]);

        $this->config->expects($this->once())->method('isPublished')->willReturn(true);

        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->willReturnMap([
                ['grapesjsbuilder_upload', [], 0, 'https://example.test/upload'],
                ['grapesjsbuilder_delete', [], 0, 'https://example.test/delete'],
            ]);

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                '@GrapesJsBuilder/Setting/vars.html.twig',
                [
                    'dataUpload' => 'https://example.test/upload',
                    'dataDelete' => 'https://example.test/delete',
                ]
            )
            ->willReturn('<script>vars</script>');

        $subscriber = new InjectCustomContentSubscriber($this->config, $this->createStub(GrapesJsBuilderModel::class), $this->twig, $requestStack, $this->router, $this->grapesJsBuilderRepository);
        $event      = new CustomContentEvent('view', 'page.header.left');

        $subscriber->injectViewCustomContent($event);

        $this->assertSame(['<script>vars</script>'], $event->getContent());
    }
}
