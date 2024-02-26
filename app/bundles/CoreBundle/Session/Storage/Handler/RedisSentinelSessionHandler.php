<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Session\Storage\Handler;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PRedisConnectionHelper;
use Predis\Client;
use Predis\Response\ErrorInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler;

/**
 * @deprecated since Mautic 5.0, to be removed in 6.0 with no replacement.
 */
class RedisSentinelSessionHandler extends AbstractSessionHandler
{
    /**
     * @var Client Redis client
     */
    private \Predis\Client $redis;

    public function __construct(
        private array $redisConfiguration,
        CoreParametersHelper $coreParametersHelper
    ) {
        $redisOptions = PRedisConnectionHelper::makeRedisOptions($redisConfiguration, 'session:'.$coreParametersHelper->get('db_name').':');

        $redisOptions['primaryOnly'] = $coreParametersHelper->get('redis_primary_only');

        $this->redis = PRedisConnectionHelper::createClient(PRedisConnectionHelper::getRedisEndpoints($redisConfiguration['url']), $redisOptions);
    }

    protected function doRead(string $sessionId): string
    {
        return $this->redis->get($sessionId) ?: '';
    }

    protected function doWrite(string $sessionId, string $data): bool
    {
        $expireTime = isset($this->redisConfiguration['session_expire_time']) ? (int) $this->redisConfiguration['session_expire_time'] : 1_209_600;
        $result     = $this->redis->setEx($sessionId, $expireTime, $data);

        return $result && !$result instanceof ErrorInterface;
    }

    protected function doDestroy(string $sessionId): bool
    {
        $this->redis->del($sessionId);

        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function gc($maxlifetime): bool
    {
        return true;
    }

    public function updateTimestamp($sessionId, $data): bool
    {
        $expireTime = isset($this->redisConfiguration['session_expire_time']) ? (int) $this->redisConfiguration['session_expire_time'] : 1_209_600;

        return (bool) $this->redis->expire($sessionId, $expireTime);
    }
}
