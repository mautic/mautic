<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'email');

$variantParent = $email->getVariantParent();
$subheader = ($variantParent) ? '<div><span class="small">' . $view['translator']->trans('mautic.email.header.editvariant', array(
    '%name%' => $email->getName(),
    '%parent%' => $variantParent->getName()
)) . '</span></div>' : '';

$header = ($email->getId()) ?
    $view['translator']->trans('mautic.email.header.edit',
        array('%name%' => $email->getName())) :
    $view['translator']->trans('mautic.email.header.new');

$view['slots']->set("headerTitle", $header.$subheader);

$emailType = $form['emailType']->vars['data'];

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
                <ul class="bg-auto nav nav-tabs pr-md pl-md">
                    <li class="active"><a href="#email-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.email.email'); ?></a></li>
                    <li class=""><a href="#advanced-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.core.advanced'); ?></a></li>
                    <li class=""><a href="#source-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.core.content'); ?></a></li>
                </ul>
                <!--/ tabs controls -->
                <div class="tab-content pa-md">
                    <div class="tab-pane fade in active bdr-w-0" id="email-container">
                        <div class="row">
                            <div class="col-md-12">
                                <?php echo $view['form']->row($form['subject']); ?>
                            </div>
                            <div class="col-md-12">
                                <?php echo $view['form']->row($form['template']); ?>
                            </div>
                        </div>
                        <?php echo $view->render('MauticCoreBundle:Helper:theme_select.html.php', array(
                            'type'   => 'email',
                            'themes' => $themes,
                            'active' => $form['template']->vars['value']
                        )); ?>
                    </div>

                    <div class="tab-pane fade bdr-w-0" id="advanced-container">
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['fromName']); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['fromAddress']); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['replyToAddress']); ?>
                            </div>

                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['bccAddress']); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="pull-left">
                                    <?php echo $view['form']->label($form['assetAttachments']); ?>
                                </div>
                                <div class="text-right pr-10">
                                    <span class="label label-info" id="attachment-size"><?php echo $attachmentSize; ?></span>
                                </div>
                                <div class="clearfix"></div>
                                <?php echo $view['form']->widget($form['assetAttachments']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade bdr-w-0" id="source-container">
                        <div class="row">
                            <div class="col-md-12" id="customHtmlContainer" style="min-height: 325px;">
                                <?php echo $view['form']->row($form['customHtml']); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="pull-left">
                                    <?php echo $view['form']->label($form['plainText']); ?>
                                </div>
                                <div class="text-right pr-10">
                                    <i class="fa fa-spinner fa-spin ml-2 plaintext-spinner hide"></i>
                                    <a class="small" onclick="Mautic.autoGeneratePlaintext();"><?php echo $view['translator']->trans('mautic.email.plaintext.generate'); ?></a>
                                </div>
                                <div class="clearfix"></div>
                                <?php echo $view['form']->widget($form['plainText']); ?>
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
            <?php if ($isVariant): ?>
                <?php echo $view['form']->row($form['variantSettings']); ?>
                <?php echo $view['form']->row($form['isPublished']); ?>
                <?php echo $view['form']->row($form['publishUp']); ?>
                <?php echo $view['form']->row($form['publishDown']); ?>
            <?php else: ?>
            <div id="leadList"<?php echo ($emailType == 'template') ? ' class="hide"' : ''; ?>>
                <?php echo $view['form']->row($form['lists']); ?>
            </div>
            <?php echo $view['form']->row($form['category']); ?>
            <?php echo $view['form']->row($form['language']); ?>
            <div id="publishStatus"<?php echo ($emailType == 'list') ? ' class="hide"' : ''; ?>>
                <?php echo $view['form']->row($form['isPublished']); ?>
                <?php echo $view['form']->row($form['publishUp']); ?>
                <?php echo $view['form']->row($form['publishDown']); ?>
            </div>
            <?php endif; ?>

            <?php echo $view['form']->row($form['unsubscribeForm']); ?>

            <div class="hide">
                <?php echo $view['form']->rest($form); ?>
            </div>
        </div>
    </div>
</div>
<?php echo $view['form']->end($form); ?>

<?php echo $view->render('MauticCoreBundle:Helper:builder.html.php', array(
    'type'          => 'email',
    'sectionForm'   => $sectionForm,
    'builderAssets' => $builderAssets,
    'slots'         => $slots,
    'objectId'      => $email->getSessionId()
)); ?>

<?php
$type = $email->getEmailType();
if (empty($type) || !empty($forceTypeSelection)):
    echo $view->render('MauticCoreBundle:Helper:form_selecttype.html.php',
        array(
            'item'               => $email,
            'mauticLang'         => array(
                'newListEmail' => 'mautic.email.type.list.header',
                'newTemplateEmail'   => 'mautic.email.type.template.header'
            ),
            'typePrefix'         => 'email',
            'cancelUrl'          => 'mautic_email_index',
            'header'             => 'mautic.email.type.header',
            'typeOneHeader'      => 'mautic.email.type.template.header',
            'typeOneIconClass'   => 'fa-cube',
            'typeOneDescription' => 'mautic.email.type.template.description',
            'typeOneOnClick'     => "Mautic.selectEmailType('template');",
            'typeTwoHeader'      => 'mautic.email.type.list.header',
            'typeTwoIconClass'   => 'fa-pie-chart',
            'typeTwoDescription' => 'mautic.email.type.list.description',
            'typeTwoOnClick'     => "Mautic.selectEmailType('list');",
        ));
endif;