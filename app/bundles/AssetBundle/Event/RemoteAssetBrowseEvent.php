<?php

namespace Mautic\AssetBundle\Event;

use Gaufrette\Adapter;
use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

class RemoteAssetBrowseEvent extends CommonEvent
{
    private ?Adapter $adapter = null;

    public function __construct(
        private UnifiedIntegrationInterface $integration,
    ) {
    }

    public function getAdapter(): ?Adapter
    {
        return $this->adapter;
    }

    public function getIntegration(): UnifiedIntegrationInterface
    {
        return $this->integration;
    }

    public function setAdapter(Adapter $adapter): void
    {
        $this->adapter = $adapter;
    }
}
