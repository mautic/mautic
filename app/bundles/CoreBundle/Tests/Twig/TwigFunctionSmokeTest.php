<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

use Mautic\CoreBundle\Form\Type\TelType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

/**
 * Smoke test: every Twig function declared by Mautic - either by the #[AsTwigFunction] attribute or by the
 * getFunctions() method - must be loaded in the Twig environment built by the container.
 *
 * @see TwigCallableRegistrationTest for the same check on a standalone Twig environment
 */
final class TwigFunctionSmokeTest extends KernelTestCase
{
    /**
     * Hand-picked functions from various bundles and extensions, to catch a discovery that silently
     * stops finding classes.
     *
     * @var string[]
     */
    private const EXPECTED_FUNCTIONS = [
        'mauticAppVersion',      // CoreBundle, VersionExtension
        'securityIsGranted',     // CoreBundle, SecurityExtension
        'dateToFull',            // CoreBundle, DateExtension
        'getAssetUrl',           // CoreBundle, AssetExtension
        'translatorHasId',       // CoreBundle, TranslatorExtension
        'getEntities',           // CoreBundle, EntityHelper
        'leadGetAvatar',         // LeadBundle, LeadExtension
        'getChannelLabel',       // ChannelBundle, ChannelExtension
        'getCampaignEventIcon',  // CampaignBundle, CampaignEventIconExtension
    ];

    protected function setUp(): void
    {
        self::bootKernel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // The kernel boot registers an exception handler that is not removed on shutdown.
        // PHPUnit 11.5 fails the test if a leaked handler remains on the stack.
        // @see https://github.com/sebastianbergmann/phpunit/issues/5721
        restore_exception_handler();
    }

    public function testKnownTwigFunctionsAreLoadedInEnvironment(): void
    {
        $twigEnvironment = self::getContainer()->get(Environment::class);
        \assert($twigEnvironment instanceof Environment);

        foreach (self::EXPECTED_FUNCTIONS as $name) {
            $this->assertNotNull(
                $twigEnvironment->getFunction($name),
                sprintf('Twig function "%s" is not loaded in the Twig environment.', $name)
            );
        }
    }

    /**
     * @see Fixtures/functions/get_class.test of the removed TwigIntegrationTest
     */
    public function testGetClassFunctionRendersShortClassName(): void
    {
        $twigEnvironment = self::getContainer()->get(Environment::class);
        \assert($twigEnvironment instanceof Environment);

        $rendered = $twigEnvironment->createTemplate('{{ get_class(class) }}')
            ->render(['class' => TelType::class]);

        $this->assertSame('TelType', $rendered);
    }

    /**
     * The asset URL carries an environment-specific prefix and version, so only the surrounding
     * script tag is asserted.
     *
     * @see Fixtures/functions/includeScript.test of the removed TwigIntegrationTest
     */
    public function testIncludeScriptFunctionRendersScriptTag(): void
    {
        $twigEnvironment = self::getContainer()->get(Environment::class);
        \assert($twigEnvironment instanceof Environment);

        $assetFilePath = 'app/bundles/IntegrationsBundle/Assets/js/integrations.js';

        $rendered = $twigEnvironment->createTemplate("{{ includeScript(path, 'integrationsConfigOnLoad', 'integrationsConfigOnLoad') }}")
            ->render(['path' => $assetFilePath]);

        $this->assertStringStartsWith('<script async="async" type="text/javascript" data-source="mautic">Mautic.loadScript(\'', $rendered);
        $this->assertStringContainsString($assetFilePath, $rendered);
        $this->assertStringEndsWith("', 'integrationsConfigOnLoad', 'integrationsConfigOnLoad');</script>", $rendered);

        $renderedWithoutCallbacks = $twigEnvironment->createTemplate('{{ includeScript(path) }}')
            ->render(['path' => $assetFilePath]);

        $this->assertStringEndsWith("', '', '');</script>", $renderedWithoutCallbacks);
    }

    public function testDeclaredTwigFunctionsAreLoadedInEnvironment(): void
    {
        $twigEnvironment = self::getContainer()->get(Environment::class);
        \assert($twigEnvironment instanceof Environment);

        $declaredFunctionCount = 0;
        $missingFunctions      = [];

        foreach (TwigCallableRegistrationTest::provideDeclaredTwigCallables() as [$kind, $name, $class]) {
            if ('function' !== $kind) {
                continue;
            }

            ++$declaredFunctionCount;

            if (null === $twigEnvironment->getFunction($name)) {
                $missingFunctions[] = sprintf('%s (%s)', $name, $class);
            }
        }

        $this->assertSame([], $missingFunctions, 'These Twig functions are declared by Mautic, but not loaded in the Twig environment.');

        // guard against the discovery finding nothing and the test passing by accident
        $this->assertGreaterThan(50, $declaredFunctionCount);
    }
}
