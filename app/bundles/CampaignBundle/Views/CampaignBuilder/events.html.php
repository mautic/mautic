<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$levelInitiated = false;
if (!isset($level)) {
    $level = 1;
}

$levelClass  = 'nestedSortableLevel' . $level;
$levelClass .= ($level === 1) ? '  nestedSortable' : '';
?>
<?php if (!$levelInitiated): ?>
<ol class="<?php echo $levelClass; ?>">
<?php endif; ?>
    <?php foreach ($events as $event): ?>
    <?php
    $parent    = $event['parent'];
    $attr      = 'id="event'.$event['id'].'"';
    $attr     .= (!empty($parent)) ? ' data-parent="'.$parent.'"' : '';
    $children  = $event['children'];
    $template  = (isset($event['settings']['template'])) ? $event['settings']['template'] :
        'MauticCampaignBundle:Event:generic.html.php';

    echo $view->render($template, array(
        'event'   => $event,
        'inForm'  => (isset($inForm)) ? $inForm : false,
        'id'      => $event['id'],
        'deleted' => in_array($event['id'], $deletedEvents)
    ));

    if (!empty($children)):
        echo $view->render('MauticCampaignBundle:CampaignBuilder:events.html.php', array(
            'events'        => $event['children'],
            'level'         => $level + 1,
            'deletedEvents' => $deletedEvents,
            'inForm'        => $inForm
        ));
    endif;
    ?>
    <?php endforeach; ?>
<?php if (!$levelInitiated): ?>
</ol>
<?php $levelInitiated = true; ?>
<?php endif; ?>