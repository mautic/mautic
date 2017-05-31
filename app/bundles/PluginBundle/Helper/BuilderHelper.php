<?php

namespace Mautic\PluginBundle\Helper;

use Mautic\PluginBundle\Model\PluginModel;

class BuilderHelper
{
    private $pluginModel;

    public function __construct(PluginModel $pluginModel)
    {
        $this->pluginModel = $pluginModel;
    }

    public function getBuilderPlugins()
    {
        $builderPlugins = [];
        $plugins        = $this->pluginModel->getPluginBundles();

        foreach ($plugins as $plugin) {
            $config = $this->pluginModel->getBundleConfig($plugin['bundle'], '', true);
            $type   = !empty($config['type']) ? $config['type'] : null;

            if ($type === 'builder') {
                $builderPlugins[$plugin['bundle']] = $plugin['config'];
            }
        }

        return $builderPlugins;
    }
}
