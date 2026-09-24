<?php

namespace Utils\Rector\Tests\LoadMetadataMauticHelperToAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity]
#[ORM\Table(name: 'already_mapped')]
class AlreadyMappedEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private $id;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('already_mapped');

        $builder->addId();
    }
}
-----
<?php

namespace Utils\Rector\Tests\LoadMetadataMauticHelperToAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity]
#[ORM\Table(name: 'already_mapped')]
class AlreadyMappedEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private $id;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('already_mapped');

        $builder->createField('id', 'integer')->makePrimaryKey()->generatedValue()->option('unsigned', true)->build();
    }
}
