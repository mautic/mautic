<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class WideEntity
{
    public const TABLE_NAME = 'wide_entity';

    protected $id;

    protected $title;

    protected $description;

    protected $system;

    protected $source;

    protected $publishUp;

    protected $publishDown;

    protected $scheduleUnit;

    protected $uuid;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(WideRepository::class);

        $builder->addIdColumns('title');

        $builder->addField('system', Types::BOOLEAN, ['columnName' => '`system`']);

        $builder->addField('source', Types::STRING);

        $builder->addPublishDates();

        $builder->addNullableField('scheduleUnit', Types::STRING, 'schedule_unit');

        static::addUuidField($builder);
    }

    public static function addUuidField(ClassMetadataBuilder $builder): void
    {
        $builder->createField('uuid', Types::GUID)
            ->nullable()
            ->build();
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: WideRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class WideEntity
{
    public const TABLE_NAME = 'wide_entity';

    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    protected $id;

    #[ORM\Column(type: 'string', length: 191)]
    protected $title;

    #[ORM\Column(type: 'text', nullable: true)]
    protected $description;

    #[ORM\Column(name: '`system`', type: Types::BOOLEAN)]
    protected $system;

    #[ORM\Column(type: Types::STRING, length: 191)]
    protected $source;

    #[ORM\Column(name: 'publish_up', type: 'datetime', nullable: true)]
    protected $publishUp;

    #[ORM\Column(name: 'publish_down', type: 'datetime', nullable: true)]
    protected $publishDown;

    #[ORM\Column(name: 'schedule_unit', type: Types::STRING, length: 191, nullable: true)]
    protected $scheduleUnit;

    #[ORM\Column(type: 'guid', nullable: true)]
    protected $uuid;

    public static function addUuidField(ClassMetadataBuilder $builder): void
    {
        $builder->createField('uuid', Types::GUID)
            ->nullable()
            ->build();
    }
}
