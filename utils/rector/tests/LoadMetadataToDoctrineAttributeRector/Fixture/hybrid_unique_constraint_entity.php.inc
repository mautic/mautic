<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class HybridUniqueConstraintEntity
{
    public const TABLE_NAME = 'campaign_summary';

    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(SummaryRepository::class)
            ->addUniqueConstraint(['campaign_id', 'event_id', 'date_triggered'], 'campaign_event_date_triggered');
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: SummaryRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\UniqueConstraint(columns: ['campaign_id', 'event_id', 'date_triggered'], name: 'campaign_event_date_triggered')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class HybridUniqueConstraintEntity
{
    public const TABLE_NAME = 'campaign_summary';

    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;
}
