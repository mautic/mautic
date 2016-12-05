<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'pointTrigger');
$view['slots']->set('headerTitle', $entity->getName());

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', [
    'item'            => $entity,
    'templateButtons' => [
        'edit'   => $permissions['point:triggers:edit'],
        'delete' => $permissions['point:triggers:delete'],
    ],
    'routeBase' => 'pointtrigger',
    'langVar'   => 'point.trigger',
]));
?>

<div class="scrollable trigger-details">
    <?php //@todo - output trigger details/actions?>
</div>