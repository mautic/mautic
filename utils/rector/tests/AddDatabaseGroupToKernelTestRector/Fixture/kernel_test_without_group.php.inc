<?php

namespace Utils\Rector\Tests\AddDatabaseGroupToKernelTestRector\Fixture;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class KernelTestWithoutGroup extends KernelTestCase
{
    public function testSomething()
    {
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\AddDatabaseGroupToKernelTestRector\Fixture;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('database')]
final class KernelTestWithoutGroup extends KernelTestCase
{
    public function testSomething()
    {
    }
}

?>
