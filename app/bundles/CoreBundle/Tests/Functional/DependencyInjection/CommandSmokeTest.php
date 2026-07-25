<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use Symfony\Component\Console\Command\Command;

final class CommandSmokeTest extends AbstractContainerSmokeTestCase
{
    /**
     * There are 195 commands in the container, keep a small reserve for removed ones.
     */
    private const MINIMAL_COMMAND_COUNT = 192;

    public function testAllCommandsCanBeCreated(): void
    {
        $commands = array_filter(
            $this->createAllServices(),
            static fn (object $service): bool => $service instanceof Command
        );

        $this->assertGreaterThanOrEqual(self::MINIMAL_COMMAND_COUNT, count($commands));
    }
}
