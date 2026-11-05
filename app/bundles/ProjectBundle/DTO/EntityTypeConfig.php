<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\DTO;

use Mautic\CoreBundle\Model\FormModel;

final readonly class EntityTypeConfig
{
    public function __construct(
        public string $entityClass,
        public string $label,
        public ?FormModel $model = null,
        public ?DetailRoute $detailRoute = null,
    ) {
    }
}
