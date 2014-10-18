<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($note instanceof \Mautic\LeadBundle\Entity\LeadNote) {
    $id        = $note->getId();
    $text      = $note->getText();
    $date      = $note->getDateAdded();
    $createdBy = $note->getCreatedBy();
    $author    = $createdBy->getFirstName() . ' ' . $createdBy->getLastName();
    $type      = $note->getType();
} else {
    $id     = $note['id'];
    $text   = $note['text'];
    $date   = $note['dateAdded'];
    $author = $note['createdBy']['firstName'] . ' ' . $note['createdBy']['lastName'];
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
        <div class="panel-body np">
            <div class="height-auto icon bdr-r bg-dark-xs col-xs-1 text-center">
                <h3><i class="fa fa-lg fa-fw <?php echo $icon; ?>"></i></h3>
            </div>
            <div class="media-body col-xs-11">
                <p class="mb-sm">
                    <?php echo $text; ?>
                </p>
                <div class="clearfix"></div>
                <div class="pull-left">
                    <div><i class="fa fa-clock-o fa-fw"></i><span class="small"><?php echo $view['date']->toFullConcat($date); ?></span></div>
                    <div><i class="fa fa-user fa-fw"></i><span class="small"><?php echo $author; ?></span></div>
                </div>
                <div class="pull-right">
                     <span>
                        <?php if ($permissions['edit']): ?>
                            <a class="btn btn-xs" href="<?php echo $this->container->get('router')->generate('mautic_leadnote_action', array('leadId' => $lead->getId(), 'objectAction' => 'edit', 'objectId' => $id)); ?>" data-toggle="ajaxmodal" data-target="#leadModal" data-header="<?php echo $view['translator']->trans('mautic.lead.note.header.edit'); ?>"><i class="fa fa-pencil"></i></a>
                        <?php endif; ?>
                         <?php if ($permissions['delete']): ?>
                             <a class="btn btn-xs ml-10" href="javascript:void(0);" onclick="Mautic.showConfirmation('<?php echo $view->escape($view["translator"]->trans('mautic.lead.note.confirmdelete'), 'js'); ?>', '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>', 'executeAction', ['<?php echo $view['router']->generate('mautic_leadnote_action', array('objectAction' => 'delete', 'objectId' => $id, 'leadId' => $lead->getId())); ?>', ''], '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);"><i class="fa fa-trash text-danger"></i></a>
                         <?php endif; ?>
                    </span>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>
</li>