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
$view['slots']->set('mauticContent', 'mobile_notification');

$header = ($notification->getId()) ?
    $view['translator']->trans('mautic.notification.mobile.header.edit',
        ['%name%' => $notification->getName()]) :
    $view['translator']->trans('mautic.notification.mobile.header.new');

$view['slots']->set('headerTitle', $header);

/** @var \Mautic\NotificationBundle\Integration\OneSignalIntegration $integration */
$integrationSettings = $integration->getIntegrationSettings()->getFeatureSettings();
?>

<?php echo $view['form']->start($form); ?>
<div class="box-layout">
    <div class="col-md-9 height-auto bg-white">
        <div class="row">
            <div class="col-xs-12">
                <!-- tabs controls -->
                <ul class="bg-auto nav nav-tabs pr-md pl-md">
                    <li class="active">
                        <a href="#notification-container" role="tab" data-toggle="tab">
                            <?php echo $view['translator']->trans('mautic.core.details'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#data-notification-container" role="tab" data-toggle="tab">
                            <?php echo $view['translator']->trans('mautic.notification.tab.data'); ?>
                        </a>
                    </li>
                    <?php if (in_array('ios', $integrationSettings['platforms'])) : ?>
                        <li>
                            <a href="#ios-notification-container" role="tab" data-toggle="tab">
                                <?php echo $view['translator']->trans('mautic.notification.tab.ios'); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (in_array('android', $integrationSettings['platforms'])) : ?>
                        <li>
                            <a href="#android-notification-container" role="tab" data-toggle="tab">
                                <?php echo $view['translator']->trans('mautic.notification.tab.android'); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <!--/ tabs controls -->
                <div class="tab-content pa-md">
                    <div class="tab-pane fade in active bdr-w-0" id="notification-container">
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['name']); ?>
                                <?php echo $view['form']->row($form['heading']); ?>
                                <?php echo $view['form']->row($form['url']); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['message']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade in bdr-w-0" id="data-notification-container">
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['mobileSettings']['additional_data']); ?>
                            </div>
                        </div>
                    </div>
                    <?php echo $view['form']->widget($form['mobileSettings'], ['integrationSettings' => $integrationSettings]); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto bdr-l">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php echo $view['form']->row($form['category']); ?>
            <?php echo $view['form']->row($form['language']); ?>
            <div class="hide">
                <?php echo $view['form']->row($form['isPublished']); ?>
                <?php echo $view['form']->row($form['publishUp']); ?>
                <?php echo $view['form']->row($form['publishDown']); ?>

                <h5><?php echo $view['translator']->trans('mautic.email.utm_tags'); ?></h5>
                <?php
                foreach ($form['utmTags'] as $i => $utmTag):
                    echo $view['form']->row($utmTag);
                endforeach;
                ?>
                <?php echo $view['form']->rest($form); ?>
            </div>
        </div>
    </div>
</div>
<?php echo $view['form']->end($form); ?>