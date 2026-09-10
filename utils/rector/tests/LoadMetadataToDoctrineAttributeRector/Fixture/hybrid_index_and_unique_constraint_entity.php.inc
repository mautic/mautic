<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class LeadEventLog
{
    public const TABLE_NAME = 'campaign_lead_event_log';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(LeadEventLogRepository::class)
            ->addIndex(['date_triggered'], 'campaign_date_triggered')
            ->addUniqueConstraint(['event_id', 'lead_id', 'rotation'], 'campaign_rotation');

        self::addVersionField($builder);
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: LeadEventLogRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\Index(columns: ['date_triggered'], name: 'campaign_date_triggered')]
#[ORM\UniqueConstraint(columns: ['event_id', 'lead_id', 'rotation'], name: 'campaign_rotation')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class LeadEventLog
{
    public const TABLE_NAME = 'campaign_lead_event_log';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;
}
