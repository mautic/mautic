<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view['slots']->set('mauticContent', 'leadnote');
$userId = $form->vars['data']->getId();
if (!empty($userId)) {
    $header = $view['translator']->trans('mautic.lead.note.header.edit');
} else {
    $header = $view['translator']->trans('mautic.lead.note.header.new');
}
?>
<?php echo $view['form']->start($form); ?>
<?php echo $view['form']->row($form['text']); ?>

<div class="row">
    <div class="col-xs-6">
        <?php echo $view['form']->widget($form['type']); ?>
    </div>
    <div class="col-xs-6">
        <?php echo $view['form']->widget($form['dateTime']); ?>
    </div>
</div>
<div class="row mt-10">
    <div class="col-xs-6">
        <?php echo $view['form']->row($form['attachment']); ?>
    </div>
    <div class="col-xs-12">
        <span class="<?php if (!isset($note) || !$note->getAttachment()): echo 'hidden'; endif; ?>">
        <strong><?php echo $view['translator']->trans('mautic.lead.note.type.attached.file'); ?>:</strong>
            <?php if (isset($note) && $note->getAttachment()): ?>
                <a href="<?php echo $view['router']->path('mautic_lead_note_attachment_download', ['objectId'=> $note->getId()]); ?>">
                <?php echo $note->getAttachment(); ?>
            </a>
            <?php endif; ?>
            &nbsp;
            <?php echo $view['form']->widget($form['attachment_remove']); ?>
            <span class="text-lowercase"><?php echo $view['translator']->trans('mautic.core.remove'); ?>  </span>
        </span>
    </div>
</div>

<?php echo $view['form']->row($form['buttons']); ?>
<?php echo $view['form']->end($form); ?>