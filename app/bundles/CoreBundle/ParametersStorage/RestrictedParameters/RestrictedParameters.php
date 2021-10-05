<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\ParametersStorage\RestrictedParameters;

use Mautic\CoreBundle\MauticCoreBundle;

class RestrictedParameters
{
    private array $restrictedConfigFields;

    private Container $container;

    public function __construct(array $restrictedConfigFields, Container $container)
    {
        $this->restrictedConfigFields = $restrictedConfigFields;
        $this->container              = $container;
    }

    public function find()
    {
        $array = $this->getParamsFromBundles();
    }

    private function getParamsFromBundles(): array
    {
        $bundles          = MauticCoreBundle::getBundles($this->container);
        $restrictedFields = [];
        array_walk_recursive($bundles, function ($value) {
            if (is_string($value) && false !== strpos('%%mautic.', $value)) {
                $restrictedFields[] = $value;
            }
        });

        return $restrictedFields;
    }
}
