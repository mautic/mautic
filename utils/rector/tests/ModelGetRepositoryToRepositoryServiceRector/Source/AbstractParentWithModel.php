<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ModelGetRepositoryToRepositoryServiceRector\Source;

abstract class AbstractParentWithModel
{
    public function __construct(
        protected SomeModel $someModel,
    ) {
    }
}
