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

//Lead Model
$container->setDefinition(
    'mautic.model.lead',
    new Definition(
        'Mautic\LeadBundle\Model\LeadModel',
        array(
            new Reference("service_container"),
            new Reference('request_stack'),
            new Reference('doctrine.orm.entity_manager'),
        )
    )
);

//Lead field model
$container->setDefinition(
    'mautic.model.leadfield',
    new Definition(
        'Mautic\LeadBundle\Model\LeadFieldModel',
        array(
            new Reference("service_container"),
            new Reference('request_stack'),
            new Reference('doctrine.orm.entity_manager'),
        )
    )
);

//Lead list model
$container->setDefinition(
    'mautic.model.leadlist',
    new Definition(
        'Mautic\LeadBundle\Model\LeadListModel',
        array(
            new Reference("service_container"),
            new Reference('request_stack'),
            new Reference('doctrine.orm.entity_manager'),
        )
    )
);