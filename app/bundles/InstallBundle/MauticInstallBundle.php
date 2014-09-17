<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle;

use Mautic\InstallBundle\Configurator\Step\CheckStep;
use Mautic\InstallBundle\Configurator\Step\DoctrineStep;
use Mautic\InstallBundle\Configurator\Step\SecretStep;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class MauticInstallBundle
 *
 * @package Mautic\InstallBundle
 */
class MauticInstallBundle extends Bundle
{
    public function boot()
    {
        /** @var \Mautic\InstallBundle\Configurator\Configurator $configurator */
        $configurator = $this->container->get('mautic.configurator');
        $configurator->addStep(new CheckStep($configurator->getParameters(), $configurator->isFileWritable()));
        $configurator->addStep(new DoctrineStep($configurator->getParameters()));
        $configurator->addStep(new SecretStep($configurator->getParameters()));
    }
}
