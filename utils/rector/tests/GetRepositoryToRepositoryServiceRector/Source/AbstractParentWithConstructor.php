<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\GetRepositoryToRepositoryServiceRector\Source;

use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractParentWithConstructor
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }
}
