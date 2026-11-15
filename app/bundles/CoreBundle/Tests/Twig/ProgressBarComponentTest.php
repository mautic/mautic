<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

final class ProgressBarComponentTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $loader = new FilesystemLoader(dirname(__DIR__, 2).'/Resources/views');
        $loader->addPath(dirname(__DIR__, 2).'/Resources/views', 'MauticCore');
        $this->twig = new Environment($loader);
        $this->twig->addFilter(new TwigFilter('trans', static fn (string $value): string => $value));
    }

    public function testDeterminateBarExposesProgressbarValues(): void
    {
        $output = $this->render(['label' => 'Import', 'value' => 40, 'max' => 80, 'id' => 'import']);

        $this->assertStringContainsString('role="progressbar"', $output);
        $this->assertStringContainsString('aria-labelledby="import-label"', $output);
        $this->assertStringContainsString('aria-valuemin="0" aria-valuemax="80" aria-valuenow="40"', $output);
        $this->assertStringContainsString('style="width: 50%"', $output);
        $this->assertStringContainsString('progress-component--big', $output);
        $this->assertStringNotContainsString('progress-bar-striped', $output);
    }

    public function testValueIsClampedToTheRange(): void
    {
        $this->assertStringContainsString('aria-valuenow="100"', $this->render(['label' => 'Over', 'value' => 150]));
        $this->assertStringContainsString('aria-valuenow="0"', $this->render(['label' => 'Under', 'value' => -5]));
    }

    public function testMissingValueIsIndeterminate(): void
    {
        $output = $this->render(['label' => 'Working']);

        $this->assertStringContainsString('progress-component--indeterminate', $output);
        $this->assertStringContainsString('progress-bar-striped progress-bar-animated', $output);
        $this->assertStringNotContainsString('aria-valuenow', $output);
        $this->assertStringContainsString('aria-busy="true"', $output);
    }

    public function testZeroIsDeterminate(): void
    {
        $output = $this->render(['label' => 'Start', 'value' => 0]);

        $this->assertStringContainsString('aria-valuenow="0"', $output);
        $this->assertStringContainsString('style="width: 0%"', $output);
    }

    public function testFinishedFillsTheBarAndShowsTheSuccessIcon(): void
    {
        $output = $this->render(['label' => 'Done', 'value' => 10, 'status' => 'finished']);

        $this->assertStringContainsString('progress-component--finished', $output);
        $this->assertStringContainsString('aria-valuenow="100"', $output);
        $this->assertStringContainsString('ri-checkbox-circle-fill', $output);
        $this->assertStringContainsString('aria-busy="false"', $output);
    }

    public function testEmptyMaximumFallsBackToTheDefaultRange(): void
    {
        $output = $this->render(['label' => 'Empty', 'value' => 0, 'max' => 0, 'status' => 'finished']);

        $this->assertStringContainsString('aria-valuemax="100" aria-valuenow="100"', $output);
        $this->assertStringContainsString('style="width: 100%"', $output);
    }

    public function testErrorFillsTheBarAndIsMarkedInvalid(): void
    {
        $output = $this->render(['label' => 'Failed', 'value' => 10, 'status' => 'error', 'helperText' => 'Upload failed']);

        $this->assertStringContainsString('progress-component--error', $output);
        $this->assertStringContainsString('aria-invalid="true"', $output);
        $this->assertStringContainsString('aria-valuenow="0"', $output);
        $this->assertStringContainsString('style="width: 100%"', $output);
        $this->assertStringContainsString('ri-error-warning-fill', $output);
    }

    public function testHelperTextIsLinkedAndAnnounced(): void
    {
        $output = $this->render(['label' => 'Send', 'value' => 1, 'helperText' => '1 of 4', 'id' => 'send']);

        $this->assertStringContainsString('aria-describedby="send-helper"', $output);
        $this->assertStringContainsString('id="send-helper"', $output);
        $this->assertStringContainsString('aria-live="polite">mautic.core.progress_bar.loading', $output);
        $this->assertStringNotContainsString('aria-describedby', $this->render(['label' => 'Send', 'value' => 1]));
    }

    public function testInlineHidesHelperTextAndTheFinishedTrack(): void
    {
        $active = $this->render(['label' => 'Inline', 'value' => 1, 'type' => 'inline', 'helperText' => 'Hidden']);
        $finished = $this->render(['label' => 'Inline', 'type' => 'inline', 'status' => 'finished']);

        $this->assertStringContainsString('progress-component--inline d-flex', $active);
        $this->assertStringContainsString('type-helper-text-01 mt-8 visually-hidden', $active);
        $this->assertStringContainsString('progress progress-component__track flex-grow-1 visually-hidden', $finished);
        $this->assertStringNotContainsString('progress-component__track flex-grow-1 visually-hidden', $active);
    }

    public function testSizeIndentedAndHiddenLabel(): void
    {
        $output = $this->render(['label' => 'Small', 'value' => 1, 'size' => 'small', 'type' => 'indented', 'hideLabel' => true]);

        $this->assertStringContainsString('progress-component--small', $output);
        $this->assertStringContainsString('progress-component--indented', $output);
        $this->assertStringContainsString('px-16 visually-hidden', $output);
    }

    public function testLabelIsEscaped(): void
    {
        $output = $this->render(['label' => '<script>alert(1)</script>', 'value' => 1]);

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function render(array $context): string
    {
        return $this->twig->render('Components/progress-bar.html.twig', $context);
    }
}
