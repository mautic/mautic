<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig;

use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Mautic\CoreBundle\Form\Type\SegmentedControlType;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

final class BootstrapMarkupTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
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

    public function testSegmentedControlSelectsOnlyTheExactChoiceValue(): void
    {
        $formFactory = Forms::createFormFactoryBuilder()
            ->addType(new ButtonGroupType())
            ->addType(new SegmentedControlType())
            ->getFormFactory();
        $form = $formFactory->createNamed('mode', SegmentedControlType::class, '0', [
            'label'                    => 'Mode',
            'choices'                  => ['Zero' => '0', 'Double zero' => '00'],
            'choice_translation_domain' => false,
        ]);
        $html = $this->twig->createTemplate('{{ form_widget(form) }}')->render(['form' => $form->createView()]);
        $checkedChoices = (new Crawler($html))->filter('input.btn-check:checked');

        $this->assertCount(1, $checkedChoices);
        $this->assertSame('0', $checkedChoices->attr('value'));
    }

    public function testSegmentedControlPassesChoiceAttributesToInputs(): void
    {
        $formFactory = Forms::createFormFactoryBuilder()
            ->addType(new ButtonGroupType())
            ->addType(new SegmentedControlType())
            ->getFormFactory();
        $form = $formFactory->createNamed('mode', SegmentedControlType::class, 'a', [
            'choices'                   => ['A' => 'a', 'B' => 'b'],
            'choice_attr'               => ['B' => ['data-test' => 'choice-b', 'class' => 'custom-choice']],
            'choice_translation_domain' => false,
        ]);
        $html = $this->twig->createTemplate('{{ form_widget(form) }}')->render(['form' => $form->createView()]);

        $crawler = new Crawler($html);

        $this->assertSame('choice-b', $crawler->filter('input[value="b"]')->attr('data-test'));
        $this->assertSame('btn-check custom-choice', $crawler->filter('input[value="b"]')->attr('class'));
        $this->assertNull($crawler->filter('input[value="a"]')->attr('data-test'));
    }

    public function testSegmentedControlForwardsCallerClassToGroup(): void
    {
        $formFactory = Forms::createFormFactoryBuilder()
            ->addType(new ButtonGroupType())
            ->addType(new SegmentedControlType())
            ->getFormFactory();
        $form = $formFactory->createNamed('mode', SegmentedControlType::class, 'a', [
            'choices'                   => ['A' => 'a', 'B' => 'b'],
            'attr'                      => ['class' => 'caller-class'],
            'choice_translation_domain' => false,
        ]);
        $html = $this->twig->createTemplate('{{ form_widget(form) }}')->render(['form' => $form->createView()]);
        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('.segmented-control.caller-class'));
        $this->assertCount(0, $crawler->filter('input.caller-class'));
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

    public function testSegmentedControlGetsLockedClassWhenReadonly(): void
    {
        $formFactory = Forms::createFormFactoryBuilder()
            ->addType(new ButtonGroupType())
            ->addType(new SegmentedControlType())
            ->getFormFactory();
        $options = ['choice_translation_domain' => false, 'choices' => ['A' => 'a', 'B' => 'b']];
        $render = fn (array $formOptions): Crawler => new Crawler(
            $this->twig->createTemplate('{{ form_widget(form) }}')->render([
                'form' => $formFactory->createNamed('mode', SegmentedControlType::class, 'a', $formOptions + $options)->createView(),
            ])
        );

        $this->assertCount(1, $render(['attr' => ['readonly' => 'readonly']])->filter('.segmented-control.segmented-control--locked'));
        $this->assertCount(0, $render([])->filter('.segmented-control--locked'));
    }
}
