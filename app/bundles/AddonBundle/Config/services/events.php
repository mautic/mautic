<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$container->setDefinition(
    'mautic.addon.pointbundle.subscriber',
    new \Symfony\Component\DependencyInjection\Definition(
        'Mautic\AddonBundle\EventListener\PointSubscriber',
        array(new \Symfony\Component\DependencyInjection\Reference('mautic.factory'))
    )
)
    ->addTag('kernel.event_subscriber');