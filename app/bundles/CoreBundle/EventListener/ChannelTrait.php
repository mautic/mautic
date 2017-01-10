<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Factory\ModelFactory;

trait ChannelTrait
{
    /**
     * @var ModelFactory
     */
    protected $modelFactory;

    /**
     * @param ModelFactory $modelFactory
     */
    public function setModelFactory(ModelFactory $modelFactory)
    {
        $this->modelFactory = $modelFactory;
    }

    /**
     * Get the model for a channel.
     *
     * @param $channel
     *
     * @return mixed
     */
    protected function getChannelModel($channel)
    {
        if (null !== $this->modelFactory) {
            if ($this->modelFactory->hasModel($channel)) {
                return $this->modelFactory->getModel($channel);
            }
        } else {
            // BC - @deprecated - to be removed in 3.0
            try {
                return $this->factory->getModel($channel);
            } catch (\Exception $exception) {
                // No model found
            }
        }

        return false;
    }

    /**
     * Get the entity for a channel item.
     *
     * @param $channel
     * @param $channelId
     *
     * @return mixed
     */
    protected function getChannelEntity($channel, $channelId)
    {
        $channelEntity = null;
        if ($channelModel = $this->getChannelModel($channel)) {
            try {
                $channelEntity = $channelModel->getEntity($channelId);
            } catch (\Exception $exception) {
                // Not found
            }
        }

        return $channelEntity;
    }

    /**
     * Get the name and/or view URL for a channel entity.
     *
     * @param      $channel
     * @param      $channelId
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

                if ($this->router->getRouteCollection()->get($routeSourceName) !== null) {
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
