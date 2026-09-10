<?php

namespace Utils\Rector\Tests\LoadMetadataStaticHelperToAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\ProjectBundle\Entity\ProjectTrait;

class PageEntity
{
    use ProjectTrait;

    protected $id;

    protected $title;

    protected $description;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('pages')
            ->setCustomRepositoryClass(PageRepository::class);

        $builder->addIdColumns('title');

        self::addTranslationMetadata($builder, self::class);
        self::addVariantMetadata($builder, self::class);
        static::addUuidField($builder);
        self::addProjectsField($builder, 'page_projects_xref', 'page_id');
        self::addVersionField($builder);
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataStaticHelperToAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\ProjectBundle\Entity\ProjectTrait;

class PageEntity
{
    use ProjectTrait;
    #[ORM\ManyToMany(targetEntity: \Mautic\ProjectBundle\Entity\Project::class, cascade: ['merge', 'persist', 'detach'], fetch: 'LAZY', indexBy: 'name')]
    #[ORM\JoinTable(name: 'page_projects_xref')]
    #[ORM\JoinColumn(name: 'page_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'project_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private \Doctrine\Common\Collections\Collection $projects;

    protected $id;

    protected $title;

    protected $description;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('pages')
            ->setCustomRepositoryClass(PageRepository::class);

        $builder->addIdColumns('title');
    }
}
