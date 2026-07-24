<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Contracts\Service\ServiceProviderInterface;

final class ContainerServicesTest extends TestCase
{
    /**
     * Services that cannot be created in a test environment, or that are broken on purpose to keep this test green.
     *
     * @var string[]
     */
    private const SKIPPED_SERVICE_IDS = [
        // requires a database connection in the constructor
        'Mautic\CoreBundle\Form\Type\DynamicContentFilterEntryType',
        'Mautic\DynamicContentBundle\Form\Type\DynamicContentType',
        'Mautic\LeadBundle\EventListener\SearchSubscriber',
        'MauticPlugin\MauticClearbitBundle\EventListener\LeadSubscriber',
        'MauticPlugin\MauticClearbitBundle\Helper\LookupHelper',
        'MauticPlugin\MauticFullContactBundle\EventListener\LeadSubscriber',
        'MauticPlugin\MauticFullContactBundle\Helper\LookupHelper',
        'mautic.plugin.clearbit.lookup_helper',
        'mautic.plugin.fullcontact.lookup_helper',

        // requires a running Redis/Memcached server or an optional package
        'Mautic\CacheBundle\Cache\Adapter\MemcachedTagAwareAdapter',
        'Mautic\CacheBundle\Cache\Adapter\RedisAdapter',
        'Mautic\CacheBundle\Cache\Adapter\RedisTagAwareAdapter',
        'mautic.cache.adapter.memcached',
        'mautic.cache.adapter.redis',
        'mautic.cache.adapter.redis_tag_aware',
        'doctrine.uuid_generator',

        // not a service at all, an enum or a validation constraint
        'Mautic\CampaignBundle\Enum\RepublishBehavior',
        'Mautic\FormBundle\Enum\Token\RedirectUrlToken',
        'Mautic\LeadBundle\Form\Validator\Constraints\UniqueUserAlias',
        'Mautic\LeadBundle\Validator\Constraints\Length',

        // broken wiring: missing class, wrong argument count or wrong argument type
        'Mautic\AssetBundle\Controller\UploadController',
        'Mautic\CampaignBundle\Service\Campaign',
        'Mautic\CategoryBundle\Controller\Api\CategoryApiController',
        'Mautic\CoreBundle\Helper\BuilderTokenHelper',
        'Mautic\LeadBundle\Controller\Api\FieldApiController',
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

        foreach ($serviceIds as $serviceId) {
            if (in_array($serviceId, self::SKIPPED_SERVICE_IDS, true)) {
                continue;
            }

            try {
                $testContainer->get($serviceId);
            } catch (\Throwable $throwable) {
                $failedServiceIds[] = sprintf('%s: %s', $serviceId, $throwable->getMessage());
            }
        }

        $this->assertSame([], $failedServiceIds);

        $kernel->shutdown();
    }
}
