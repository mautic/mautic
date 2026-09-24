<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRules\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity]
#[ORM\Table(name: 'hybrid_helpers')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class HybridHelpersDropLoadMetadataEntity
{
    private $id;

    private $name;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addId();

        $builder->addNullableField('name', 'string');
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRules\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity]
#[ORM\Table(name: 'hybrid_helpers')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class HybridHelpersDropLoadMetadataEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $name;
}

?>
