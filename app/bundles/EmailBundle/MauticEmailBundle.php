<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle;

use Mautic\EmailBundle\DependencyInjection\Compiler\EmailTransportPass;
use Mautic\EmailBundle\DependencyInjection\Compiler\SpoolTransportPass;
use Mautic\EmailBundle\DependencyInjection\Compiler\StatHelperPass;
use Mautic\EmailBundle\DependencyInjection\Compiler\SwiftmailerDynamicMailerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class MauticEmailBundle.
 */
class MauticEmailBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new SpoolTransportPass());
        $container->addCompilerPass(new EmailTransportPass());
        $container->addCompilerPass(new SwiftmailerDynamicMailerPass());
        $container->addCompilerPass(new StatHelperPass());
    }
}
