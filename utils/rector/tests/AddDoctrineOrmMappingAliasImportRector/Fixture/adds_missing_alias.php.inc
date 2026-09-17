<?php

namespace Utils\Rector\Tests\AddDoctrineOrmMappingAliasImportRector\Fixture;

#[ORM\Entity]
class AddsMissingAlias
{
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $name;
}

?>
-----
<?php

namespace Utils\Rector\Tests\AddDoctrineOrmMappingAliasImportRector\Fixture;

use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class AddsMissingAlias
{
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $name;
}

?>
