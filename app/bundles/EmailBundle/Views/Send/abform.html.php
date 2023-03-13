<?php

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'emailSend');
/** @var \Mautic\EmailBundle\Entity\Email $email */
$header = $view['translator']->trans('mautic.email.send.list', ['%name%' => $email->getName()]);
$header .= ' <i title="'.$view['translator']->trans('mautic.email.icon_tooltip.abtest.part').'" class="fa fa-fw fa-sitemap"></i>';
$view['slots']->set('headerTitle', $header);

?>
<div class="row">
    <div class="col-sm-offset-3 col-sm-6">
        <div class="ml-lg mr-lg mt-md pa-lg">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <div class="panel-title text-center">
                        <p><?php echo $view['translator']->trans('mautic.email.absend.instructions'); ?></p>
                    </div>
                </div>
                <div class="panel-body">
                    <?php echo $view['form']->start($form); ?>
                    <div class="col-xs-8 col-xs-offset-2">
                        <div class="well mt-lg">
                            <div class="input-group">
                                <span class="input-group-btn text-center">
                                    <?php echo $view->render('MauticCoreBundle:Helper:confirm.html.php', [
                                        'message'         => $view['translator']->trans('mautic.email.form.confirmsend', ['%name%' => $email->getName()]),
                                        'confirmText'     => $view['translator']->trans('mautic.email.send'),
                                        'confirmCallback' => 'submitAbSendForm',
                                        'iconClass'       => 'fa fa-send-o',
                                        'btnText'         => $view['translator']->trans('mautic.email.send.background'),
                                        'btnClass'        => 'btn btn-primary btn-send'.((!$pending) ? ' disabled' : ''),
                                    ]);
                                    ?>
                                </span>
                            </div>
                            <div class="text-center">
                                <span class="label label-primary mt-lg"><?php echo $view['translator']->transChoice('mautic.email.send.pending', $pending, ['%pending%' => $pending]); ?></span>
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