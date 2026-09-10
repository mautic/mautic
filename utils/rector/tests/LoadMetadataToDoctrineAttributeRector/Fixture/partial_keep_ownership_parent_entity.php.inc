<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class OwnEntity
{
    protected $id;

    protected $form;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('own_entity');

        $builder->addId();

        $builder->createManyToOne('form', 'Form')
            ->inversedBy('actions')
            ->addJoinColumn('form_id', 'id', false, false, 'CASCADE')
            ->isOwnershipParent()
            ->build();
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity]
#[ORM\Table(name: 'own_entity')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class OwnEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    protected $id;

    protected $form;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->createManyToOne('form', 'Form')
            ->inversedBy('actions')
            ->addJoinColumn('form_id', 'id', false, false, 'CASCADE')
            ->isOwnershipParent()
            ->build();
    }
}
