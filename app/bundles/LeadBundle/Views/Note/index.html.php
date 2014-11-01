<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="box-layout mb-lg">
	<div class="panel col-xs-10 va-m">
	    <div class="form-control-icon pa-xs input-group">
	        <input type="text" name="search" value="<?php echo $search; ?>" id="NoteFilter" class="form-control bdr-w-0" placeholder="<?php echo $view['translator']->trans('mautic.core.search.placeholder'); ?>" data-toggle="livesearch" data-target="#NoteList" data-action="<?php echo $view['router']->generate('mautic_leadnote_index', array('leadId' => $lead->getId(), 'page' => 1)); ?>">
	        <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
	    </div>
	</div>
	<div class="col-xs-2 va-m">
		<a class="btn btn-primary btn-leadnote-add pull-right" href="<?php echo $this->container->get('router')->generate('mautic_leadnote_action', array('leadId' => $lead->getId(), 'objectAction' => 'new')); ?>" data-toggle="ajaxmodal" data-target="#leadModal" data-header="<?php echo $view['translator']->trans('mautic.lead.note.header.new'); ?>"><i class="fa fa-plus fa-lg"></i> Add Note</a>
	</div>
</div>

<div id="NoteList">
    <?php $view['slots']->output('_content'); ?>
</div>