<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'sms');

$smsType = $form['smsType']->vars['data'];

$header = ($sms->getId()) ?
    $view['translator']->trans('mautic.sms.header.edit',
        array('%name%' => $sms->getName())) :
    $view['translator']->trans('mautic.sms.header.new');

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
                    <div class="tab-pane fade in active bdr-w-0" id="sms-container">
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['name']); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
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
            <?php echo $view['form']->row($form['name']); ?>
            <div id="leadList"<?php echo ($smsType == 'template') ? ' class="hide"' : ''; ?>>
                <?php echo $view['form']->row($form['lists']); ?>
            </div>
            <?php //echo $view['form']->row($form['category']); ?>
            <?php echo $view['form']->row($form['language']); ?>
            <div class="hide">
                <div id="publishStatus"<?php echo ($smsType == 'list') ? ' class="hide"' : ''; ?>>
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
$type = $sms->getSmsType();
if (empty($type) || ! empty($forceTypeSelection)):
    echo $view->render('MauticCoreBundle:Helper:form_selecttype.html.php',
        array(
            'item'               => $sms,
            'mauticLang'         => array(
                'newListSms'     => 'mautic.sms.type.list.header',
                'newTemplateSms' => 'mautic.sms.type.template.header'
            ),
            'typePrefix'         => 'sms',
            'cancelUrl'          => 'mautic_sms_index',
            'header'             => 'mautic.sms.type.header',
            'typeOneHeader'      => 'mautic.sms.type.template.header',
            'typeOneIconClass'   => 'fa-cube',
            'typeOneDescription' => 'mautic.sms.type.template.description',
            'typeOneOnClick'     => "Mautic.selectSmsType('template');",
            'typeTwoHeader'      => 'mautic.sms.type.list.header',
            'typeTwoIconClass'   => 'fa-list',
            'typeTwoDescription' => 'mautic.sms.type.list.description',
            'typeTwoOnClick'     => "Mautic.selectSmsType('list');",
        ));
endif;