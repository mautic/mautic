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

//Setup a listener to prepopulate information such as bundle name, action name, template name, etc into the request
//attributes for use in the templates
$listener = new Definition('Mautic\BaseBundle\EventListener\MauticListener');
$listener->addTag('kernel.event_listener', array(
    'event'  => 'kernel.controller',
    'method' => 'onKernelController'
));
$container->setDefinition('mautic.events.action_listener', $listener);