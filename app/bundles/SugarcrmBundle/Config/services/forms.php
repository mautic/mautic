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

$container->setDefinition('mautic.sugarcrm.form.type.apikeys',
    new Definition( 'Mautic\SugarcrmBundle\Form\Type\ApiKeysType',
        array(new Reference('mautic.factory'))
    )
)->addTag('form.type', array( 'alias' => 'sugarcrm_apikeys', ));

$container->setDefinition('mautic.sugarcrm.form.type.mappedfields',
    new Definition( 'Mautic\SugarcrmBundle\Form\Type\MappedFieldsType',
        array(new Reference('mautic.factory'))
    )
)->addTag('form.type', array( 'alias' => 'sugarcrm_mappedfields', ));