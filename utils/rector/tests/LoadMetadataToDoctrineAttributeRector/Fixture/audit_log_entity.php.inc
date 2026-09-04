<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class AuditLog
{
    protected $id;

    protected $userId;

    protected $userName;

    protected $bundle;

    protected $objectId;

    protected $details = [];

    protected $dateAdded;

    protected $ipAddress;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('audit_log')
            ->setCustomRepositoryClass(AuditLogRepository::class)
            ->addIndex(['object', 'object_id'], 'object_search')
            ->addIndex(['date_added'], 'date_added_index');

        $builder->addBigIntIdField();

        $builder->createField('userId', 'integer')
            ->columnName('user_id')
            ->build();

        $builder->createField('userName', 'string')
            ->columnName('user_name')
            ->build();

        $builder->createField('bundle', 'string')
            ->length(50)
            ->build();

        $builder->addBigIntIdField('objectId', 'object_id', false);

        $builder->createField('details', 'array')
            ->nullable()
            ->build();

        $builder->addDateAdded();

        $builder->createField('ipAddress', 'string')
            ->columnName('ip_address')
            ->length(45)
            ->build();
    }
}

-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(columns: ['object', 'object_id'], name: 'object_search')]
#[ORM\Index(columns: ['date_added'], name: 'date_added_index')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class AuditLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    protected $id;

    #[ORM\Column(name: 'user_id', type: 'integer')]
    protected $userId;

    #[ORM\Column(name: 'user_name', type: 'string', length: 191)]
    protected $userName;

    #[ORM\Column(type: 'string', length: 50)]
    protected $bundle;

    #[ORM\Column(name: 'object_id', type: 'bigint', options: ['unsigned' => true])]
    protected $objectId;

    #[ORM\Column(type: 'array', nullable: true)]
    protected $details = [];

    #[ORM\Column(name: 'date_added', type: 'datetime')]
    protected $dateAdded;

    #[ORM\Column(name: 'ip_address', type: 'string', length: 45)]
    protected $ipAddress;
}
