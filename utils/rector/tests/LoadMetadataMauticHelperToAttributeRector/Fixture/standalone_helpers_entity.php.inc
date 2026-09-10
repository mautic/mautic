<?php

namespace Utils\Rector\Tests\LoadMetadataMauticHelperToAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class StandaloneHelpersEntity
{
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('things');

        $builder->addIdColumns();

        $builder->addPublishDates();
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataMauticHelperToAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class StandaloneHelpersEntity
{
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('things');
        $builder->createField('id', 'integer')->makePrimaryKey()->generatedValue()->option('unsigned', true)->build();
        $builder->createField('name', 'string')->build();
        $builder->createField('description', 'text')->nullable()->build();
        $builder->createField('publishUp', 'datetime')->columnName('publish_up')->nullable()->build();
        $builder->createField('publishDown', 'datetime')->columnName('publish_down')->nullable()->build();
    }
}
