<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Component\Console\Command\Command;

function createAnonymousCommand(): Command
{
    return new class() extends Command {
    };
}
