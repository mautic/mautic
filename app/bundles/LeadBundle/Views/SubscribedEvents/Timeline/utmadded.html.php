<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Mautic\CoreBundle\Helper\InputHelper;

$utmTags = $event['extra']['utmtags'];
?>

<dl class="dl-horizontal">
<?php if (!empty($utmTags['utm_campaign'])): ?>
<dt><?php echo $view['translator']->trans('mautic.lead.timeline.event.utmcampaign'); ?>:</dt>
<dd><?php echo $utmTags['utm_campaign']; ?></dd>
<?php endif; ?>
<?php if (!empty($utmTags['utm_content'])): ?>
<dt><?php echo $view['translator']->trans('mautic.lead.timeline.event.utmcontent'); ?>:</dt>
   <dd><?php echo $utmTags['utm_content']; ?></dd>
<?php endif; ?>
<?php if (!empty($utmTags['utm_medium'])): ?>
<dt><?php echo $view['translator']->trans('mautic.lead.timeline.event.utmmedium'); ?>:</dt>
<dd><?php echo $utmTags['utm_medium']; ?></dd>
<?php endif; ?>

<?php if (!empty($utmTags['utm_source'])): ?>
<dt><?php echo $view['translator']->trans('mautic.lead.timeline.event.umtsource'); ?>:</dt>
<dd><?php echo $utmTags['utm_source']; ?></dd>
<?php endif; ?>
<?php if (!empty($utmTags['utm_term'])): ?>
<dt><?php echo $view['translator']->trans('mautic.lead.timeline.event.utmterm'); ?>:</dt>
<dd><?php echo $utmTags['utm_term']; ?></dd>
<?php endif; ?>
<?php

$counter = 1;
if (!empty($utmTags['query'])) {
    foreach ($utmTags['query'] as $k => $v) {
        // remove tags
        $v = InputHelper::clean($v);

        if (in_array($v, ['', null, []])) {
            continue;
        }
        if (in_array($k, ['ct', 'page_title', 'page_referrer', 'page_url'])) {
            continue;
        }

        if (in_array($k, ['utm_campaign', 'utm_source', 'utm_medium', 'utm_content', 'utm_term'])) {
            continue;
        }

        if (!empty($v)) {
            ++$counter;

            $k = ucwords(str_replace('_', ' ', $k));

            echo '<dt>'.$k.':</dt>';
            echo '<dd>'.$v.'</dd>';

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