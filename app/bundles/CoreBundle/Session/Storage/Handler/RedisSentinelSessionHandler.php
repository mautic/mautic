<?php

namespace Mautic\CoreBundle\Session\Storage\Handler;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PRedisConnectionHelper;
use Predis\Client;
use Predis\Response\ErrorInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler;

class RedisSentinelSessionHandler extends AbstractSessionHandler
{
    /**
     * @var Client Redis client
     */
    private $redis;

    /**
     * @var array
     */
    private $redisConfiguration;

    /**
     * List of available options:.
     */
    public function __construct(array $redisConfiguration, CoreParametersHelper $coreParametersHelper)
    {
        $this->redisConfiguration = $redisConfiguration;

        $redisOptions = PRedisConnectionHelper::makeRedisOptions($redisConfiguration, 'session:'.$coreParametersHelper->get('db_name').':');

        $this->redis = new Client(PRedisConnectionHelper::getRedisEndpoints($redisConfiguration['url']), $redisOptions);
    }

    /**
     * {@inheritdoc}
     */
    protected function doRead($sessionId): string
    {
        return $this->redis->get($sessionId) ?: '';
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite($sessionId, $data): bool
    {
        $expireTime = isset($this->redisConfiguration['session_expire_time']) ? (int) $this->redisConfiguration['session_expire_time'] : 1209600;
        $result     = $this->redis->setEx($sessionId, $expireTime, $data);

        return $result && !$result instanceof ErrorInterface;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDestroy($sessionId): bool
    {
        $this->redis->del($sessionId);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function updateTimestamp($sessionId, $data)
    {
        $expireTime = isset($this->redisConfiguration['session_expire_time']) ? (int) $this->redisConfiguration['session_expire_time'] : 1209600;

        return (bool) $this->redis->expire($sessionId, $expireTime);
    }
}
