<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

//Configurator object
$container->setDefinition('mautic.helper.integration', new Definition(
    'Mautic\AddonBundle\Helper\IntegrationHelper',
    array(new Reference('mautic.factory'))
));

$container->setDefinition('mautic.helper.addon', new Definition(
    'Mautic\AddonBundle\Helper\AddonHelper',
    array(new Reference('mautic.factory'))
));