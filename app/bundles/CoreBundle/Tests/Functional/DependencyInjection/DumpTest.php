<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DumpTest extends AbstractContainerSmokeTestCase
{
    public function testDump(): void
    {
        $counts = [];
        foreach ($this->createAllServices() as $service) {
            if (!$service instanceof EventSubscriberInterface || !$this->isLocalService($service)) {
                continue;
            }
            foreach ($service::getSubscribedEvents() as $eventName => $unused) {
                $counts[$eventName] = ($counts[$eventName] ?? 0) + 1;
            }
        }
        arsort($counts);
        file_put_contents('/tmp/claude-1000/-var-www-mautic/50c906dd-ca39-4270-b858-89e289725378/scratchpad/counts.json', json_encode($counts, JSON_PRETTY_PRINT));
        $this->assertTrue(true);
    }
}
