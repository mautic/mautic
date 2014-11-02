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

$container->setDefinition(
    'mautic.lead.constraint.alias',
    new Definition(
        'Mautic\LeadBundle\Form\Validator\Constraints\UniqueUserAliasValidator',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('validator.constraint_validator', array(
        'alias' => 'uniqueleadlist',
    ));