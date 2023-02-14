<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Tests\Functional;

trait LoadFixturesTrait
{
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $fixtureFilePath = self::$kernel->locateResource('@OpenIdBundle/Resources/fixtures/subject_id.yaml');
        $this->loadFixtureFiles([$fixtureFilePath]);
    }
}
