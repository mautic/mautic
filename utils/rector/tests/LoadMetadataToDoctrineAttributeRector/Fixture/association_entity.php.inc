<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\UserBundle\Entity\User;

class AssocEntity
{
    protected $id;

    protected $owner;

    protected $items;

    protected $lead;

    protected $category;

    protected $ipAddress;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('assoc_entity');

        $builder->addId();

        $builder->createManyToOne('owner', User::class)
            ->addJoinColumn('owner_id', 'id', true, false, 'SET NULL')
            ->cascadePersist()
            ->cascadeDetach()
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('items', ChildEntity::class)
            ->setIndexBy('id')
            ->mappedBy('parent')
            ->setOrderBy(['dateAdded' => 'DESC'])
            ->cascadeAll()
            ->orphanRemoval()
            ->build();

        $builder->addLead(true, 'CASCADE', false, 'items');

        $builder->addCategory();

        $builder->addIpAddress();
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\UserBundle\Entity\User;

#[ORM\Entity]
#[ORM\Table(name: 'assoc_entity')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class AssocEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    protected $id;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist', 'detach'], fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: 'owner_id', onDelete: 'SET NULL')]
    protected $owner;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: ChildEntity::class, cascade: ['all'], orphanRemoval: true, indexBy: 'id')]
    #[ORM\OrderBy(['dateAdded' => 'DESC'])]
    protected $items;

    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'lead_id', onDelete: 'CASCADE')]
    protected $lead;

    #[ORM\ManyToOne(targetEntity: \Mautic\CategoryBundle\Entity\Category::class, cascade: ['merge', 'detach'])]
    #[ORM\JoinColumn(name: 'category_id', onDelete: 'SET NULL')]
    protected $category;

    #[ORM\ManyToOne(targetEntity: \Mautic\CoreBundle\Entity\IpAddress::class, cascade: ['persist', 'merge', 'detach'])]
    #[ORM\JoinColumn(name: 'ip_id', nullable: false, onDelete: 'SET NULL')]
    protected $ipAddress;
}
