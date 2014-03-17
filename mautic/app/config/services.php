<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;

//Register Mautic's custom routing
$container->setDefinition ('mautic.routing_loader',
    new Definition(
        'Mautic\BaseBundle\Routing\MauticLoader',
        array('%mautic.bundles%')
    )
)->addTag('routing.loader');