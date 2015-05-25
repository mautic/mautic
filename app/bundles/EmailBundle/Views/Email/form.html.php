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

$contentMode = $form['contentMode']->vars['data'];
?>
    <!-- start: box layout -->
<?php echo $view['form']->start($form); ?>
    <div class="box-layout">
        <!-- container -->
        <div class="col-md-9 bg-auto height-auto">
            <div class="pa-md">
                <div class="row">
                    <div class="col-md-6">
                        <?php echo $view['form']->row($form['name']); ?>
                    </div>

                    <div class="col-md-6">
                        <?php echo $view['form']->row($form['subject']); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">

                    </div>

                    <div class="col-md-6">

                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 col-lg-6">
                        <?php echo $view['form']->row($form['description']); ?>
                    </div>

                    <div class="col-md-12 col-lg-6">
                        <div class="row">
                            <div class="form-group col-xs-12">
                                <div>
                                    <div class="pull-left">
                                        <?php echo $view['form']->label($form['plainText']); ?>
                                    </div>
                                    <div class="text-right pr-10">
                                        <i class="fa fa-spinner fa-spin ml-2 plaintext-spinner hide"></i>
                                        <a class="small" onclick="Mautic.autoGeneratePlaintext();"><?php echo $view['translator']->trans('mautic.email.plaintext.generate'); ?></a>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                                <?php echo $view['form']->widget($form['plainText']); ?>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <?php echo $view['form']->label($form['contentMode']); ?>
                            <div>
                                <?php echo $view['form']->widget($form['contentMode']); ?>
                                <button type="button" class="btn btn-primary ml-10" onclick="Mautic.launchBuilder('emailform', 'email');">
                                    <i class="fa fa-cube"></i> <?php echo $view['translator']->trans('mautic.core.builder'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="template-fields<?php echo ($contentMode == 'custom') ? ' hide' : ''; ?>">
                            <?php echo $view['form']->row($form['template']); ?>
                        </div>
                    </div>
                </div>


                <div id="customHtmlContainer" class="hide">
                    <?php echo $view['form']->row($form['customHtml']); ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 bg-white height-auto bdr-l">
            <div class="pr-lg pl-lg pt-md pb-md">
                <?php echo $view['form']->row($form['fromName']); ?>
                <?php echo $view['form']->row($form['fromAddress']); ?>
                <?php echo $view['form']->row($form['replyToAddress']); ?>
                <?php echo $view['form']->row($form['bccAddress']); ?>

                <?php if (isset($form['variantSettings'])): ?>
                    <?php echo $view['form']->row($form['variantSettings']); ?>
                    <?php echo $view['form']->row($form['isPublished']); ?>
                    <?php echo $view['form']->row($form['publishUp']); ?>
                    <?php echo $view['form']->row($form['publishDown']); ?>
                <?php else: ?>
                <?php echo $view['form']->row($form['category']); ?>
                <?php echo $view['form']->row($form['lists']); ?>
                <?php echo $view['form']->row($form['language']); ?>
                <?php echo $view['form']->row($form['isPublished']); ?>
                <?php echo $view['form']->row($form['publishUp']); ?>
                <?php echo $view['form']->row($form['publishDown']); ?>
                <?php echo $view['form']->row($form['unsubscribeForm']); ?>
                <?php endif; ?>
                <?php echo $view['form']->rest($form); ?>
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
                <div class="template-dnd-help<?php echo ($contentMode == 'custom') ? ' hide' : ''; ?>"><?php echo $view['translator']->trans('mautic.core.builder.token.help'); ?></div>
                <div class="custom-dnd-help<?php echo ($contentMode == 'custom') ? '' : ' hide'; ?>">
                    <div class="custom-drop-message hide text-center"><?php echo $view['translator']->trans('mautic.core.builder.token.drophere'); ?></div>
                    <div class="custom-general-message"><?php echo $view['translator']->trans('mautic.core.builder.token.help_custom'); ?></div>
                </div>
            </div>
        </div>
        <div class="panel-group builder-tokens" id="emailTokensPanel">
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