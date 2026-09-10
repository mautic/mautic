<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class FormEntity
{
    private bool $isPublished = true;

    private $dateAdded;

    private $createdBy;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setMappedSuperClass();

        $builder->createField('isPublished', 'boolean')
            ->columnName('is_published')
            ->build();

        $builder->addDateAdded(true);

        $builder->createField('createdBy', 'integer')
            ->columnName('created_by')
            ->nullable()
            ->build();
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\MappedSuperclass]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class FormEntity
{
    #[ORM\Column(name: 'is_published', type: 'boolean')]
    private bool $isPublished = true;

    #[ORM\Column(name: 'date_added', type: 'datetime', nullable: true)]
    private $dateAdded;

    #[ORM\Column(name: 'created_by', type: 'integer', nullable: true)]
    private $createdBy;
}
