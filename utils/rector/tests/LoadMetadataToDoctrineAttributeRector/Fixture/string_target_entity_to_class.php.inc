<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class StringTargetEntity
{
    private $id;

    private $tweet;

    private $stat;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('string_target');
        $builder->addId();

        $builder->createManyToOne('tweet', 'Tweet')
            ->inversedBy('stats')
            ->build();

        $builder->createManyToOne('stat', 'Mautic\SocialBundle\Entity\Stat')
            ->build();
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity]
#[ORM\Table(name: 'string_target')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class StringTargetEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: Tweet::class, inversedBy: 'stats')]
    private $tweet;

    #[ORM\ManyToOne(targetEntity: \Mautic\SocialBundle\Entity\Stat::class)]
    private $stat;
}
