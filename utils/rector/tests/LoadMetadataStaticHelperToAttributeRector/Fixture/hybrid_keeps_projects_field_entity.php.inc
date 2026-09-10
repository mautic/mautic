<?php

namespace Utils\Rector\Tests\LoadMetadataStaticHelperToAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\ProjectBundle\Entity\ProjectTrait;

#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class LeadEventLog
{
    use ProjectTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        self::addVersionField($builder);
        self::addProjectsField($builder, 'lead_event_log_projects_xref', 'lead_event_log_id');
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataStaticHelperToAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\ProjectBundle\Entity\ProjectTrait;

#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class LeadEventLog
{
    use ProjectTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        self::addProjectsField($builder, 'lead_event_log_projects_xref', 'lead_event_log_id');
    }
}
