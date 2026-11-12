<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

final class QuantityStepperComponentTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $loader = new FilesystemLoader(dirname(__DIR__, 2).'/Resources/views');
        $loader->addPath(dirname(__DIR__, 2).'/Resources/views', 'MauticCore');
        $this->twig = new Environment($loader);
        $this->twig->addFilter(new TwigFilter('trans', static fn (string $value): string => $value));
    }

    public function testRendersNativeNumberInputWithDefaults(): void
    {
        $output = $this->render(['name' => 'qty']);

        $this->assertStringContainsString('type="number"', $output);
        $this->assertStringContainsString('name="qty"', $output);
        $this->assertStringContainsString('value="1"', $output);
        $this->assertStringContainsString('min="0" max="99" step="1"', $output);
        $this->assertStringContainsString('quantity-stepper--md', $output);
        $this->assertStringContainsString('data-quantity-step="-1"', $output);
        $this->assertStringContainsString('data-quantity-step="1"', $output);
    }

    public function testValueZeroIsKept(): void
    {
        $this->assertStringContainsString('value="0"', $this->render(['name' => 'qty', 'value' => 0]));
    }

    public function testDisabledDisablesAllControls(): void
    {
        $output = $this->render(['name' => 'qty', 'disabled' => true]);

        $this->assertSame(3, substr_count($output, ' disabled'));
    }

    public function testSizeAndBoundsAreConfigurable(): void
    {
        $output = $this->render(['name' => 'qty', 'size' => 'sm', 'min' => 2, 'max' => 10, 'step' => 2, 'value' => 4]);

        $this->assertStringContainsString('quantity-stepper--sm', $output);
        $this->assertStringContainsString('min="2" max="10" step="2"', $output);
        $this->assertStringContainsString('value="4"', $output);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function render(array $context): string
    {
        return $this->twig->render('Components/quantity-stepper.html.twig', $context);
    }
}
