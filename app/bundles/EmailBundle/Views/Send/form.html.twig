<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');

$view['slots']->set('mauticContent', 'emailSend');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.email.send.list', ['%name%' => $email->getName()]));

?>
<div class="row">
    <div class="col-sm-offset-2 col-sm-8 col-md-offset-3 col-md-6">
        <div class="ml-lg mr-lg mt-md pa-lg">
            <div class="panel panel-info">
                <div class="panel-body">
                    <?php echo $view['form']->start($form); ?>
                    <div class="col-xs-offset-1 col-xs-10 col-lg-offset-2 col-lg-8">
                        <div class="well mt-lg">
                            <div class="input-group">
                                <span class="input-group-btn text-center">
                                    <?php echo $view->render('MauticCoreBundle:Helper:confirm.html.php', [
                                        'message'         => $view['translator']->trans('mautic.email.form.confirmsend', ['%name%' => $email->getName()]),
                                        'confirmText'     => $view['translator']->trans('mautic.email.send'),
                                        'confirmCallback' => 'submitSendForm',
                                        'iconClass'       => 'fa fa-send-o',
                                        'btnText'         => $view['translator']->trans('mautic.email.send'),
                                        'btnClass'        => 'btn btn-primary btn-send'.((!$pending) ? ' disabled' : ''),
                                    ]);
                                    ?>
                                </span>
                            </div>
                            <div class="text-center">
                                <span class="label label-primary mt-lg"><?php echo $view['translator']->trans(
                                        'mautic.email.send.pending',
                                        ['%count%' => $pending]
                                    ); ?></span>
                                <div class="mt-sm">
                                    <a class="text-danger mt-md" href="<?php echo $view['router']->path('mautic_email_action', ['objectAction' => 'view', 'objectId' => $email->getId()]); ?>" data-toggle="ajax"><?php echo $view['translator']->trans('mautic.core.form.cancel'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php echo $view['form']->end($form); ?>
                </div>
            </div>
        </div>
    </div>
</div>
