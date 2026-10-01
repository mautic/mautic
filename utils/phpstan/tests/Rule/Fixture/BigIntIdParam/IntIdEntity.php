<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BigIntIdParam;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

final class IntIdEntity
{
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->addId();
    }
}
