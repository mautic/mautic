<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$searchValue    = (empty($searchValue)) ? '' : $searchValue;
$target         = (empty($target)) ? '.page-list' : $target;
$overlayTarget  = (empty($overlayTarget)) ? $target : $overlayTarget;
$overlayEnabled = (!empty($overlayDisabled)) ? 'false' : 'true';
$id             = (empty($searchId)) ? 'list-search' : $searchId;
$tmpl           = (empty($tmpl)) ? 'list' : $tmpl;
?>

<div class="input-group">
    <?php if (!empty($searchHelp)): ?>
     <div class="input-group-btn">
        <button class="btn btn-default btn-nospin" data-toggle="modal" data-target="#<?php echo $searchId; ?>-search-help">
            <i class="fa fa-question-circle"></i>
        </button>
    </div>
    <?php endif; ?>

    <input type="search" class="form-control search" id="<?php echo $id; ?>" name="search" placeholder="<?php echo $view['translator']->trans('mautic.core.search.placeholder'); ?>" value="<?php echo $view->escape($searchValue); ?>" autocomplete="false" data-toggle="livesearch" data-target="<?php echo $target; ?>" data-tmpl="<?php echo $tmpl; ?>" data-action="<?php echo $action; ?>" data-overlay="<?php echo $overlayEnabled; ?>" data-overlay-text="<?php echo $view['translator']->trans('mautic.core.search.livesearch'); ?>" data-overlay-target="<?php echo $overlayTarget; ?>" />
    <div class="input-group-btn">
        <button type="button" class="btn btn-default btn-search btn-nospin" id="btn-filter" data-livesearch-parent="<?php echo $id; ?>">
            <i class="fa fa-search fa-fw"></i>
        </button>
    </div>
</div>

<?php
if ($searchHelp):
echo $view->render('MauticCoreBundle:Helper:modal.html.php', [
    'id'     => $searchId.'-search-help',
    'header' => $view['translator']->trans('mautic.core.search.header'),
    'body'   => $view['translator']->trans('mautic.core.search.help').$view['translator']->trans($searchHelp),
]);
endif;
?>
