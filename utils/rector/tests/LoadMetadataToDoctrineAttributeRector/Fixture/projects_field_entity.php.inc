<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\ProjectBundle\Entity\ProjectTrait;

class ProjEntity
{
    use ProjectTrait;

    protected $id;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('proj_entity');

        $builder->addId();

        self::addProjectsField($builder, 'proj_entity_projects_xref', 'proj_entity_id');
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\ProjectBundle\Entity\ProjectTrait;

#[ORM\Entity]
#[ORM\Table(name: 'proj_entity')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class ProjEntity
{
    use ProjectTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    protected $id;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        self::addProjectsField($builder, 'proj_entity_projects_xref', 'proj_entity_id');
    }
}
