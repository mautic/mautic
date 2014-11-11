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
<?php echo $view['form']->start($form); ?>
<div class="row">
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <?php echo $header; ?>
                </h3>
            </div>
            <div class="panel-body">
                <?php echo $view['form']->row($form['subject']); ?>
                <?php echo $view['form']->row($form['plainText']); ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <?php echo $view['form']->end($form); ?>
        <div class="hide builder email-builder">
            <div class="builder-content">
                <input type="hidden" id="EmailBuilderUrl" value="<?php echo $view['router']->generate('mautic_email_action', array('objectAction' => 'builder', 'objectId' => $email->getSessionId())); ?>" />
            </div>
            <div class="builder-panel">
                <p>
                    <button type="button" class="btn btn-primary btn-close-builder" onclick="Mautic.closeEmailEditor();"><?php echo $view['translator']->trans('mautic.email.builder.close'); ?></button>
                </p>
                <div class="well well-sm margin-md-top"><em><?php echo $view['translator']->trans('mautic.email.token.help'); ?></em></div>
                <div class="panel-group" id="email_tokens">
                    <?php foreach ($tokens as $k => $t): ?>
                    <?php $id = \Mautic\CoreBundle\Helper\InputHelper::alphanum($k); ?>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <?php echo $t['header']; ?>
                            </h4>
                        </div>
                        <div class="panel-body">
                            <?php echo $t['content']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>