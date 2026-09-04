<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\LeadList;

class ManyToManyAndOneToOneEntity
{
    protected $id;

    protected $lists;

    protected $owning;

    protected $inverse;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('m2m_o2o_entity');

        $builder->addId();

        $builder->createManyToMany('lists', LeadList::class)
            ->setJoinTable('m2m_o2o_list_xref')
            ->setIndexBy('id')
            ->addInverseJoinColumn('leadlist_id', 'id', false, false, 'CASCADE')
            ->addJoinColumn('entity_id', 'id', false, false, 'CASCADE')
            ->setOrderBy(['name' => 'ASC'])
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToOne('owning', OtherEntity::class)
            ->inversedBy('inverse')
            ->addJoinColumn('owning_id', 'id', false)
            ->build();

        $builder->createOneToOne('inverse', OtherEntity::class)
            ->mappedBy('owning')
            ->fetchExtraLazy()
            ->cascadeAll()
            ->build();
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\LeadList;

#[ORM\Entity]
#[ORM\Table(name: 'm2m_o2o_entity')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class ManyToManyAndOneToOneEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    protected $id;

    #[ORM\ManyToMany(targetEntity: LeadList::class, fetch: 'EXTRA_LAZY', indexBy: 'id')]
    #[ORM\JoinTable(name: 'm2m_o2o_list_xref')]
    #[ORM\JoinColumn(name: 'entity_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'leadlist_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    protected $lists;

    #[ORM\OneToOne(targetEntity: OtherEntity::class, inversedBy: 'inverse')]
    #[ORM\JoinColumn(name: 'owning_id', nullable: false)]
    protected $owning;

    #[ORM\OneToOne(mappedBy: 'owning', targetEntity: OtherEntity::class, cascade: ['all'], fetch: 'EXTRA_LAZY')]
    protected $inverse;
}
