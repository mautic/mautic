<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($note instanceof \Mautic\LeadBundle\Entity\LeadNote) {
    $id     = $note->getId();
    $text   = $note->getText();
    $date   = $note->getDateTime();
    $author = $note->getCreatedByUser();
    $type   = $note->getType();
} else {
    $id     = $note['id'];
    $text   = $note['text'];
    $date   = $note['dateTime'];
    $author = $note['createdByUser'];
    $type   = $note['type'];
}

switch ($type) {
    default:
    case 'general':
        $icon = 'fa-file-text';
        break;
    case 'email':
        $icon = 'fa-send';
        break;
    case 'call':
        $icon = 'fa-phone';
        break;
    case 'meeting':
        $icon = 'fa-group';
        break;
}

?>
<li id="LeadNote<?php echo $id; ?>">
    <div class="panel ">
        <div class="panel-body np box-layout">
            <div class="height-auto icon bdr-r bg-dark-xs col-xs-1 text-center">
                <h3><i class="fa fa-lg fa-fw <?php echo $icon; ?>"></i></h3>
            </div>
            <div class="media-body col-xs-11 pa-10">
                <div class="pull-right btn-group">
                    <?php if ($permissions['edit']): ?>
                        <a class="btn btn-default btn-xs" href="<?php echo $view['router']->generate('mautic_contactnote_action', ['leadId' => $lead->getId(), 'objectAction' => 'edit', 'objectId' => $id]); ?>" data-toggle="ajaxmodal" data-target="#MauticSharedModal" data-header="<?php echo $view['translator']->trans('mautic.lead.note.header.edit'); ?>"><i class="fa fa-pencil"></i></a>
                    <?php endif; ?>
                     <?php if ($permissions['delete']): ?>
                         <a class="btn btn-default btn-xs"
                            data-toggle="confirmation"
                            href="<?php echo $view['router']->path('mautic_contactnote_action', ['objectAction' => 'delete', 'objectId' => $id, 'leadId' => $lead->getId()]); ?>"
                            data-message="<?php echo $view->escape($view['translator']->trans('mautic.lead.note.confirmdelete')); ?>"
                            data-confirm-text="<?php echo $view->escape($view['translator']->trans('mautic.core.form.delete')); ?>"
                            data-confirm-callback="executeAction"
                            data-cancel-text="<?php echo $view->escape($view['translator']->trans('mautic.core.form.cancel')); ?>">
                             <i class="fa fa-trash text-danger"></i>
                         </a>
                     <?php endif; ?>
                </div>
                <?php echo $text; ?>
                <div class="mt-15 text-muted"><i class="fa fa-clock-o fa-fw"></i><span class="small"><?php echo $view['date']->toFullConcat($date); ?></span> <i class="fa fa-user fa-fw"></i><span class="small"><?php echo $author; ?></span></div>
            </div>
        </div>
    </div>
</li>
