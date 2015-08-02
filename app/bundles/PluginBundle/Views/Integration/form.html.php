<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$formSettings  = $integration->getFormSettings();
$hasFeatures   = (isset($form['supportedFeatures']) && count($form['supportedFeatures']));
$hasFields     = (isset($form['featureSettings']) && count($form['featureSettings']['leadFields']));
$fieldHtml     = (!empty($form['featureSettings']['leadFields'])) ? $view['form']->row($form['featureSettings']['leadFields'], array('integration' => $integration)) : '';

$fieldTabClass = ($hasFields) ? '' : ' hide';
$description   = $integration->getDescription();
?>

<?php if (!empty($description)): ?>
<div class="alert alert-info">
    <?php echo $description; ?>
</div>
<?php endif; ?>
<ul class="nav nav-tabs pr-md pl-md">
    <li class="active" id="details-tab"><a href="#details-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.plugin.integration.tab.details'); ?></a></li>
    <?php if ($hasFeatures): ?>
    <li class="" id="features-tab"><a href="#features-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.plugin.integration.tab.features'); ?></a></li>
    <?php endif; ?>
    <li class="<?php echo $fieldTabClass; ?>" id="fields-tab"><a href="#fields-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.plugin.integration.tab.fieldmapping'); ?></a></li>
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
        <?php if (!empty($formSettings['requires_callback'])): ?>
        <div class="well well-sm">
            <?php echo $view['translator']->trans('mautic.integration.callbackuri'); ?><br />
            <input type="text" readonly value="<?php echo $integration->getAuthCallbackUrl(); ?>" class="form-control" />
        </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-xs-12 text-right">
                <?php echo $view['form']->widget($form['authButton']); ?>
            </div>
        </div>
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
        <?php if ($featureSettings > 1 || ($featureSettings === 1 && !isset($form['featureSettings']['leadFields']))): ?>
        <h4 class="mb-sm mt-lg"><?php echo $view['translator']->trans($form['featureSettings']->vars['label']); ?></h4>
        <?php echo $view['form']->row($form['featureSettings']); ?>
        <?php else: ?>
        <?php $form['featureSettings']->setRendered(); ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="tab-pane fade bdr-w-0" id="fields-container">
        <h4 class="mb-sm"><?php echo $view['translator']->trans($form['featureSettings']['leadFields']->vars['label']); ?></h4>
        <?php echo $fieldHtml; ?>
    </div>
</div>
<?php echo $view['form']->end($form); ?>
