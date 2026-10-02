<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRules\Fixture;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Doctrine\Type\ArrayType;

#[ORM\Entity]
#[ORM\Table(name: 'column_const_type')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class ColumnConstTypeAndOptionsEntity
{
    private $properties;

    private $payload;

    private $continueSending;

    private $type;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->createField('properties', ArrayType::ARRAY)->nullable()->build();

        $builder->createField('payload', Types::BLOB)
            ->nullable()
            ->length(MySQLPlatform::LENGTH_LIMIT_MEDIUMBLOB)
            ->build();

        $builder->addField('continueSending', Types::BOOLEAN, [
            'columnName' => 'continue_sending',
            'nullable'   => false,
            'options'    => [
                'default' => false,
            ],
        ]);

        $builder->addField('type', Types::STRING, [
            'length'  => 10,
            'default' => 'html',
        ]);
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRules\Fixture;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Doctrine\Type\ArrayType;

#[ORM\Entity]
#[ORM\Table(name: 'column_const_type')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class ColumnConstTypeAndOptionsEntity
{
    #[ORM\Column(type: ArrayType::ARRAY, nullable: true)]
    private $properties;

    #[ORM\Column(type: Types::BLOB, length: MySQLPlatform::LENGTH_LIMIT_MEDIUMBLOB, nullable: true)]
    private $payload;

    #[ORM\Column(name: 'continue_sending', type: Types::BOOLEAN, options: ['default' => false])]
    private $continueSending;

    #[ORM\Column(type: Types::STRING, length: 191, options: ['default' => 'html'])]
    private $type;
}

?>
