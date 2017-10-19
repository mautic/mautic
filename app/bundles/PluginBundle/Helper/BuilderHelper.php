<?php

namespace Mautic\PluginBundle\Helper;

use Mautic\CoreBundle\Helper\BundleHelper;

class BuilderHelper
{
    private $pluginModel;

    public function __construct(BundleHelper $bundleHelper)
    {
        $this->bundleHelper = $bundleHelper;
    }

    public function getBuilderPlugins()
    {
        $builderPlugins = [];
        $plugins        = $this->bundleHelper->getPluginBundles();

        foreach ($plugins as $plugin) {
            $config = $this->bundleHelper->getBundleConfig($plugin['bundle'], '', true);
            $type   = !empty($config['type']) ? $config['type'] : null;

            if ($type === 'builder') {
                $builderPlugins[$plugin['bundle']] = $plugin['config'];
            }
        }

        return $builderPlugins;
    }
}
