<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'notification');

$notificationType = $form['notificationType']->vars['data'];

$header = ($notification->getId()) ?
    $view['translator']->trans('mautic.notification.header.edit',
        array('%name%' => $notification->getName())) :
    $view['translator']->trans('mautic.notification.header.new');

$view['slots']->set("headerTitle", $header);

if (!isset($attachmentSize)) {
    $attachmentSize = 0;
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
                                <?php echo $view['form']->row($form['url']); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['message']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto bdr-l">
        <div class="pr-lg pl-lg pt-md pb-md">
            <div id="leadList"<?php echo ($notificationType == 'template') ? ' class="hide"' : ''; ?>>
                <?php echo $view['form']->row($form['lists']); ?>
            </div>
            <?php echo $view['form']->row($form['category']); ?>
            <?php echo $view['form']->row($form['language']); ?>
            <div class="hide">
                <div id="publishStatus"<?php echo ($notificationType == 'list') ? ' class="hide"' : ''; ?>>
                    <?php echo $view['form']->row($form['isPublished']); ?>
                    <?php echo $view['form']->row($form['publishUp']); ?>
                    <?php echo $view['form']->row($form['publishDown']); ?>
                </div>

                <?php echo $view['form']->rest($form); ?>
            </div>
        </div>
    </div>
</div>
<?php echo $view['form']->end($form); ?>

<?php
$type = $notification->getNotificationType();
if (empty($type) || ! empty($forceTypeSelection)):
    echo $view->render('MauticCoreBundle:Helper:form_selecttype.html.php',
        array(
            'item'               => $notification,
            'mauticLang'         => array(
                'newListNotification'     => 'mautic.notification.type.list.header',
                'newTemplateNotification' => 'mautic.notification.type.template.header'
            ),
            'typePrefix'         => 'notification',
            'cancelUrl'          => 'mautic_notification_index',
            'header'             => 'mautic.notification.type.header',
            'typeOneHeader'      => 'mautic.notification.type.template.header',
            'typeOneIconClass'   => 'fa-cube',
            'typeOneDescription' => 'mautic.notification.type.template.description',
            'typeOneOnClick'     => "Mautic.selectNotificationType('template');",
            'typeTwoHeader'      => 'mautic.notification.type.list.header',
            'typeTwoIconClass'   => 'fa-list',
            'typeTwoDescription' => 'mautic.notification.type.list.description',
            'typeTwoOnClick'     => "Mautic.selectNotificationType('list');",
        ));
endif;