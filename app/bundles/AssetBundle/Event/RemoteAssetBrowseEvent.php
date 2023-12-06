<?php

namespace Mautic\AssetBundle\Event;

use Gaufrette\Adapter;
use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

class RemoteAssetBrowseEvent extends CommonEvent
{
    private ?\Gaufrette\Adapter $adapter = null;

    /**
     * @var AbstractIntegration
     */
    private \Mautic\PluginBundle\Integration\UnifiedIntegrationInterface $integration;

    public function __construct(UnifiedIntegrationInterface $integration)
    {
        $this->integration = $integration;
    }

    /**
     * @return Adapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @return AbstractIntegration
     */
    public function getIntegration()
    {
        return $this->integration;
    }

    /**
     * @return $this
     */
    public function setAdapter(Adapter $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }
}
