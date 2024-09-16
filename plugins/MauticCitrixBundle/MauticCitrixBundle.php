<?php

namespace MauticPlugin\MauticCitrixBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;

/**
 * Class MauticCitrixBundle.
 */
class MauticCitrixBundle extends PluginBundleBase
{
    public function boot()
    {
        parent::boot();

        CitrixHelper::init(
            $this->container->get('mautic.helper.integration'),
            $this->container->get('monolog.logger.mautic'),
            $this->container->get('router')
        );
    }
}
