<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="box-layout mb-lg">
	<div class="col-xs-10 va-m">
        <form action="<?php echo $view['router']->path('mautic_contactnote_index', ['page' => $page, 'leadId' => $lead->getId(), 'tmpl' => 'list']); ?>" class="panel" id="note-filters" method="post">
            <div class="form-control-icon pa-xs">
                <input type="text" name="search" value="<?php echo $view->escape($search); ?>" id="NoteFilter" class="form-control bdr-w-0" placeholder="<?php echo $view['translator']->trans('mautic.core.search.placeholder'); ?>" data-toggle="livesearch" data-target="#NoteList" data-action="<?php echo $view['router']->path('mautic_contactnote_index', ['leadId' => $lead->getId(), 'page' => 1]); ?>">
                <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
            </div>
            <input type="hidden" name="leadId" id="leadId" value="<?php echo $view->escape($lead->getId()); ?>" />
            <div class="panel-footer text-muted">
                <?php foreach ($noteTypes as $typeKey => $typeName) : ?>
                    <div class="checkbox-inline custom-primary">
                        <label class="mb-0">
                            <input name="noteTypes[]" type="checkbox" value="<?php echo $view->escape($typeKey); ?>"<?php echo in_array($typeKey, $noteType) ? ' checked' : ''; ?> />
                            <span class="mr-0"></span>
                            <?php echo $view['translator']->trans($typeName); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </form>
	</div>
	<div class="col-xs-2 va-t">
		<a class="btn btn-primary btn-leadnote-add pull-right" href="<?php echo $view['router']->path('mautic_contactnote_action', ['leadId' => $lead->getId(), 'objectAction' => 'new']); ?>" data-toggle="ajaxmodal" data-target="#MauticSharedModal" data-header="<?php echo $view['translator']->trans('mautic.lead.note.header.new'); ?>"><i class="fa fa-plus fa-lg"></i> <?php echo $view['translator']->trans('mautic.lead.add.note'); ?></a>
	</div>
</div>

<div id="NoteList">
    <?php $view['slots']->output('_content'); ?>
</div>
