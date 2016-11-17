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
$view['slots']->set('mauticContent', 'calendar');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.calendar.menu.index'));
?>

<div class="panel panel-default mnb-5 bdr-t-wdh-0">
	<div class="panel-body">
		<div id="calendar"></div>
	</div>
</div>

<?php echo $view->render('MauticCoreBundle:Helper:modal.html.php', [
    'id'            => 'CalendarEditModal',
    'footerButtons' => true,
]);
