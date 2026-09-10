<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class HybridIndexWithOptionsEntity
{
    public const TABLE_NAME = 'page_hits';

    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(HitRepository::class)
            ->addIndex(['tracking_id'], 'page_hit_tracking_search')
            ->addIndexWithOptions(['url'], 'page_hit_url', ['lengths' => [0 => 128]]);
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: HitRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\Index(columns: ['tracking_id'], name: 'page_hit_tracking_search')]
#[ORM\Index(columns: ['url'], name: 'page_hit_url', options: ['lengths' => [0 => 128]])]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class HybridIndexWithOptionsEntity
{
    public const TABLE_NAME = 'page_hits';

    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;
}
