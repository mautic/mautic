<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$class = (empty($action['allowCampaignForm'])) ? 'action-standalone-only' : '';
if (empty($action['allowCampaignForm']) && !$isStandalone):
    $class .= ' hide';
endif;
?>

<li id="action_<?php echo $type; ?>" class="<?php echo $class; ?>">
    <a data-toggle="ajaxmodal" data-target="#formComponentModal" class="list-group-item" href="<?php echo $view['router']->path(
        'mautic_formaction_action',
        ['objectAction' => 'new', 'type' => $type, 'tmpl' => 'action', 'formId' => $formId]
    ); ?>">
        <div data-toggle="tooltip" title="<?php echo $view['translator']->trans($action['description']); ?>">
            <span><?php echo $view['translator']->trans($action['label']); ?></span>
        </div>
    </a>
</li>