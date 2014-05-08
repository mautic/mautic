<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;

//Mautic event listener
$container->setDefinition(
    'mautic.core.subscriber',
    new Definition(
        'Mautic\CoreBundle\EventListener\CoreSubscriber',
        array(
            new Reference('service_container'),
            new Reference('request_stack'),
            new Reference('doctrine.orm.entity_manager')
        )
    )
)
    ->addTag('kernel.event_subscriber');


//Database table prefix
$container->setDefinition ('mautic.tblprefix_subscriber',
    new Definition(
        'Mautic\CoreBundle\EventListener\TablePrefixSubscriber',
        array(
            '%mautic.db_table_prefix%',
            '%mautic.bundles%'
        )
    )
)->addTag('doctrine.event_subscriber');