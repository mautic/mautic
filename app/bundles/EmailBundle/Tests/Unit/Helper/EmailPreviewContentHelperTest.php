<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\EmailPreviewContentHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EmailPreviewContentHelperTest extends TestCase
{
    private ThemeHelper&MockObject $themeHelper;

    private EmailPreviewContentHelper $helper;

    protected function setUp(): void
    {
        $this->themeHelper = $this->createMock(ThemeHelper::class);
        $this->helper      = new EmailPreviewContentHelper($this->themeHelper);
    }

    public function testResolveReturnsCustomHtmlWhenPresent(): void
    {
        $email = new Email();
        $email->setCustomHtml('<html><body>Custom</body></html>');

        $result = $this->helper->resolve($email);

        $this->assertSame('<html><body>Custom</body></html>', $result->getContent());
        $this->assertFalse($result->isRenderedFromTheme());
    }

    public function testResolveRendersThemeWhenCustomHtmlIsEmpty(): void
    {
        $email = new Email();
        $email->setTemplate('_1-2-1-2-column');
        $email->setContent([]);

        $this->themeHelper->expects($this->once())
            ->method('checkForTwigTemplate')
            ->with('@themes/_1-2-1-2-column/html/email.html.twig')
            ->willReturn('@themes/_1-2-1-2-column/html/email.html.twig');

        $this->themeHelper->expects($this->once())
            ->method('renderThemeTemplate')
            ->with(
                '@themes/_1-2-1-2-column/html/email.html.twig',
                self::callback(static fn (array $parameters): bool => true === $parameters['inBrowser']
                    && [] === $parameters['content']
                    && '_1-2-1-2-column' === $parameters['template'])
            )
            ->willReturn('<mjml><mj-body><mj-text>Theme</mj-text></mj-body></mjml>');

        $result = $this->helper->resolve($email);

        $this->assertStringContainsString('<mjml>', $result->getContent());
        $this->assertTrue($result->isRenderedFromTheme());
    }

    public function testResolveDoesNotRenderThemeForCodeMode(): void
    {
        $email = new Email();
        $email->setTemplate('mautic_code_mode');

        $this->themeHelper->expects($this->never())->method('renderThemeTemplate');

        $result = $this->helper->resolve($email);

        $this->assertSame('', $result->getContent());
        $this->assertFalse($result->isRenderedFromTheme());
    }

    public function testResolveUsesOverrideContentForDraftPreview(): void
    {
        $email = new Email();
        $email->setCustomHtml('<html><body>Published</body></html>');

        $result = $this->helper->resolve($email, '<html><body>Draft</body></html>', true);

        $this->assertSame('<html><body>Draft</body></html>', $result->getContent());
        $this->assertFalse($result->isRenderedFromTheme());
    }

    public function testResolveRendersThemeWhenDraftOverrideIsNull(): void
    {
        $email = new Email();
        $email->setTemplate('_1-2-1-2-column');
        $email->setCustomHtml('<html><body>Published</body></html>');
        $email->setContent([]);

        $this->themeHelper->expects($this->once())
            ->method('checkForTwigTemplate')
            ->willReturn('@themes/_1-2-1-2-column/html/email.html.twig');

        $this->themeHelper->expects($this->once())
            ->method('renderThemeTemplate')
            ->willReturn('<mjml><mj-body><mj-text>Draft theme</mj-text></mj-body></mjml>');

        $result = $this->helper->resolve($email, null, true);

        $this->assertStringContainsString('Draft theme', $result->getContent());
        $this->assertTrue($result->isRenderedFromTheme());
    }
}
