<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<script>
    mauticLang.newListEmail = "<?php echo $view['translator']->trans('mautic.email.type.list.header'); ?>";
    mauticLang.newTemplateEmail = "<?php echo $view['translator']->trans('mautic.email.type.template.header'); ?>";
</script>
<div class="email-type-modal-backdrop" style="width: 100%; height: 100%; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: #000000; opacity: 0.9; z-index: 9000"></div>

<div class="modal fade in email-type-modal" style="display: block; z-index: 9999;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <a href="<?php echo $view['router']->generate('mautic_email_index'); ?>" onclick="Mautic.startModalLoadingBar('.email-type-modal');" data-toggle="ajax" class="close" ><span aria-hidden="true">&times;</span></a>
                <h4 class="modal-title" id="myModalLabel">
                    <?php echo $view['translator']->trans('mautic.email.type.header'); ?>
                </h4>
                <div class="modal-loading-bar"></div>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <div class="col-xs-10 np">
                                    <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.email.type.list.header'); ?></h3>
                                </div>
                                <div class="col-xs-2 pl-0 pr-0 pt-10 pb-10 text-right">
                                    <i class="fa fa-list fa-lg"></i>
                                </div>
                            </div>
                            <div class="panel-body" style="min-height: 150px;">
                                <?php echo $view['translator']->trans('mautic.email.type.list.description'); ?>
                            </div>
                            <div class="panel-footer text-center">
                                <button class="btn btn-lg btn-default btn-nospin text-success" onclick="Mautic.selectEmailType('list');"><?php echo $view['translator']->trans('mautic.core.select'); ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <div class="col-xs-10 np">
                                    <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.email.type.template.header'); ?></h3>
                                </div>
                                <div class="col-xs-2 pl-0 pr-0 pt-10 pb-10 text-right">
                                    <i class="fa fa-cube fa-lg"></i>
                                </div>
                            </div>
                            <div class="panel-body" style="min-height: 150px;">
                                <?php echo $view['translator']->trans('mautic.email.type.template.description'); ?>
                            </div>
                            <div class="panel-footer text-center">
                                <button class="btn btn-lg btn-default btn-nospin text-primary" onclick="Mautic.selectEmailType('template');"><?php echo $view['translator']->trans('mautic.core.select'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>