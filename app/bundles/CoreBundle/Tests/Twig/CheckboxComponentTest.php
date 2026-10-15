<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

final class CheckboxComponentTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $loader = new FilesystemLoader(dirname(__DIR__, 2).'/Resources/views');
        $this->twig = new Environment($loader);
        $this->twig->addFilter(new TwigFilter('trans', static fn (string $value): string => $value));
        $this->twig->addFilter(new TwigFilter('purify', static fn (string $value): string => $value, ['is_safe' => ['html']]));
    }

    public function testLabelHtmlIsRenderedOnlyWhenEnabled(): void
    {
        $html = $this->render([
            'items' => [['id' => 'html_on', 'value' => '1', 'label' => 'Read <a href="/x">docs</a>', 'label_html' => true]],
        ]);
        $plain = $this->render([
            'items' => [['id' => 'html_off', 'value' => '1', 'label' => 'Read <a href="/x">docs</a>', 'label_html' => false]],
        ]);

        $this->assertStringContainsString('<a href="/x">docs</a>', $html);
        $this->assertStringNotContainsString('<a href', $plain);
        $this->assertStringContainsString('&lt;a href=&quot;/x&quot;&gt;docs&lt;/a&gt;', $plain);
    }

    public function testItemsDoNotRequireInputsUnlessGroupIsRequired(): void
    {
        $output = $this->render([
            'name'  => 'events[]',
            'items' => [
                ['id' => 'events_1', 'value' => '1', 'label' => 'One'],
                ['id' => 'events_2', 'value' => '2', 'label' => 'Two'],
            ],
        ]);

        $this->assertSame(0, substr_count($output, ' required'));
        $this->assertSame(2, substr_count($output, 'name="events[]"'));
    }

    public function testRequiredGroupMarksEveryInput(): void
    {
        $output = $this->render([
            'name'     => 'events[]',
            'required' => true,
            'items'    => [
                ['id' => 'events_1', 'value' => '1', 'label' => 'One'],
                ['id' => 'events_2', 'value' => '2', 'label' => 'Two'],
            ],
        ]);

        $this->assertSame(2, substr_count($output, 'required'));
    }

    public function testFalseLabelRendersNoLabel(): void
    {
        $output = $this->render([
            'items' => [['id' => 'no_label', 'value' => '1', 'label' => false]],
        ]);

        $this->assertStringNotContainsString('<label', $output);
    }

    public function testLabelAttributesAndTextAreEscaped(): void
    {
        $output = $this->render([
            'items' => [[
                'id'              => 'escaped',
                'value'           => '1',
                'label'           => 'Option <b>x</b>',
                'labelAttributes' => ['title' => '"><script>alert(1)</script>'],
            ]],
        ]);

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringNotContainsString('<b>x</b>', $output);
        $this->assertStringContainsString('&quot;&gt;&lt;script&gt;', $output);
    }

    public function testItemAttributesArePassedAndClassIsMerged(): void
    {
        $output = $this->render([
            'items' => [[
                'id'         => 'attr_item',
                'value'      => '1',
                'label'      => 'Attr',
                'className'  => 'list-checkbox',
                'attributes' => ['class' => 'extra-class', 'data-toggle' => 'selectrow', 'onclick' => false, 'data-x' => null],
            ]],
        ]);

        $this->assertStringContainsString('class="form-check-input list-checkbox extra-class"', $output);
        $this->assertStringContainsString('data-toggle="selectrow"', $output);
        $this->assertStringNotContainsString('onclick', $output);
        $this->assertStringNotContainsString('data-x', $output);
    }

    public function testInputWithoutNameHasNoNameAttribute(): void
    {
        $output = $this->render([
            'items' => [['id' => 'no_name', 'value' => '1', 'label' => 'No name']],
        ]);

        $this->assertStringNotContainsString('name=', $output);
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function render(array $variables): string
    {
        return $this->twig->load('Components/checkbox.html.twig')->render($variables);
    }
}
