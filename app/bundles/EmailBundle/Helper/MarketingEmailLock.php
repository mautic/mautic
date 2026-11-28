<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\RedisStore;

/**
 * Serializes the duplicate-send check for one contact and one marketing email.
 *
 * The lock is deliberately contact-scoped so campaign workers can continue to
 * process different contacts in parallel. The same guard is used by batch and
 * real-time campaign execution paths.
 */
class MarketingEmailLock
{
    private readonly LockFactory $factory;

    public function __construct(
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
    ) {
        $this->factory = new LockFactory($this->createStore($pathsHelper, $coreParametersHelper));
    }

    /**
     * @param array<int, int> $emailIds
     *
     * @return array<int, LockInterface>|null
     */
    public function acquire(array $emailIds, int $contactId): ?array
    {
        $emailIds = array_values(array_unique(array_map('intval', $emailIds)));
        sort($emailIds, SORT_NUMERIC);

        $locks = [];
        foreach ($emailIds as $emailId) {
            if ($emailId <= 0) {
                continue;
            }

            // Keep the Redis lock for the same default lifetime as the
            // campaign command lock. File locks remain held until release.
            $lock = $this->factory->createLock(
                sprintf('campaign-email-%d-contact-%d', $emailId, $contactId),
                null
            );
            if ($lock->acquire()) {
                $locks[] = $lock;
                continue;
            }

            $this->release($locks);

            return null;
        }

        return $locks;
    }

    /**
     * @param array<int, LockInterface> $locks
     */
    public function release(array $locks): void
    {
        foreach (array_reverse($locks) as $lock) {
            $lock->release();
        }
    }

    private function createStore(PathsHelper $pathsHelper, CoreParametersHelper $coreParametersHelper): FlockStore|RedisStore
    {
        $cacheAdapter = $coreParametersHelper->get('cache_adapter');
        if (in_array($cacheAdapter, ['mautic.cache.adapter.redis', 'mautic.cache.adapter.redis_tag_aware'], true)) {
            $cacheAdapterConfig = $coreParametersHelper->get('cache_adapter_redis');
            $redis              = RedisAdapter::createConnection(
                $cacheAdapterConfig['dsn'] ?? null,
                $cacheAdapterConfig['options'] ?? []
            );

            return new RedisStore($redis, 3600);
        }

        $cacheDirectory = $pathsHelper->getSystemPath('cache');
        if (!is_dir($cacheDirectory) && !@mkdir($cacheDirectory, 0775, true) && !is_dir($cacheDirectory)) {
            throw new \RuntimeException($cacheDirectory.' could not be created.');
        }

        $runDirectory = $cacheDirectory.'/../run';
        if (!is_dir($runDirectory) && !@mkdir($runDirectory, 0775, true) && !is_dir($runDirectory)) {
            throw new \RuntimeException($runDirectory.' could not be created.');
        }

        return new FlockStore($runDirectory);
    }
}
