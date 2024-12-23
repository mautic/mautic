<?php

namespace Mautic\CoreBundle\Doctrine\Loader;

use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;

/**
 * The sole purpose of this class is to make SymfonyFixturesLoader mockable.
 */
final class MauticFixturesLoader implements FixturesLoaderInterface
{
    public function __construct(
        private SymfonyFixturesLoader $fixturesLoader,
    ) {
    }

    public function getFixtures(array $groups = []): array
    {
        return $this->fixturesLoader->getFixtures($groups);
    }
}
