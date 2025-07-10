<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Loader;

use Doctrine\Common\DataFixtures\FixtureInterface;

interface FixturesLoaderInterface
{
    /**
     * Returns the array of data fixtures to execute.
     *
     * @param string[] $groups
     *
     * @return FixtureInterface[]
     */
    public function getFixtures(array $groups = []): array;
}
