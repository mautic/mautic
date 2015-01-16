<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$leadFields  = (isset($form['featureSettings']) && isset($form['featureSettings']['leadFields'])) ? $view['form']->row($form['featureSettings']['leadFields']) : '';
$hasFeatures = (isset($form['supportedFeatures']) && count($form['supportedFeatures']));
?>

<ul class="nav nav-tabs pr-md pl-md">
    <li class="active"><a href="#details-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.addon.integration.tab.details'); ?></a></li>
    <?php if ($hasFeatures): ?>
    <li class=""><a href="#features-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.addon.integration.tab.features'); ?></a></li>
    <?php endif; ?>
    <?php if ($leadFields): ?>
    <li class=""><a href="#fields-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.addon.integration.tab.fieldmapping'); ?></a></li>
    <?php endif; ?>
</ul>

<?php echo $view['form']->start($form); ?>
<!--/ tabs controls -->
<div class="tab-content pa-md bg-white">
    <div class="tab-pane fade in active bdr-w-0" id="details-container">
        <?php echo $view['form']->row($form['isPublished']); ?>
        <?php echo $view['form']->row($form['apiKeys']); ?>
        <?php if (isset($form['authButton'])): ?>
        <?php list($specialInstructions, $alertType) = $integration->getFormNotes('authorization'); ?>
        <?php if (!empty($specialInstructions)): ?>
            <div class="alert alert-<?php echo $alertType; ?>">
                <?php echo $view['translator']->trans($specialInstructions); ?>
            </div>
        <?php endif; ?>
        <?php if (strpos($integration->getAuthenticationType(), 'oauth') !== false): ?>
        <div class="well well-sm">
            <?php echo $view['translator']->trans('mautic.integration.callbackuri'); ?><br />
            <input type="text" readonly value="<?php echo $integration->getOauthCallbackUrl(); ?>" class="form-control" />
        </div>
        <?php endif; ?>
        <?php echo $view['form']->row($form['authButton']); ?>
        <?php endif; ?>
    </div>

    <?php if ($hasFeatures): ?>
    <div class="tab-pane fade bdr-w-0" id="features-container">
        <h4 class="mb-sm"><?php echo $view['translator']->trans($form['supportedFeatures']->vars['label']); ?></h4>
        <?php list($specialInstructions, $alertType) = $integration->getFormNotes('features'); ?>
        <?php if (!empty($specialInstructions)): ?>
            <div class="alert alert-<?php echo $alertType; ?>">
                <?php echo $view['translator']->trans($specialInstructions); ?>
            </div>
        <?php endif; ?>

        <?php echo $view['form']->row($form['supportedFeatures']); ?>
        <?php $featureSettings = count($form['featureSettings']->children); ?>
        <?php if ($featureSettings > 1 || ($featureSettings === 1 && !$leadFields)): ?>
        <h4 class="mb-sm mt-lg"><?php echo $view['translator']->trans($form['featureSettings']->vars['label']); ?></h4>
        <?php echo $view['form']->row($form['featureSettings']); ?>
        <?php else: ?>
        <?php $form['featureSettings']->setRendered(); ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($leadFields): ?>
    <div class="tab-pane fade bdr-w-0" id="fields-container">
        <h4 class="mb-sm"><?php echo $view['translator']->trans($form['featureSettings']['leadFields']->vars['label']); ?></h4>
        <?php list($specialInstructions, $alertType) = $integration->getFormNotes('field_match'); ?>
        <?php if (!empty($specialInstructions)): ?>
        <div class="alert alert-<?php echo $alertType; ?>">
            <?php echo $view['translator']->trans($specialInstructions); ?>
        </div>
        <?php endif; ?>
        <?php echo $leadFields; ?>
    </div>
    <?php endif; ?>
</div>

<?php echo $view['form']->end($form); ?>
