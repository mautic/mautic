<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Unit\EventSubscriber;

use Mautic\EmailBundle\Event\EmailSendEvent;
use MauticPlugin\GrapesJsBuilderBundle\EventSubscriber\EmailPreviewSubscriber;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailPreviewSubscriberTest extends TestCase
{
    private Config&MockObject $config;

    private EmailPreviewSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->config     = $this->createMock(Config::class);
        $this->subscriber = new EmailPreviewSubscriber($this->config);
    }

    public function testConvertsPublicMjmlPreviewToHtml(): void
    {
        $this->config->method('isPublished')->willReturn(true);

        $mjml = '<mjml><mj-body><mj-section><mj-column><mj-text>Preview</mj-text></mj-column></mj-section></mj-body></mjml>';
        $event = new EmailSendEvent(null, [
            'content' => $mjml,
            'source'  => ['publicPreview' => true],
        ]);

        $this->subscriber->convertPublicMjmlPreview($event);

        self::assertStringNotContainsString('<mjml>', $event->getContent(true));
        self::assertStringContainsString('Preview', $event->getContent(true));
    }

    public function testDoesNotConvertWhenNotPublicPreview(): void
    {
        $this->config->method('isPublished')->willReturn(true);

        $mjml  = '<mjml><mj-body></mj-body></mjml>';
        $event = new EmailSendEvent(null, ['content' => $mjml]);

        $this->subscriber->convertPublicMjmlPreview($event);

        self::assertSame($mjml, $event->getContent(true));
    }

    public function testDoesNotConvertHtmlContent(): void
    {
        $this->config->method('isPublished')->willReturn(true);

        $html  = '<html><body>Hello</body></html>';
        $event = new EmailSendEvent(null, [
            'content' => $html,
            'source'  => ['publicPreview' => true],
        ]);

        $this->subscriber->convertPublicMjmlPreview($event);

        self::assertSame($html, $event->getContent(true));
    }

    public function testDoesNotConvertWhenPluginIsUnpublished(): void
    {
        $this->config->method('isPublished')->willReturn(false);

        $mjml  = '<mjml><mj-body></mj-body></mjml>';
        $event = new EmailSendEvent(null, [
            'content' => $mjml,
            'source'  => ['publicPreview' => true],
        ]);

        $this->subscriber->convertPublicMjmlPreview($event);

        self::assertSame($mjml, $event->getContent(true));
    }
}
