<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class SkipUnsupportedHelper
{
    protected $id;

    protected $name;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('some_table');

        $builder->addId();

        $builder->createManyToMany('items', SomeTarget::class)
            ->setJoinTable('some_join')
            ->addJoinColumn('some_id', 'id')
            ->addInverseJoinColumn('target_id', 'id')
            ->build();

        $builder->createField('name', 'string')
            ->build();
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity]
#[ORM\Table(name: 'some_table')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class SkipUnsupportedHelper
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    protected $id;

    #[ORM\Column(type: 'string', length: 191)]
    protected $name;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->createManyToMany('items', SomeTarget::class)
            ->setJoinTable('some_join')
            ->addJoinColumn('some_id', 'id')
            ->addInverseJoinColumn('target_id', 'id')
            ->build();
    }
}
