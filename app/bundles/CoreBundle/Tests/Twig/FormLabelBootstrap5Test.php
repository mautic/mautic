<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\Forms;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

final class FormLabelBootstrap5Test extends TestCase
{
    public function testLabelsGetBootstrapClassAndKeepCustomClasses(): void
    {
        $views  = dirname(__DIR__, 2).'/Resources/views';
        $loader = new FilesystemLoader([dirname(__DIR__, 5).'/vendor/symfony/twig-bridge/Resources/views/Form']);
        $loader->addPath($views, 'MauticCore');
        $twig = new Environment(new ChainLoader([
            new ArrayLoader(['page.html.twig' => '{% form_theme form "FormTheme/mautic_form_layout.html.twig" %}{{ form_label(form.custom) }}{{ form_label(form.default) }}']),
            new FilesystemLoader([$views]),
            $loader,
        ]));
        $twig->addExtension(new TranslationExtension());
        $twig->addFilter(new TwigFilter('purify', static fn (string $value): string => $value, ['is_safe' => ['html']]));
        $twig->addFilter(new TwigFilter('trans', static fn (string $value): string => $value));
        $twig->addFilter(new TwigFilter('humanize', static fn (string $value): string => ucfirst(str_replace('_', ' ', $value))));
        $twig->addTest(new TwigTest('numeric', static fn ($value): bool => is_numeric($value)));
        $twig->addTest(new TwigTest('string', static fn ($value): bool => is_string($value)));
        $twig->addFunction(new TwigFunction('toggleYesNo', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('formFieldFormatList', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('path', static fn (): string => '#'));
        $twig->addFunction(new TwigFunction('asset', static fn (): string => '#'));
        $twig->addFunction(new TwigFunction('mautic_asset', static fn (): string => '#'));
        $twig->addExtension(new FormExtension());
        $renderer = new FormRenderer(new TwigRendererEngine(['form_div_layout.html.twig'], $twig));
        $twig->addRuntimeLoader(new class($renderer) implements RuntimeLoaderInterface {
            public function __construct(private readonly FormRendererInterface $renderer)
            {
            }

            public function load(string $class): ?object
            {
                return FormRenderer::class === $class ? $this->renderer : null;
            }
        });

        $form = Forms::createFormFactory()->createNamedBuilder('f')
            ->add('custom', TextType::class, [
                'label'      => 'Custom label',
                'label_attr' => ['class' => 'custom-label'],
                'required'   => true,
            ])
            ->add('default', TextType::class, [
                'label'    => 'Default label',
                'required' => false,
            ])
            ->getForm()
            ->createView();

        $output = $twig->render('page.html.twig', ['form' => $form]);

        $this->assertStringContainsString('class="custom-label form-label required"', $output);
        $this->assertStringContainsString('class="form-label"', $output);
    }
}
