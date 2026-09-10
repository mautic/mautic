<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class PromotedPropertyEntity
{
    private $id;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('promoted_entity');
        $builder->addId();

        $builder->createField('name', 'string')
            ->columnName('name')
            ->length(45)
            ->build();
    }

    public function __construct(
        private ?string $name = null,
    ) {
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity]
#[ORM\Table(name: 'promoted_entity')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class PromotedPropertyEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    public function __construct(
        #[ORM\Column(type: 'string', length: 45)]
        private ?string $name = null,
    ) {
    }
}
