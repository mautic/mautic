<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\SAML\Store\Request;

use LightSaml\State\Request\RequestState;
use LightSaml\Store\Request\AbstractRequestStateArrayStore;
use Mautic\CacheBundle\Cache\CacheProviderInterface;

class RequestStateStore extends AbstractRequestStateArrayStore
{
    private string $prefix;

    public function __construct(private CacheProviderInterface $cacheProvider, string $prefix, string $suffix)
    {
        $this->prefix = $prefix.$suffix;
    }

    public function set(RequestState $state): self
    {
        $id = $state->getId();

        $item = $this->cacheProvider->getItem($this->prefix.$id);
        $item->expiresAfter(2 * 60); // The login is valid for 2 minutes.

        $item->set($state);
        $this->cacheProvider->save($item);

        return $this;
    }

    public function get($id): ?RequestState
    {
        $item = $this->cacheProvider->getItem($this->prefix.$id);

        if (!$item->isHit()) {
            return null;
        }

        $state = $item->get();
        if (!$state instanceof RequestState) {
            return null;
        }

        return $state;
    }

    public function remove($id): bool
    {
        return $this->cacheProvider->deleteItem($this->prefix.$id);
    }

    public function clear(): void
    {
        $this->cacheProvider->clear($this->prefix);
    }

    /**
     * @return array<mixed>
     */
    protected function getArray(): array
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * @param array<mixed> $arr
     */
    protected function setArray(array $arr): AbstractRequestStateArrayStore
    {
        throw new \LogicException('Not implemented');
    }
}
