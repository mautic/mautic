<?php

namespace Utils\Rector\Tests\GetRepositoryToRepositoryServiceRector\Fixture;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Hit;

final class TestCaseUsesContainer extends MauticMysqlTestCase
{
    public function testSomething(): void
    {
        $hitRepository = $this->em->getRepository(Hit::class);
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\GetRepositoryToRepositoryServiceRector\Fixture;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Hit;

final class TestCaseUsesContainer extends MauticMysqlTestCase
{
    public function testSomething(): void
    {
        $hitRepository = self::getContainer()->get(\Mautic\PageBundle\Entity\HitRepository::class);
    }
}

?>
