<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use Symfony\Component\Console\Command\Command;

final class CommandSmokeTest extends AbstractContainerSmokeTestCase
{
    /**
     * There are 63 local commands in the container, keep a small reserve for removed ones.
     */
    private const int MINIMAL_COMMAND_COUNT = 60;

    public function testAllCommandsCanBeCreated(): void
    {
        $commands = array_filter(
            $this->createAllServices(),
            fn (object $service): bool => $service instanceof Command && $this->isLocalService($service)
        );

        $this->assertGreaterThanOrEqual(self::MINIMAL_COMMAND_COUNT, count($commands));
    }
}
