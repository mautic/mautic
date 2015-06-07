<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle;

use Mautic\InstallBundle\Configurator\Step\CheckStep;
use Mautic\InstallBundle\Configurator\Step\DoctrineStep;
use Mautic\InstallBundle\Configurator\Step\EmailStep;
use Mautic\InstallBundle\Configurator\Step\MiscStep;
use Mautic\InstallBundle\Configurator\Step\UserStep;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class MauticInstallBundle
 *
 * @package Mautic\InstallBundle
 */
class MauticInstallBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        /** @var \Mautic\InstallBundle\Configurator\Configurator $configurator */
        $configurator = $this->container->get('mautic.configurator');

        $factory =  $this->container->get('mautic.factory');
        $request = $factory->getRequest();

        // Steps should be added here in the order they'll be displayed
        $configurator->addStep(new CheckStep($configurator->isFileWritable(), $this->container->getParameter('kernel.root_dir'), $request->getSchemeAndHttpHost().$request->getBasePath()));
        $configurator->addStep(new DoctrineStep($configurator->getParameters()));
        $configurator->addStep(new UserStep($factory->getSession()));
        $configurator->addStep(new EmailStep($factory->getSession()));

        /*
        $configurator->addStep(new MiscStep($request->getSchemeAndHttpHost().$request->getBasePath()));
        */
    }
}
