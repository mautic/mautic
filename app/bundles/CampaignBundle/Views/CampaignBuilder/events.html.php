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
?>
<?php if (!$levelInitiated && $level > 1): ?>
<ol>
<?php
endif;
    foreach ($events as $event):
        if ($event instanceof \Mautic\CampaignBundle\Entity\Event) {
            $event = $event->convertToArray();
        }
        $settings = $eventTriggers[$event['type']];

        $attr      = 'id="event'.$event['id'].'"';
        $attr     .= (!empty($event['parent'])) ? ' data-parent="'.$event['parent']->getId().'"' : '';

        $template  = (isset($settings['template'])) ? $settings['template'] :
            'MauticCampaignBundle:Event:generic.html.php';

        $childrenHtml = (!empty($event['children'])) ? $view->render('MauticCampaignBundle:CampaignBuilder:events.html.php', array(
                'events'        => $event['children'],
                'level'         => $level + 1,
                'deletedEvents' => $deletedEvents,
                'inForm'        => $inForm,
                'eventTriggers' => $eventTriggers,
                'id'            => $event['id']
            )) : '';

        echo $view->render($template, array(
            'event'        => $event,
            'inForm'       => (isset($inForm)) ? $inForm : false,
            'id'           => $event['id'],
            'deleted'      => in_array($event['id'], $deletedEvents),
            'childrenHtml' => $childrenHtml,
            'level'        => $level
        ));
     endforeach;
if (!$levelInitiated && $level > 1): ?>
</ol>
<?php $levelInitiated = true; ?>
<?php endif; ?>