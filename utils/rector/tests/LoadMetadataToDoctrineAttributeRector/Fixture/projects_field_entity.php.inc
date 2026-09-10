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
    #[ORM\ManyToMany(targetEntity: \Mautic\ProjectBundle\Entity\Project::class, cascade: ['merge', 'persist', 'detach'], fetch: 'LAZY', indexBy: 'name')]
    #[ORM\JoinTable(name: 'proj_entity_projects_xref')]
    #[ORM\JoinColumn(name: 'proj_entity_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'project_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private \Doctrine\Common\Collections\Collection $projects;

    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    protected $id;
}
