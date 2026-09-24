<?php

namespace Utils\Rector\Tests\TestGetRepositoryToContainerGetRector\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Utils\Rector\Tests\TestGetRepositoryToContainerGetRector\Source\SomeEntity;

final class ConvertsTestCaseGetRepository extends KernelTestCase
{
    private EntityManagerInterface $em;

    public function testThing(): void
    {
        $repository = $this->em->getRepository(SomeEntity::class);
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\TestGetRepositoryToContainerGetRector\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Utils\Rector\Tests\TestGetRepositoryToContainerGetRector\Source\SomeEntity;

final class ConvertsTestCaseGetRepository extends KernelTestCase
{
    private EntityManagerInterface $em;

    public function testThing(): void
    {
        $repository = $this->getContainer()->get(\Utils\Rector\Tests\TestGetRepositoryToContainerGetRector\Source\SomeRepository::class);
    }
}

?>
