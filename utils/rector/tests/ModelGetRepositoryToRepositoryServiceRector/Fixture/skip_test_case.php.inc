<?php

namespace Utils\Rector\Tests\ModelGetRepositoryToRepositoryServiceRector\Fixture;

use PHPUnit\Framework\TestCase;
use Utils\Rector\Tests\ModelGetRepositoryToRepositoryServiceRector\Source\SomeModel;

final class SkipTestCase extends TestCase
{
    public function testThing(): void
    {
        $someModel = $this->createMock(SomeModel::class);

        $someModel->getRepository()->findThing(1);
    }
}
