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

//Client Model
$container->setDefinition(
    'mautic.model.client',
    new Definition(
        'Mautic\ApiBundle\Model\ClientModel',
        array(
            new Reference("service_container"),
            new Reference('request_stack'),
            new Reference('doctrine.orm.entity_manager'),
        )
    )
);