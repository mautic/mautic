<?php

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Factory\ModelFactory;

trait ChannelTrait
{
    /**
     * @var ModelFactory<object>
     */
    protected $modelFactory;

    /**
     * @param ModelFactory<object> $modelFactory
     */
    public function setModelFactory(ModelFactory $modelFactory): void
    {
        $this->modelFactory = $modelFactory;
    }

    /**
     * Get the model for a channel.
     *
     * @return mixed
     */
    protected function getChannelModel($channel)
    {
        if ($this->modelFactory->hasModel($channel)) {
            return $this->modelFactory->getModel($channel);
        }

        return false;
    }

    /**
     * Get the entity for a channel item.
     *
     * @return mixed
     */
    protected function getChannelEntity($channel, $channelId)
    {
        $channelEntity = null;
        if ($channelModel = $this->getChannelModel($channel)) {
            try {
                $channelEntity = $channelModel->getEntity($channelId);
            } catch (\Exception) {
                // Not found
            }
        }

        return $channelEntity;
    }

    /**
     * Get the name and/or view URL for a channel entity.
     *
     * @param bool $returnWithViewUrl
     *
     * @return array|bool|string
     */
    protected function getChannelEntityName($channel, $channelId, $returnWithViewUrl = false)
    {
        if ($channelEntity = $this->getChannelEntity($channel, $channelId)) {
            $channelModel = $this->getChannelModel($channel);
            $name         = false;
            if (method_exists($channelEntity, $channelModel->getNameGetter())) {
                $name = $channelEntity->{$channelModel->getNameGetter()}();
            }

            if ($name && $returnWithViewUrl) {
                $url           = null;
                $baseRouteName = str_replace('.', '_', $channel);
                if (method_exists($channelModel, 'getActionRouteBase')) {
                    $baseRouteName = $channelModel->getActionRouteBase();
                }
                $routeSourceName = 'mautic_'.$baseRouteName.'_action';

                if (null !== $this->router->getRouteCollection()->get($routeSourceName)) {
                    $url = $this->router->generate(
                        $routeSourceName,
                        [
                            'objectAction' => 'view',
                            'objectId'     => $channelId,
                        ]
                    );
                }

                return [
                    'name' => $name,
                    'url'  => $url,
                ];
            }

            return $name;
        }

        return false;
    }
}
