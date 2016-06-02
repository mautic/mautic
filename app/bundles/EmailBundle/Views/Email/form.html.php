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

$template  = $form['template']->vars['data'];
$emailType = $form['emailType']->vars['data'];

$view['assets']->addScriptDeclaration('var mauticEntityId = '.$email->getSessionId().';');

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
                    <li class=""><a href="#source-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.core.source'); ?></a></li>
                </ul>
                <!--/ tabs controls -->
                <div class="tab-content pa-md">
                    <div class="tab-pane fade in active bdr-w-0" id="email-container">
                        <div class="row">
                            <div class="col-md-8">
                                <?php echo $view['form']->row($form['subject']); ?>
                            </div>

                            <div class="col-md-4">
                                <?php echo $view['form']->row($form['template']); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="custom-html-mask<?php if (!$template) echo ' hide'; ?>">
                                <div class="well text-center col-md-6 col-md-offset-3">
                                    <h3 style="padding: 30px;">
                                        <a href="javascript: void(0);" onclick="Mautic.launchBuilder('emailform', 'email');">
                                            <?php echo $view['translator']->trans('mautic.core.builder.launch'); ?> <i class="fa fa-angle-right"></i>
                                        </a>
                                    </h3>
                                </div>
                            </div>
                        </div>
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

<div class="hide builder email-builder">
    <div class="builder-content">
        <input type="hidden" id="builder_url" value="<?php echo $view['router']->generate('mautic_email_action', array('objectAction' => 'builder', 'objectId' => $email->getSessionId())); ?>" />
    </div>
    <div class="builder-panel">
        <div class="builder-panel-top">
            <p>
                <button type="button" class="btn btn-primary btn-close-builder" onclick="Mautic.closeBuilder('email');"><?php echo $view['translator']->trans('mautic.core.close.builder'); ?></button>
            </p>
            <div class="well well-small mb-10" id="customHtmlDropzone">
                <div class="template-dnd-help<?php echo (!$template) ? ' hide' : ''; ?>"><?php echo $view['translator']->trans('mautic.core.builder.token.help'); ?></div>
                <div class="custom-dnd-help<?php echo (!$template) ? '' : ' hide'; ?>">
                    <div class="custom-drop-message hide text-center"><?php echo $view['translator']->trans('mautic.core.builder.token.drophere'); ?></div>
                    <div class="custom-general-message"><?php echo $view['translator']->trans('mautic.core.builder.token.help_custom'); ?></div>
                </div>
            </div>
        </div>
        <div class="panel-group builder-tokens" id="emailTokensPanel">
            <div class="panel-group builder-slots" id="emailSlotsPanel">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#slot-type-container" role="tab" data-toggle="tab">Content Slots</a></li>
                <li class=""><a href="#customize-form-container" role="tab" data-toggle="tab">Customize</a></li>
            </ul>
            <div class="bg-auto tab-content pa-md">
                <div class="tab-pane fade bdr-w-0 active in" id="slot-type-container">
                    <?php if ($slots): ?>
                        <?php foreach ($slots as $slotKey => $slot): ?>
                            <div class="slot-type-handle btn btn-default btn-lg btn-block" data-slot-type="<?php echo $slotKey; ?>">
                                <i class="fa fa-<?php echo $slot['icon']; ?>" aria-hidden="true"></i>
                                <?php echo $slot['header']; ?>
                                <script type="text/html">
                                    <?php echo $view->render($slot['content']); ?>
                                </script>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <p class="text-muted pt-md text-center"><i>Drag the slot to the desired position.</i></p>
                </div>
                <div class="tab-pane fade bdr-w-0" id="customize-form-container">
                    <div id="slot-form-container"></div>
                    <?php if ($slots): ?>
                        <?php foreach ($slots as $slotKey => $slot): ?>
                            <script type="text/html" data-slot-type-form="<?php echo $slotKey; ?>">
                                <?php echo $view['form']->start($slot['form']); ?>
                                <?php echo $view['form']->end($slot['form']); ?>
                            </script>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
            <?php foreach ($tokens as $k => $t): ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><?php echo $t['header']; ?></h4>
                    </div>
                    <div class="panel-body">
                        <?php echo $t['content']; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php echo $view->render('MauticCoreBundle:Helper:buildermodal_feedback.html.php'); ?>
            <?php echo $view->render('MauticCoreBundle:Helper:buildermodal_link.html.php'); ?>
        </div>
    </div>
</div>

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