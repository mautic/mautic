<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use Mautic\CoreBundle\Shortener\Shortener;

/**
 * These services used to collect their tagged services through compiler passes and now use #[AutowireIterator].
 * The counts below are the ones the compiler passes produced, so a broken attribute is caught right away.
 */
final class TaggedServiceWiringSmokeTest extends AbstractContainerSmokeTestCase
{
    /**
     * Fewer services can be collected than there are tagged ones, as the setters key them by name.
     *
     * @var array<string, int>
     */
    private const EXPECTED_COLLECTED_SERVICE_COUNTS = [
        'mautic.email.stats.helper_container'                     => 6,
        'mautic.helper.update_checks'                             => 2,
        'mautic.integrations.helper'                              => 4,
        'mautic.integrations.helper.auth_integrations'            => 0,
        'mautic.integrations.helper.builder_integrations'         => 1,
        'mautic.integrations.helper.config_integrations'          => 4,
        'mautic.integrations.helper.sync_integrations'            => 0,
        'mautic.integrations.sync.notification.handler_container' => 2,
        'mautic.sms.callback_handler_container'                   => 1,
        'mautic.update.step_provider'                             => 7,
        Shortener::class                                          => 0,
    ];

    public function testTaggedServicesAreCollectedByTheConstructor(): void
    {
        $container = $this->buildContainer();

        $collectedServiceCounts = [];
        foreach (array_keys(self::EXPECTED_COLLECTED_SERVICE_COUNTS) as $serviceId) {
            $service = $container->get($serviceId);
            $collectedServiceCounts[$serviceId] = $this->countCollectedServices($service);
        }

        $this->assertSame(self::EXPECTED_COLLECTED_SERVICE_COUNTS, $collectedServiceCounts);
    }

    /**
     * The collected services are kept in array properties, sometimes grouped by more than one key.
     */
    private function countCollectedServices(object $service): int
    {
        $count = 0;

        foreach ((new \ReflectionObject($service))->getProperties() as $property) {
            if (!$property->isInitialized($service)) {
                continue;
            }

            $value = $property->getValue($service);
            if (is_array($value)) {
                $count += $this->countObjects($value);
            }
        }

        return $count;
    }

    /**
     * @param mixed[] $values
     */
    private function countObjects(array $values): int
    {
        $count = 0;

        foreach ($values as $value) {
            if (is_array($value)) {
                $count += $this->countObjects($value);
            } elseif (is_object($value)) {
                ++$count;
            }
        }

        return $count;
    }
}
