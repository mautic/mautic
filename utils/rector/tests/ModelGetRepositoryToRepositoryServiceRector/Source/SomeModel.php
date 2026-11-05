<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ModelGetRepositoryToRepositoryServiceRector\Source;

use Mautic\CoreBundle\Model\AbstractCommonModel;

/**
 * @extends AbstractCommonModel<object>
 */
class SomeModel extends AbstractCommonModel
{
    public function __construct(
        private readonly SomeRepository $someRepository,
    ) {
    }

    public function getRepository(): SomeRepository
    {
        return $this->someRepository;
    }
}
