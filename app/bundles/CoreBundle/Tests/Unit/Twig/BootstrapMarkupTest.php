<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig;

use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Mautic\FormBundle\Form\Type\FormFieldRatingType;
use Mautic\LeadBundle\Form\Type\CompanyChangeScoreActionType;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

final class BootstrapMarkupTest extends TestCase
{
    private Environment $twig;

    private string $defaultLocale;

    protected function setUp(): void
    {
        $this->defaultLocale = \Locale::getDefault();
        $loader = new FilesystemLoader(__DIR__.'/../../../Resources/views');
        $loader->addPath(__DIR__.'/../../../Resources/views', 'MauticCore');
        $loader->addPath(dirname((new \ReflectionClass(FormExtension::class))->getFileName(), 2).'/Resources/views/Form');
        $this->twig = new Environment($loader, ['autoescape' => 'html']);
        $this->twig->addExtension(new TranslationExtension());
        $this->twig->addExtension(new FormExtension());
        $this->twig->addFilter(new TwigFilter('purify', static fn (string $value): string => $value));
        $this->twig->addTest(new TwigTest('numeric', is_numeric(...)));
        $this->twig->addTest(new TwigTest('string', is_string(...)));
        $this->twig->addFunction(new TwigFunction('formFieldFormatList', static fn (): string => ''));
        $renderer = new FormRenderer(new TwigRendererEngine(['form_div_layout.html.twig', 'FormTheme/mautic_form_layout.html.twig'], $this->twig));
        $this->twig->addRuntimeLoader(new FactoryRuntimeLoader([FormRenderer::class => static fn (): FormRenderer => $renderer]));
    }

    protected function tearDown(): void
    {
        \Locale::setDefault($this->defaultLocale);
    }

    public function testTabsRenderNativeSelectionAndFadeState(): void
    {
        $html = $this->twig->render('Helper/nav_tabs.html.twig', [
            'tabs' => [
                ['id' => 'first', 'title' => 'first', 'content' => 'first content'],
                ['id' => 'second', 'title' => 'second', 'content' => 'second content'],
            ],
        ]);
        $crawler = new Crawler($html);

        $this->assertCount(2, $crawler->filter('.nav-link[data-bs-toggle="tab"]'));
        $this->assertSame('#first', $crawler->filter('.nav-link.active')->attr('href'));
        $this->assertSame('true', $crawler->filter('.nav-link.active')->attr('aria-selected'));
        $this->assertSame('first', $crawler->filter('.tab-pane.active.show')->attr('id'));
        $this->assertCount(0, $crawler->filter('[data-toggle], .tab-pane.in'));
    }

    public function testButtonGroupUsesSiblingInputsAndLabelsWithoutJavascript(): void
    {
        $form = Forms::createFormFactory()->createNamed('direction', ButtonGroupType::class, 'outgoing', [
            'choices' => ['Incoming' => 'incoming', 'Outgoing' => 'outgoing'],
        ]);
        $html = $this->twig->createTemplate('{{ form_widget(form) }}')->render(['form' => $form->createView()]);
        $crawler = new Crawler($html);

        $this->assertCount(2, $crawler->filter('input.btn-check + label.btn'));
        $this->assertSame('outgoing', $crawler->filter('input.btn-check:checked')->attr('value'));
        $this->assertSame('direction_1', $crawler->filter('input.btn-check:checked + label')->attr('for'));
        $this->assertCount(0, $crawler->filter('[data-toggle="buttons"], label input'));
    }

    public function testDisabledButtonGroupRetainsDisabledInputs(): void
    {
        $form = Forms::createFormFactory()->createNamed('direction', ButtonGroupType::class, 'outgoing', [
            'choices' => ['Incoming' => 'incoming', 'Outgoing' => 'outgoing'],
            'disabled' => true,
        ]);
        $html = $this->twig->createTemplate('{{ form_widget(form) }}')->render(['form' => $form->createView()]);

        $this->assertCount(2, (new Crawler($html))->filter('input.btn-check:disabled + label.btn'));
    }

    public function testNumberStepperRoundTripsIntegersInNonAsciiLocale(): void
    {
        \Locale::setDefault('fa_IR');
        $form = Forms::createFormFactory()->createNamed('score', NumberType::class, 1234, [
            'block_prefix' => 'quantity_stepper',
            'html5'        => true,
            'scale'        => 0,
            'attr'         => ['size' => 'sm', 'digits' => 5],
        ]);
        $html = $this->twig->createTemplate('{{ form_widget(form) }}')->render(['form' => $form->createView()]);
        $input = (new Crawler($html))->filter('input[type="number"]');

        $this->assertSame('1234', $input->attr('value'));
        $this->assertSame('1', $input->attr('step'));
        $form->submit('-125');
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(-125, $form->getData());
    }

