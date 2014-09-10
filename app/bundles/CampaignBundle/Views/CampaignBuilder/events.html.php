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
        if ($event instanceof \Mautic\CampaignBundle\Entity\CampaignEvent) {
            $parent   = $event->getParent();
            $id       = $event->getId();
            $children = $event->getChildren();
            $type     = $event->getType();
        } else {
            $parent   = $event['parent'];
            $id       = $event['id'];
            $children = $event['children'];
            $type     = $event['type'];
        }
        $settings = $eventTriggers[$type];

        $attr      = 'id="event'.$id.'"';
        $attr     .= (!empty($parent)) ? ' data-parent="'.$parent->getId().'"' : '';

        $template  = (isset($settings['template'])) ? $settings['template'] :
            'MauticCampaignBundle:Event:generic.html.php';

        echo $view->render($template, array(
            'event'   => $event,
            'inForm'  => (isset($inForm)) ? $inForm : false,
            'id'      => $id,
            'deleted' => in_array($id, $deletedEvents)
        ));

        if (!empty($children)):
            echo $view->render('MauticCampaignBundle:CampaignBuilder:events.html.php', array(
                'events'        => $children,
                'level'         => $level + 1,
                'deletedEvents' => $deletedEvents,
                'inForm'        => $inForm,
                'eventTriggers' => $eventTriggers
            ));
        endif;
     endforeach;
if (!$levelInitiated && $level > 1): ?>
</ol>
<?php $levelInitiated = true; ?>
<?php endif; ?>