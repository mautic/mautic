<?php

namespace Utils\Rector\Tests\LoadMetadataMauticHelperToAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class IndexOptionsEntity
{
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('hits')
            ->addIndex(['tracking_id'], 'hit_tracking')
            ->addFulltextIndex(['content'], 'hit_content')
            ->addIndexWithOptions(['url'], 'hit_url', ['lengths' => [0 => 128]]);

        $builder->addBigIntIdField();
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataMauticHelperToAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class IndexOptionsEntity
{
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('hits')
            ->addIndex(['tracking_id'], 'hit_tracking')
            ->addIndex(['content'], 'hit_content', ['fulltext'])
            ->addIndex(['url'], 'hit_url', null, ['lengths' => [0 => 128]]);

        $builder->createField('id', 'bigint')->columnName('id')->makePrimaryKey()->generatedValue()->option('unsigned', true)->build();
    }
}
