<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'email');

$variantParent = $email->getVariantParent();
$subheader = ($variantParent) ? '<span class="small"> - ' . $view['translator']->trans('mautic.email.header.editvariant', array(
    '%name%' => $email->getSubject(),
    '%parent%' => $variantParent->getSubject()
)) . '</span>' : '';

$header = ($email->getId()) ?
    $view['translator']->trans('mautic.email.header.edit',
        array('%name%' => $email->getSubject())) :
    $view['translator']->trans('mautic.email.header.new');

$view['slots']->set("headerTitle", $header.$subheader);
?>

<div class="scrollable">
    <?php echo $view['form']->form($form); ?>

    <div class="hide email-builder">
        <div class="email-builder-content">
            <input type="hidden" id="EmailBuilderUrl" value="<?php echo $view['router']->generate('mautic_email_action', array('objectAction' => 'builder', 'objectId' => $email->getSessionId())); ?>" />
        </div>
        <div class="email-builder-panel">
            <button class="btn btn-warning btn-close-builder" onclick="Mautic.closeEmailEditor();"><?php echo $view['translator']->trans('mautic.email.builder.close'); ?></button>
            <div class="well well-sm margin-md-top"><em><?php echo $view['translator']->trans('mautic.email.token.help'); ?></em></div>
            <div class="panel-group margin-sm-top" id="email_tokens">
                <?php foreach ($tokens as $k => $t): ?>
                <?php $id = \Mautic\CoreBundle\Helper\InputHelper::alphanum($k); ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a style="display: block;" data-toggle="collapse" data-parent="#email_tokens" href="#<?php echo $id; ?>">
                                <span class="pull-left">
                                    <?php echo $t['header']; ?>
                                </span>
                                <span class="pull-right">
                                    <i class="fa fa-lg fa-fw fa-angle-down"></i>
                                </span>
                                <div class="clearfix"></div>
                            </a>
                        </h4>
                    </div>
                    <div id="<?php echo $id; ?>" class="panel-collapse collapse">
                        <div class="panel-body">
                            <?php echo $t['content']; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="footer-margin"></div>
</div>