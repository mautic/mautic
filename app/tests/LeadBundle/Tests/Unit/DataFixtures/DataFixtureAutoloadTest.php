<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Unit\DataFixtures;

use Composer\Autoload\ClassLoader;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadClickData;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadDncData;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadPageHitData;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadSegmentsData;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadTagData;
use PHPUnit\Framework\TestCase;

/**
 * LeadBundle registers these fixtures as services in Config/services.php,
 * so they must stay autoloadable in a production install (composer --no-dev),
 * where autoload-dev is dropped. This builds a loader from the production
 * autoload section of app/composer.json only and asserts it resolves them.
 */
final class DataFixtureAutoloadTest extends TestCase
{
    private const array SERVICE_FIXTURE_CLASSES = [
        LoadClickData::class,
        LoadDncData::class,
        LoadPageHitData::class,
        LoadSegmentsData::class,
        LoadTagData::class,
    ];

    public function testFixtureServicesResolveViaProductionAutoload(): void
    {
        $appDir   = dirname(__DIR__, 5);
        $contents = file_get_contents($appDir.'/composer.json');
        $this->assertIsString($contents);

        $composer = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($composer);
        $this->assertIsArray($composer['autoload']);

        $productionPsr4 = $composer['autoload']['psr-4'];
        $this->assertIsArray($productionPsr4);
        $this->assertArrayHasKey('Mautic\\', $productionPsr4, 'Mautic\\ must be mapped in production autoload');

        $mauticSourceDir = $productionPsr4['Mautic\\'];
        $this->assertIsString($mauticSourceDir);

        $loader = new ClassLoader();
        $loader->addPsr4('Mautic\\', $appDir.'/'.$mauticSourceDir);

        foreach (self::SERVICE_FIXTURE_CLASSES as $fixtureClass) {
            $this->assertIsString(
                $loader->findFile($fixtureClass),
                sprintf('%s must autoload without dev dependencies', $fixtureClass)
            );
        }

        // relocated tests live in app/tests (dev-only); the production loader must not reach them,
        // proving the assertions above are not satisfied by a leaked dev autoload entry
        $this->assertFalse(
            $loader->findFile(self::class),
            'relocated tests must not be reachable via production autoload'
        );
    }
}
