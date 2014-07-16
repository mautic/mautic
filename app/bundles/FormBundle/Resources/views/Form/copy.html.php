<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="panel panel-success">
    <div class="panel-heading">
        <?php echo $view['translator']->trans('mautic.form.form.header.copy'); ?>
        <div class="pull-right" data-toggle="tooltip" data-placement="left"
             title="<?php echo $view['translator']->trans('mautic.form.form.preview'); ?>">
            <button class="btn btn-primary btn-xs" data-toggle="modal" data-target="#form-preview">
                <i class="fa fa-camera"></i>
            </button>
        </div>
    </div>
    <div class="panel-body">
        <h2><?php echo $view['translator']->trans('mautic.form.form.header.landingpages'); ?></h2>
        <p><?php echo $view['translator']->trans('mautic.form.form.help.landingpages'); ?></p>
        <br />

        <h2><?php echo $view['translator']->trans('mautic.form.form.header.automaticcopy'); ?></h2>
        <p><?php echo $view['translator']->trans('mautic.form.form.help.automaticcopy'); ?></p>
        <textarea class="form-html form-control" readonly onclick="this.setSelectionRange(0, this.value.length);">&lt;script type="text/javascript" src="<?php echo $view['router']->generate('mautic_form_generateform', array('id' => $form->getId()), true); ?>"&gt;&lt;/script&gt;</textarea>
        <br />
        <h2><?php echo $view['translator']->trans('mautic.form.form.header.manualcopy'); ?></h2>
        <p><?php echo $view['translator']->trans('mautic.form.form.help.manualcopy'); ?></p>
        <textarea class="form-html form-control" readonly onclick="this.setSelectionRange(0, this.value.length);">
            <?php echo htmlentities($form->getCachedHtml()); ?>
        </textarea>
    </div>
</div>
<?php
echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id'     => 'form-preview',
    'header' => $view['translator']->trans('mautic.form.form.header.preview'),
    'body'   => $view->render('MauticFormBundle:Form:preview.html.php', array('form' => $form)),
    'size'   => 'lg'
));
?>