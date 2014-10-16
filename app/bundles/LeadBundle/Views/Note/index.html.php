<span class="input-group-btn"><a class="btn btn-sm btn-default btn-leadnote-add pull-right" href="<?php echo $this->container->get('router')->generate('mautic_leadnote_action', array('leadId' => $lead->getId(), 'objectAction' => 'new')); ?>" data-toggle="ajaxmodal" data-target="#leadModal" data-header="<?php echo $view['translator']->trans('mautic.lead.note.header.new'); ?>"><i class="fa fa-plus fa-lg"></i> Add Note</a></span>

<!-- form -->
<form action="" class="panel">
    <div class="form-control-icon pa-xs input-group">
        <input type="text" class="form-control bdr-w-0" placeholder="<?php echo $view['translator']->trans('mautic.core.search.placeholder'); ?>">
        <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
    </div>
</form>
<!--/ form -->

<ul class="timeline">
    <li class="header ellipsis bg-white"><?php echo $view['translator']->trans('mautic.lead.note.notes'); ?></li>
    <li class="wrapper">
        <ul class="events">
            <?php foreach ($notes as $note): ?>
                <?php echo $view->render('MauticLeadBundle:Note:note.html.php', array(
                    'note'        => $note,
                    'lead'        => $lead,
                    'permissions' => $permissions
                )); ?>
            <?php endforeach; ?>
        </ul>
    </li>
</ul>