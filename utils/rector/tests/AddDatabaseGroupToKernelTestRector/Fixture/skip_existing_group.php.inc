<?php

namespace Utils\Rector\Tests\AddDatabaseGroupToKernelTestRector\Fixture;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('database')]
final class SkipExistingGroup extends KernelTestCase
{
    public function testSomething()
    {
    }
}