    public function testCompanyScoreRendersAndSubmitsNegativePoints(): void
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();
        $form = $factory->createNamed('action', CompanyChangeScoreActionType::class);
        $html = $this->twig->createTemplate('{{ form_row(form.score) }}')->render(['form' => $form->createView()]);
        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('label[for="action_score"]'));
        $this->assertCount(0, $crawler->filter('input[tabindex="-1"], input[type="hidden"]'));
        $this->assertCount(1, $crawler->filter('.quantity-stepper--sm input[type="number"][name="action[score]"]'));
        $this->assertCount(0, $crawler->filter('input[min], input[max]'));
        $this->assertSame('1', $crawler->filter('input[name="action[score]"]')->attr('step'));
        $form->submit(['score' => '-125']);
        $this->assertTrue($form->isValid());
        $this->assertEquals(-125, $form->getData()['score']);
    }

    public function testCompanyScoreRoundTripsIntegersInNonAsciiLocale(): void
    {
        \Locale::setDefault('fa_IR');
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();
        $form = $factory->createNamed('action', CompanyChangeScoreActionType::class, ['score' => 1234]);
        $html = $this->twig->createTemplate('{{ form_row(form.score) }}')->render(['form' => $form->createView()]);
        $input = (new Crawler($html))->filter('input[name="action[score]"]');

        $this->assertSame('1234', $input->attr('value'));
        $this->assertSame('1', $input->attr('step'));
        $form->submit(['score' => '-125']);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(-125, $form->getData()['score']);
    }

    public function testRatingRetainsItsRangeAndSubmittedValue(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);
        $factory = Forms::createFormFactoryBuilder()->addType(new FormFieldRatingType($translator))->getFormFactory();
        $form = $factory->createNamed('rating', FormFieldRatingType::class);
        $html = $this->twig->createTemplate('{{ form_widget(form.star_count) }}')->render(['form' => $form->createView()]);
        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('.quantity-stepper--sm input[min="1"][max="10"][value="5"]'));
        $form->submit(['star_count' => '8', 'symbol' => '★']);
        $this->assertTrue($form->isSynchronized());
        $this->assertSame(8, $form->getData()['star_count']);
    }

    public function testNativeAttributesAndPercentSuffixAreKept(): void
    {
        $form = Forms::createFormFactory()->createNamed('weight', IntegerType::class, 10, [
            'block_prefix' => 'quantity_stepper',
            'attr' => ['readonly' => true, 'data-show-on' => '{"enabled":"checked"}', 'postaddon_text' => '%', 'size' => 'sm', 'digits' => 3],
        ]);
        $html = $this->twig->createTemplate('{{ form_widget(form) }}')->render(['form' => $form->createView()]);
        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('input[readonly][data-show-on]'));
        $this->assertSame('{"enabled":"checked"}', $crawler->filter('input')->attr('data-show-on'));
        $this->assertCount(2, $crawler->filter('button:disabled'));
        $this->assertStringContainsString('<span>%</span>', $html);
        $this->assertCount(0, $crawler->filter('input[size], input[digits], input[postaddon_text]'));
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
        $this->assertStringContainsString('<span class="quantity-stepper__digit">1</span>', $output);
        $this->assertStringContainsString('role="group"', $output);
    }

    public function testValueZeroIsKept(): void
    {
        $this->assertStringContainsString('value="0"', $this->render(['name' => 'qty', 'value' => 0]));
    }

    public function testEmptyUnboundedValuesAreNotReplaced(): void
    {
        $output = $this->render(['name' => 'qty', 'value' => '', 'min' => null, 'max' => null]);

        $this->assertStringContainsString('value=""', $output);
        $this->assertStringNotContainsString(' min="', $output);
        $this->assertStringNotContainsString(' max="', $output);
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

    public function testDefaultValueStaysInsideNegativeBounds(): void
    {
        $this->assertStringContainsString('value="-1"', $this->render(['name' => 'qty', 'min' => -10, 'max' => -1]));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function render(array $context): string
    {
        return $this->twig->render('Components/quantity-stepper.html.twig', $context);
    }
}
