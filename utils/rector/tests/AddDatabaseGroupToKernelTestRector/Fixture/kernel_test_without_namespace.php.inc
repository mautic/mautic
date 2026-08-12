<?php

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class KernelTestWithoutNamespace extends KernelTestCase
{
    public function testSomething()
    {
    }
}

?>
-----
<?php

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[\PHPUnit\Framework\Attributes\Group('database')]
final class KernelTestWithoutNamespace extends KernelTestCase
{
    public function testSomething()
    {
    }
}

?>
