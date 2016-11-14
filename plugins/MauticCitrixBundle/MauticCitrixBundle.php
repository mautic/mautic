<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
        CitrixHelper::init($this->container);
    }
}
