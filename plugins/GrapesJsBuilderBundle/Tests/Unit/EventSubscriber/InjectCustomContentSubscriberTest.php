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

final class InjectCustomContentSubscriberTest extends TestCase
{
    /** @var MockObject&Config */
    private MockObject $config;
    /** @var MockObject&GrapesJsBuilderModel */
    private MockObject $model;
    /** @var MockObject&Environment */
    private MockObject $twig;
    /** @var MockObject&RouterInterface */
    private MockObject $router;

    public function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->model  = $this->createMock(GrapesJsBuilderModel::class);
        $this->twig   = $this->createMock(Environment::class);
        $this->router = $this->createMock(RouterInterface::class);
    }

    public function testInjectViewCustomContentExitsWhenPluginNotPublished(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());
        $this->config->method('isPublished')->willReturn(false);

        $subscriber = new InjectCustomContentSubscriber($this->config, $this->model, $this->twig, $requestStack, $this->router);
        $event      = new CustomContentEvent('view', 'email.settings.advanced', ['email' => new Email()]);

        $this->twig->expects(self::never())->method('render');

        $subscriber->injectViewCustomContent($event);

        self::assertSame([], $event->getContent());
    }

    public function testInjectViewCustomContentUsesRequestCustomMjmlOnPost(): void
    {
        $request = new Request([], [
            'grapesjsbuilder' => ['customMjml' => '<mjml>request</mjml>'],
        ]);
        $request->setMethod('POST');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $repository = $this->createMock(GrapesJsBuilderRepository::class);
        $this->model->method('getRepository')->willReturn($repository);
        $repository->method('findOneBy')->willReturn(null);

        $this->config->method('isPublished')->willReturn(true);

        $this->twig->expects(self::once())
            ->method('render')
            ->with('@GrapesJsBuilder/Setting/fields.html.twig', ['customMjml' => '<mjml>request</mjml>'])
            ->willReturn('<div>ok</div>');

        $subscriber = new InjectCustomContentSubscriber($this->config, $this->model, $this->twig, $requestStack, $this->router);
        $event      = new CustomContentEvent('view', 'email.settings.advanced', ['email' => new Email()]);

        $subscriber->injectViewCustomContent($event);

        self::assertSame(['<div>ok</div>'], $event->getContent());
    }

    public function testInjectViewCustomContentUsesStoredMjmlOnGet(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], [], [], [], ['REQUEST_METHOD' => 'GET']));

        $grapesJsBuilder = $this->createMock(GrapesJsBuilder::class);
        $grapesJsBuilder->method('getCustomMjml')->willReturn('<mjml>stored</mjml>');

        $repository = $this->createMock(GrapesJsBuilderRepository::class);
        $repository->method('findOneBy')->willReturn($grapesJsBuilder);

        $this->model->method('getRepository')->willReturn($repository);
        $this->config->method('isPublished')->willReturn(true);

        $this->twig->expects(self::once())
            ->method('render')
            ->with('@GrapesJsBuilder/Setting/fields.html.twig', ['customMjml' => '<mjml>stored</mjml>'])
            ->willReturn('<div>stored</div>');

        $subscriber = new InjectCustomContentSubscriber($this->config, $this->model, $this->twig, $requestStack, $this->router);
        $event      = new CustomContentEvent('view', 'email.settings.advanced', ['email' => new Email()]);

        $subscriber->injectViewCustomContent($event);

        self::assertSame(['<div>stored</div>'], $event->getContent());
    }

    public function testInjectViewCustomContentInjectsPageHeaderVars(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $this->config->method('isPublished')->willReturn(true);

        $this->router->expects(self::exactly(3))
            ->method('generate')
            ->willReturnMap([
                ['grapesjsbuilder_assets', [], 0, 'https://example.test/assets'],
                ['grapesjsbuilder_upload', [], 0, 'https://example.test/upload'],
                ['grapesjsbuilder_delete', [], 0, 'https://example.test/delete'],
            ]);

        $this->twig->expects(self::once())
            ->method('render')
            ->with(
                '@GrapesJsBuilder/Setting/vars.html.twig',
                [
                    'dataAssets' => 'https://example.test/assets',
                    'dataUpload' => 'https://example.test/upload',
                    'dataDelete' => 'https://example.test/delete',
                ]
            )
            ->willReturn('<script>vars</script>');

        $subscriber = new InjectCustomContentSubscriber($this->config, $this->model, $this->twig, $requestStack, $this->router);
        $event      = new CustomContentEvent('view', 'page.header.left');

        $subscriber->injectViewCustomContent($event);

        self::assertSame(['<script>vars</script>'], $event->getContent());
    }
}
