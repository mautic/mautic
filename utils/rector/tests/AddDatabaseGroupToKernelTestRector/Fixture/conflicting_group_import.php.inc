<?php

namespace Utils\Rector\Tests\AddDatabaseGroupToKernelTestRector\Fixture;

use Mautic\LeadBundle\Entity\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ConflictingGroupImport extends KernelTestCase
{
    public function testSomething()
    {
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\AddDatabaseGroupToKernelTestRector\Fixture;

use Mautic\LeadBundle\Entity\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[\PHPUnit\Framework\Attributes\Group('database')]
final class ConflictingGroupImport extends KernelTestCase
{
    public function testSomething()
    {
    }
}

?>
