<?php

namespace Utils\Rector\Tests\LoadMetadataToDoctrineAttributeRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class SkipUnsupportedHelper
{
    protected $id;

    protected $name;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('some_table');

        $builder->addId();

        $builder->createManyToMany('items', SomeTarget::class)
            ->setJoinTable('some_join')
            ->addJoinColumn('some_id', 'id')
            ->addInverseJoinColumn('target_id', 'id')
            ->build();

        $builder->createField('name', 'string')
            ->build();
    }
}
