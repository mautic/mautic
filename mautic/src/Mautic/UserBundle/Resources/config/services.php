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

//Register the user form
$container->register(
        'mautic.form.type.user',
        'Mautic\UserBundle\Form\Type\UserType'
    )
    ->addTag('form.type', array(
        'alias' => 'user',
    ));

/*

$container->setDefinition(
    'mautic_user.example',
    new Definition(
        'Mautic\UserBundle\Example',
        array(
            new Reference('service_id'),
            "plain_value",
            new Parameter('parameter_name'),
        )
    )
);

*/