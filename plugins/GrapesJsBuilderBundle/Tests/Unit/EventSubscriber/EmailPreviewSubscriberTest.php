<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Unit\EventSubscriber;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Event\EmailSendEvent;
use MauticPlugin\GrapesJsBuilderBundle\EventSubscriber\EmailPreviewSubscriber;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class EmailPreviewSubscriberTest extends TestCase
{
    private Config&MockObject $config;

    private Environment&MockObject $twig;

    private CoreParametersHelper&MockObject $coreParametersHelper;

    private string $projectDir;

    private EmailPreviewSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->config               = $this->createMock(Config::class);
        $this->twig                 = $this->createMock(Environment::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->projectDir           = sys_get_temp_dir().'/gjs-preview-test-'.uniqid('', true);

        $assetDir = $this->projectDir.'/plugins/GrapesJsBuilderBundle/Assets/library/js/dist';
        mkdir($assetDir, 0777, true);
        file_put_contents($assetDir.'/mjml-preview.js', 'window.MauticGrapesJsPreview = {};');
        file_put_contents($assetDir.'/manifest.json', json_encode(['mjml-preview.js' => 'mjml-preview.js'], JSON_THROW_ON_ERROR));

        $this->subscriber = new EmailPreviewSubscriber(
            $this->config,
            $this->twig,
            $this->coreParametersHelper,
            $this->projectDir,
        );
    }

    protected function tearDown(): void
    {
        $assetDir = $this->projectDir.'/plugins/GrapesJsBuilderBundle/Assets/library/js/dist';
        if (is_file($assetDir.'/manifest.json')) {
            unlink($assetDir.'/manifest.json');
        }
        if (is_file($assetDir.'/mjml-preview.js')) {
            unlink($assetDir.'/mjml-preview.js');
        }
        if (is_dir($assetDir)) {
            rmdir($assetDir);
        }
        if (is_dir(dirname(dirname(dirname($assetDir))))) {
            @rmdir(dirname(dirname(dirname($assetDir))));
        }
        if (is_dir(dirname(dirname(dirname(dirname($assetDir)))))) {
            @rmdir(dirname(dirname(dirname(dirname($assetDir)))));
        }
        if (is_dir($this->projectDir.'/plugins/GrapesJsBuilderBundle')) {
            @rmdir($this->projectDir.'/plugins/GrapesJsBuilderBundle');
        }
        if (is_dir($this->projectDir.'/plugins')) {
            @rmdir($this->projectDir.'/plugins');
        }
        if (is_dir($this->projectDir)) {
            @rmdir($this->projectDir);
        }
    }

    public function testWrapsPublicMjmlPreview(): void
    {
        $this->config->method('isPublished')->willReturn(true);
        $this->coreParametersHelper->method('get')->with('site_url')->willReturn('https://example.com');

        $mjml = '<mjml><mj-body><mj-text>Preview</mj-text></mj-body></mjml>';

        $this->twig->expects(self::once())
            ->method('render')
            ->with(
                '@GrapesJsBuilder/Preview/mjml.html.twig',
                self::callback(static function (array $parameters) use ($mjml): bool {
                    return $mjml === $parameters['mjml']
                        && 'https://example.com/plugins/GrapesJsBuilderBundle/Assets/library/js/dist/mjml-preview.js' === $parameters['scriptUrl'];
                })
            )
            ->willReturn('<html>wrapped</html>');

        $event = new EmailSendEvent(null, [
            'content' => $mjml,
            'source'  => ['publicPreview' => true],
        ]);

        $this->subscriber->wrapPublicMjmlPreview($event);

        self::assertSame('<html>wrapped</html>', $event->getContent(true));
    }

    public function testDoesNotWrapWhenPreviewScriptIsMissing(): void
    {
        $this->config->method('isPublished')->willReturn(true);
        $this->twig->expects(self::never())->method('render');

        $assetDir = $this->projectDir.'/plugins/GrapesJsBuilderBundle/Assets/library/js/dist';
        unlink($assetDir.'/manifest.json');

        $mjml  = '<mjml><mj-body></mj-body></mjml>';
        $event = new EmailSendEvent(null, [
            'content' => $mjml,
            'source'  => ['publicPreview' => true],
        ]);

        $this->subscriber->wrapPublicMjmlPreview($event);

        self::assertSame($mjml, $event->getContent(true));
    }

    public function testDoesNotWrapWhenNotPublicPreview(): void
    {
        $this->config->method('isPublished')->willReturn(true);
        $this->twig->expects(self::never())->method('render');

        $mjml  = '<mjml><mj-body></mj-body></mjml>';
        $event = new EmailSendEvent(null, ['content' => $mjml]);

        $this->subscriber->wrapPublicMjmlPreview($event);

        self::assertSame($mjml, $event->getContent(true));
    }

    public function testDoesNotWrapHtmlContent(): void
    {
        $this->config->method('isPublished')->willReturn(true);
        $this->twig->expects(self::never())->method('render');

        $html  = '<html><body>Hello</body></html>';
        $event = new EmailSendEvent(null, [
            'content' => $html,
            'source'  => ['publicPreview' => true],
        ]);

        $this->subscriber->wrapPublicMjmlPreview($event);

        self::assertSame($html, $event->getContent(true));
    }
}
