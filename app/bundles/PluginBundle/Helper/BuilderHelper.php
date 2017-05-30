<?php

namespace Mautic\PluginBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;

class BuilderHelper
{
    private $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    public function getBuilderPlugins()
    {
        $builderPlugins = [];
        $plugins        = $this->factory->getPluginBundles();

        foreach ($plugins as $plugin) {
            $config = $this->factory->getBundleConfig($plugin['bundle'], '', true);
            $type   = !empty($config['type']) ? $config['type'] : null;

            if ($type === 'builder') {
                $builderPlugins[$plugin['bundle']] = $plugin['config'];
            }
        }

        return $builderPlugins;
    }
}
