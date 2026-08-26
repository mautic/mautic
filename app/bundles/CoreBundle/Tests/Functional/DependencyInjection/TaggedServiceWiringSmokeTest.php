<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use Mautic\CoreBundle\Helper\PreUpdateCheckHelper;
use Mautic\CoreBundle\Shortener\Shortener;
use Mautic\CoreBundle\Update\StepProvider;
use Mautic\IntegrationsBundle\Helper\AuthIntegrationsHelper;
use Mautic\IntegrationsBundle\Helper\BuilderIntegrationsHelper;
use Mautic\IntegrationsBundle\Helper\ConfigIntegrationsHelper;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\IntegrationsBundle\Helper\SyncIntegrationsHelper;
use Mautic\IntegrationsBundle\Sync\Notification\Handler\HandlerContainer as IntegrationsNotificationHandlerContainer;
use Mautic\SmsBundle\Callback\HandlerContainer as SmsCallbackHandlerContainer;

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
    private const array EXPECTED_COLLECTED_SERVICE_COUNTS = [
        'mautic.email.stats.helper_container'         => 6,
        PreUpdateCheckHelper::class                   => 2,
        IntegrationsHelper::class                     => 4,
        AuthIntegrationsHelper::class                 => 0,
        BuilderIntegrationsHelper::class              => 1,
        ConfigIntegrationsHelper::class               => 4,
        SyncIntegrationsHelper::class                 => 0,
        IntegrationsNotificationHandlerContainer::class => 2,
        SmsCallbackHandlerContainer::class            => 1,
        StepProvider::class                           => 7,
        Shortener::class                              => 0,
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

        foreach (new \ReflectionObject($service)->getProperties() as $property) {
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
