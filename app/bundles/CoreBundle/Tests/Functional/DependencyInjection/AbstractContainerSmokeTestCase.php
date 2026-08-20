<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use Mautic\AssetBundle\Controller\UploadController;
use Mautic\CacheBundle\Cache\Adapter\MemcachedTagAwareAdapter;
use Mautic\CacheBundle\Cache\Adapter\RedisAdapter;
use Mautic\CacheBundle\Cache\Adapter\RedisTagAwareAdapter;
use Mautic\CampaignBundle\Enum\RepublishBehavior;
use Mautic\CategoryBundle\Controller\Api\CategoryApiController;
use Mautic\CoreBundle\Form\Type\DynamicContentFilterEntryType;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\DynamicContentBundle\Form\Type\DynamicContentType;
use Mautic\FormBundle\Enum\Token\RedirectUrlToken;
use Mautic\LeadBundle\Controller\Api\FieldApiController;
use Mautic\LeadBundle\EventListener\SearchSubscriber;
use Mautic\LeadBundle\Form\Validator\Constraints\UniqueUserAlias;
use Mautic\LeadBundle\Validator\Constraints\Length;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Contracts\Service\ServiceProviderInterface;

abstract class AbstractContainerSmokeTestCase extends TestCase
{
    /**
     * Services that cannot be created in a test environment, or that are broken on purpose to keep these tests green.
     *
     * @var string[]
     */
    private const array SKIPPED_SERVICE_IDS = [
        // requires a database connection in the constructor
        DynamicContentFilterEntryType::class,
        DynamicContentType::class,
        SearchSubscriber::class,
        \MauticPlugin\MauticClearbitBundle\EventListener\LeadSubscriber::class,
        \MauticPlugin\MauticClearbitBundle\Helper\LookupHelper::class,
        \MauticPlugin\MauticFullContactBundle\EventListener\LeadSubscriber::class,
        \MauticPlugin\MauticFullContactBundle\Helper\LookupHelper::class,
        'mautic.plugin.clearbit.lookup_helper',
        'mautic.plugin.fullcontact.lookup_helper',

        // requires a running Redis/Memcached server or an optional package
        MemcachedTagAwareAdapter::class,
        RedisAdapter::class,
        RedisTagAwareAdapter::class,
        'mautic.cache.adapter.redis',
        'mautic.cache.adapter.redis_tag_aware',
        'doctrine.uuid_generator',

        // not a service at all, an enum or a validation constraint
        RepublishBehavior::class,
        RedirectUrlToken::class,
        UniqueUserAlias::class,
        Length::class,

        // broken wiring: missing class, wrong argument count or wrong argument type
        UploadController::class,
        'Mautic\CampaignBundle\Service\Campaign',
        CategoryApiController::class,
        BuilderTokenHelper::class,
        FieldApiController::class,
        'fos_oauth_server.controller.authorize',
        'mautic.helper.token_builder',
    ];

    /**
     * @return string[]
     */
    private function resolveServiceIds(Container $container): array
    {
        /** @var ServiceProviderInterface $privateServiceLocator */
        $privateServiceLocator = $container->get('test.private_services_locator');

        $serviceIds = array_unique([
            ...$container->getServiceIds(),
            ...array_keys($privateServiceLocator->getProvidedServices()),
        ]);

        sort($serviceIds);

        return $serviceIds;
    }

    protected function buildContainer(): Container
    {
        $kernel = new TestKernel();
        $kernel->boot();

        /** @var Container $container */
        $container = $kernel->getContainer();

        return $container;
    }

    /**
     * Creates every service in the container, unique per instance, as the same service can be registered under an alias too.
     *
     * @return array<int, object>
     */
    protected function createAllServices(): array
    {
        $container = $this->buildContainer();
        $serviceIds = $this->resolveServiceIds($container);

        // the test container resolves private services as well
        /** @var ContainerInterface $testContainer */
        $testContainer = $container->get('test.service_container');

        $services         = [];
        $failedServiceIds = [];

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

            if (is_object($service)) {
                $services[spl_object_id($service)] = $service;
            }
        }

        $this->assertSame([], $failedServiceIds);

        return $services;
    }

    /**
     * Vendor services are out of scope, only the Mautic ones are.
     */
    protected function isLocalService(object $service): bool
    {
        return str_starts_with($service::class, 'Mautic\\') || str_starts_with($service::class, 'MauticPlugin\\');
    }
}
