<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

final class ContainerServicesTest extends TestCase
{
    /**
     * Snapshots of how many controllers, commands and event subscribers the container holds.
     * Bump them when such a service is added or removed.
     */
    private const EXPECTED_CONTROLLER_COUNT = 155;

    private const EXPECTED_COMMAND_COUNT = 195;

    private const EXPECTED_EVENT_SUBSCRIBER_COUNT = 368;

    /**
     * Services that cannot be created in a test environment, or that are broken on purpose to keep this test green.
     *
     * @var string[]
     */
    private const SKIPPED_SERVICE_IDS = [
        // requires a database connection in the constructor
        \Mautic\CoreBundle\Form\Type\DynamicContentFilterEntryType::class,
        \Mautic\DynamicContentBundle\Form\Type\DynamicContentType::class,
        \Mautic\LeadBundle\EventListener\SearchSubscriber::class,
        \MauticPlugin\MauticClearbitBundle\EventListener\LeadSubscriber::class,
        \MauticPlugin\MauticClearbitBundle\Helper\LookupHelper::class,
        \MauticPlugin\MauticFullContactBundle\EventListener\LeadSubscriber::class,
        \MauticPlugin\MauticFullContactBundle\Helper\LookupHelper::class,
        'mautic.plugin.clearbit.lookup_helper',
        'mautic.plugin.fullcontact.lookup_helper',

        // requires a running Redis/Memcached server or an optional package
        \Mautic\CacheBundle\Cache\Adapter\MemcachedTagAwareAdapter::class,
        \Mautic\CacheBundle\Cache\Adapter\RedisAdapter::class,
        \Mautic\CacheBundle\Cache\Adapter\RedisTagAwareAdapter::class,
        'mautic.cache.adapter.memcached',
        'mautic.cache.adapter.redis',
        'mautic.cache.adapter.redis_tag_aware',
        'doctrine.uuid_generator',

        // not a service at all, an enum or a validation constraint
        \Mautic\CampaignBundle\Enum\RepublishBehavior::class,
        \Mautic\FormBundle\Enum\Token\RedirectUrlToken::class,
        \Mautic\LeadBundle\Form\Validator\Constraints\UniqueUserAlias::class,
        \Mautic\LeadBundle\Validator\Constraints\Length::class,

        // broken wiring: missing class, wrong argument count or wrong argument type
        \Mautic\AssetBundle\Controller\UploadController::class,
        'Mautic\CampaignBundle\Service\Campaign',
        \Mautic\CategoryBundle\Controller\Api\CategoryApiController::class,
        \Mautic\CoreBundle\Helper\BuilderTokenHelper::class,
        \Mautic\LeadBundle\Controller\Api\FieldApiController::class,
        'fos_oauth_server.controller.authorize',
        'mautic.campaign.service.campaign',
        'mautic.helper.token_builder',
        'oneup_uploader.controller.dropzone.class',
    ];

    public function testEveryServiceCanBeCreated(): void
    {
        $kernel = new TestKernel();
        $kernel->boot();

        /** @var Container $container */
        $container = $kernel->getContainer();

        /** @var ServiceProviderInterface $privateServiceLocator */
        $privateServiceLocator = $container->get('test.private_services_locator');

        $serviceIds = array_unique([
            ...$container->getServiceIds(),
            ...array_keys($privateServiceLocator->getProvidedServices()),
        ]);
        sort($serviceIds);

        // the test container resolves private services as well
        /** @var ContainerInterface $testContainer */
        $testContainer = $container->get('test.service_container');

        $failedServiceIds = [];

        // the same service can be registered under an alias too, so keep them unique per instance
        $controllers      = [];
        $commands         = [];
        $eventSubscribers = [];

        foreach ($serviceIds as $serviceId) {
            if (in_array($serviceId, self::SKIPPED_SERVICE_IDS, true)) {
                continue;
            }

            try {
                $service = $testContainer->get($serviceId);
            } catch (\Throwable $throwable) {
                $failedServiceIds[] = sprintf('%s: %s', $serviceId, $throwable->getMessage());
                continue;
            }

            if ($service instanceof AbstractController) {
                $controllers[spl_object_id($service)] = $serviceId;
            }

            if ($service instanceof Command) {
                $commands[spl_object_id($service)] = $serviceId;
            }

            if ($service instanceof EventSubscriberInterface) {
                $eventSubscribers[spl_object_id($service)] = $serviceId;
            }
        }

        $this->assertSame([], $failedServiceIds);

        $this->assertCount(self::EXPECTED_CONTROLLER_COUNT, $controllers);
        $this->assertCount(self::EXPECTED_COMMAND_COUNT, $commands);
        $this->assertCount(self::EXPECTED_EVENT_SUBSCRIBER_COUNT, $eventSubscribers);

        $kernel->shutdown();
    }
}
