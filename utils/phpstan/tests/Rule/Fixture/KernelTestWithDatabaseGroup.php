<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('database')]
final class KernelTestWithDatabaseGroup extends KernelTestCase
{
}
