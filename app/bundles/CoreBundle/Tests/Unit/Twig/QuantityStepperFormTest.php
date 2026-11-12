<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig;

use Mautic\FormBundle\Form\Type\FormFieldRatingType;
use Mautic\LeadBundle\Form\Type\CompanyChangeScoreActionType;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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

final class QuantityStepperFormTest extends TestCase
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

    public function testCompanyScoreRendersAndSubmitsNegativePoints(): void
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();
        $form = $factory->createNamed('action', CompanyChangeScoreActionType::class);
        $html = $this->twig->createTemplate('{{ form_widget(form.score) }}')->render(['form' => $form->createView()]);
        $crawler = new Crawler($html);

        $this->assertCount(1, $crawler->filter('.quantity-stepper--sm input[type="number"][name="action[score]"]'));
        $this->assertCount(0, $crawler->filter('input[min], input[max]'));
        $form->submit(['score' => '-125']);
        $this->assertTrue($form->isValid());
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
}
