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
$view['slots']->set('mauticContent', 'notification');

$header = ($notification->getId()) ?
    $view['translator']->trans('mautic.notification.header.edit',
        ['%name%' => $notification->getName()]) :
    $view['translator']->trans('mautic.notification.header.new');

$view['slots']->set('headerTitle', $header);
if ($notification->getId()) {
    $customButtons = [
        [
            'attr' => [
                'data-toggle' => 'ajaxmodal',
                'data-target' => '#MauticSharedModal',
                'data-header' => $view['translator']->trans('mautic.notification.notification.header.preview'),
                'data-footer' => 'false',
                'href'        => $view['router']->path(
                    'mautic_notification_action',
                    ['objectId' => $notification->getId(), 'objectAction' => 'preview']
                ),
            ],
            'btnText'   => $view['translator']->trans('mautic.notification.preview'),
            'iconClass' => 'fa fa-share',
            'primary'   => false,
            'priority'  => 10,
        ],
    ];

    $view['slots']->set(
        'actions',
        $view->render(
            'MauticCoreBundle:Helper:page_actions.html.php',
            [
                'routeBase'     => 'notification',
                'customButtons' => $customButtons,
            ]
        )
    );
}
?>

<?php echo $view['form']->start($form); ?>
<div class="box-layout">
    <div class="col-md-9 height-auto bg-white">
        <div class="row">
            <div class="col-xs-12">
                <!-- tabs controls -->
                <!--/ tabs controls -->
                <div class="tab-content pa-md">
                    <div class="tab-pane fade in active bdr-w-0" id="notification-container">
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['name']); ?>
                                <?php echo $view['form']->row($form['heading']); ?>
                                <?php echo $view['form']->row($form['message']); ?>
                                <?php echo $view['form']->row($form['url']); ?>
                                <?php echo $view['form']->row($form['button']); ?>
                                <?php echo $view['form']->row($form['actionButtonIcon1']); ?>
                            </div>
                            <div class="col-md-6">
                                <?php include 'preview.html.php'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto bdr-l">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php echo $view['form']->row($form['category']); ?>
            <?php echo $view['form']->row($form['language']); ?>
            <hr />
            <h5><?php echo $view['translator']->trans('mautic.config.tab.notificationconfig'); ?></h5>
            <br />
            <?php echo $view['form']->row($form['ttl']); ?>
            <?php echo $view['form']->row($form['priority']); ?>
            <hr />
            <h5><?php echo $view['translator']->trans('mautic.email.utm_tags'); ?></h5>
            <br />
            <?php
            foreach ($form['utmTags'] as $i => $utmTag):
                echo $view['form']->row($utmTag);
            endforeach;
            ?>
            <div class="hide">
                <?php echo $view['form']->row($form['isPublished']); ?>
                <?php echo $view['form']->row($form['publishUp']); ?>
                <?php echo $view['form']->row($form['publishDown']); ?>
                <?php echo $view['form']->rest($form); ?>
            </div>
        </div>
    </div>
</div>
<?php echo $view['form']->end($form); ?>