<?php

namespace Utils\Rector\Tests\LoadMetadataMauticHelperToAttributeRector\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class MixedChainEntity
{
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('mix')
            ->setCustomRepositoryClass(MixRepository::class)
            ->addNamedField('customField', Types::TEXT, 'custom_field', true)
            ->addId();
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataMauticHelperToAttributeRector\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class MixedChainEntity
{
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('mix');
        $builder->setCustomRepositoryClass(MixRepository::class);
        $builder->createField('customField', Types::TEXT)->columnName('custom_field')->nullable()->build();
        $builder->createField('id', 'integer')->makePrimaryKey()->generatedValue()->option('unsigned', true)->build();
    }
}
