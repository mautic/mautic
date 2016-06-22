<?php
/**
* @package     Mautic
* @copyright   2014 Mautic Contributors. All rights reserved.
* @author      Mautic
* @link        http://mautic.org
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/
$utmTags = $event['extra']['utmtags'];
$icon = (isset($event['icon'])) ? $event['icon'] : '';
$sourceLabel = '';

if (isset($utmTags['utmMedium'])) {
    switch (strtolower($utmTags['utmMedium'])) {
        case 'social':
        case 'socialmedia':
            $icon = 'fa-' . ((isset($utmTags['utmSource'])) ? strtolower($utmTags['utmSource']) : 'share-alt');
            break;
        case 'email':
        case 'newsletter':
            $icon = 'fa-envelope-o';
            break;
        case 'banner':
        case 'ad':
            $icon = 'fa-bullseye';
            break;
        case 'cpc':
            $icon = 'fa-money';
            break;
        case 'location':
            $icon = 'fa-map-marker';
            break;
        case 'device':
            $icon = 'fa-' . ((isset($utmTags['utmSource'])) ? strtolower($utmTags['utmSource']) : 'tablet');
            break;
    }
}
if(isset($utmTags['utmSource'])){
    $sourceLabel = 'From: '.ucfirst($utmTags['utmSource']);
}


?>

<li class="wrapper">
    <div class="figure"><span class="fa <?php echo $icon; ?>"></span></div>
    <div class="panel">
        <div class="panel-body">
            <h3>UTM tags</h3>
            <p class="mb-0"><?php echo $view['translator']->trans('mautic.core.timeline.event.time', array('%date%' => $view['date']->toFullConcat($event['timestamp']), '%event%' => $event['eventLabel'].' '.$sourceLabel)); ?></p>
        </div>

        <div class="panel-footer">
            <dl class="dl-horizontal">
                <?php if (!empty($utmTags['utmCampaign'])): ?>
                <dt ><?php echo $view['translator']->trans('mautic.lead.timeline.event.utmcampaign');?></dt>
                <dd class="ellipsis"><?php echo $utmTags['utmCampaign']; ?></dd>
                <?php endif; ?>
                <?php if (!empty($utmTags['utmContent'])): ?>
                <dt><?php echo $view['translator']->trans('mautic.lead.timeline.event.utmcontent');?></dt>
                   <dd class="ellipsis"><?php echo $utmTags['utmContent']; ?></dd>
                <?php endif; ?>
                <?php if (!empty($utmTags['utmMedium'])): ?>
                <dt><?php echo $view['translator']->trans('mautic.lead.timeline.event.utmmedium');?></dt>
                <dd class="ellipsis"><?php echo $utmTags['utmMedium']; ?></dd>
                <?php endif; ?>

                <?php if (!empty($utmTags['utmSource'])): ?>
                <dt><?php echo $view['translator']->trans('mautic.lead.timeline.event.umtsource');?></dt>
                <dd class="ellipsis"><?php echo $utmTags['utmSource']; ?></dd>
                <?php endif; ?>
                <?php if (!empty($utmTags['utmTerm'])): ?>
                <dt><?php echo $view['translator']->trans('mautic.lead.timeline.event.utmterm');?></dt>
                <dd class="ellipsis"><?php echo $utmTags['utmTerm']; ?></dd>
                <?php endif; ?>
                <?php

                $counter = 1;
                if (!empty($utmTags['query'])) {

                    foreach ($utmTags['query'] as $k => $v) {
                        if (in_array($v, array('', null, array()))) {
                            continue;
                        }
                        if (in_array($k, array('ct', 'page_title', 'page_referrer', 'page_url'))) {
                            continue;
                        }

                        if (in_array($k, array('utm_campaign', 'utm_source', 'utm_medium', 'utm_content', 'utm_term'))){
                            continue;
                        }

                        if (!empty($v)) {
                            $counter++;
                            
                            $k = ucwords(str_replace('_', ' ', $k));

                            echo '<dt>'.$k.':</dt>';
                            echo '<dd class="ellipsis">'.$v.'</dd>';

                            if (empty($showMore) && $counter > 5) {
                                $showMore = true;

                                echo '<div style="display:none">';
                            }

                            continue;
                        }

                        if (!empty($showMore)) {
                            echo '</div>';
                            echo '<a href="javascript:void(0);" class="text-center small center-block mt-xs" onclick="Mautic.toggleTimelineMoreVisiblity(mQuery(this).prev());">';
                            echo $view['translator']->trans('mautic.core.more.show');
                            echo '</a>';
                        }

                    }
                }
                    ?>
            </dl>

        </div>
    </div>
</li>