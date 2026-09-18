<?php

namespace Utils\Rector\Tests\AddDoctrineOrmMappingAliasImportRector\Fixture;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class SkipAliasAlreadyImported
{
    #[ORM\Column(type: 'string', length: 191)]
    private $name;
}
