<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use \MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use \MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use \MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormFeatureSettingsInterface;

echo $view['assets']->includeScript('plugins/IntegrationsBundle/Assets/js/integrations.js', 'integrationsConfigOnLoad', 'integrationsConfigOnLoad');

/** @var \MauticPlugin\IntegrationsBundle\Integration\Interfaces\IntegrationInterface $integrationObject Set through buildView */

$activeTab = $activeTab ?: 'details-container';
$showFeaturesTab =
    $integrationObject instanceof ConfigFormFeaturesInterface ||
    $integrationObject instanceof ConfigFormSyncInterface ||
    $integrationObject instanceof ConfigFormFeatureSettingsInterface;
$hasFeatureErrors =
    ($integrationObject instanceof ConfigFormFeatureSettingsInterface && $view['form']->containsErrors($form['featureSettings']['integration'])) ||
    ($integrationObject instanceof ConfigFormSyncInterface && $view['form']->containsErrors($form['featureSettings']['sync'])) ||
    (isset($form['featureSettings']['sync']['integration']) && $view['form']->containsErrors($form['featureSettings']['sync']['integration']));
?>

<?php echo $view['form']->start($form); ?>
<ul class="nav nav-tabs">
    <!-- Enabled\Auth -->
    <li class="<?php if ($activeTab == 'details-container'): echo 'active'; endif; ?> " id="details-tab">
        <a href="#details-container" role="tab" data-toggle="tab">
            <?php echo $view['translator']->trans('mautic.plugin.integration.tab.details'); ?>
        </a>
    </li>
    <!-- Enabled\Auth -->

    <!-- Features -->
    <?php if ($showFeaturesTab): ?>
        <li class="" id="features-tab">
            <a href="#features-container" role="tab" data-toggle="tab">
                <?php echo $view['translator']->trans('mautic.plugin.integration.tab.features'); ?>
                <?php if ($hasFeatureErrors): ?>
                <i class="fa fa-fw fa-warning text-danger"></i>
                <?php endif; ?>
            </a>
        </li>
    <?php endif; ?>
    <!-- Features -->

    <!-- Field Mapping -->
    <?php if ($integrationObject instanceof ConfigFormSyncInterface): ?>
    <?php $objects = $integrationObject->getSyncConfigObjects(); ?>
        <?php foreach ($form['featureSettings']['sync']['fieldMappings'] as $object => $objectFieldMapping): ?>
        <li class="<?php if ($activeTab == "field-mapping-{$object}"): echo 'active'; endif; ?> " id="fields-<?php echo $object; ?>-tab">
            <a href="#field-mappings-<?php echo $object; ?>-container" role="tab" data-toggle="tab">
                <?php echo $view['translator']->trans('mautic.integration.sync_field_mapping', ['%object%' => $view['translator']->trans($objects[$object])]); ?>
                <?php if ($view['form']->containsErrors($objectFieldMapping)): ?>
                    <i class="fa fa-fw fa-warning text-danger"></i>
                <?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>
    <?php endif; ?>
    <!-- Field Mapping -->
</ul>

<div class="tab-content pa-md bg-white">
    <!-- Enabled\Auth -->
    <div class="tab-pane fade <?php if ($activeTab == 'details-container'): echo 'in active'; endif; ?> bdr-w-0" id="details-container">
        <?php echo $view['form']->row($form['isPublished']); ?>
        <hr />
        <?php echo $view['form']->row($form['apiKeys']); ?>
    </div>
    <!-- Enabled\Auth -->

    <!-- Features -->
    <?php if ($showFeaturesTab): ?>
    <div class="tab-pane fade <?php if ($activeTab == 'features-container'): echo 'in active'; endif; ?> bdr-w-0" id="features-container">
        <?php
        if ($integrationObject instanceof ConfigFormFeaturesInterface):
            echo $view['form']->row($form['supportedFeatures']);

            if ($integrationObject instanceof ConfigFormFeatureSettingsInterface || $integrationObject instanceof ConfigFormSyncInterface):
                echo "<hr />";
            endif;
        endif;

        if ($integrationObject instanceof ConfigFormFeatureSettingsInterface):
            echo $view['form']->row($form['featureSettings']['integration']);

            if ($integrationObject instanceof ConfigFormSyncInterface):
                echo "<hr />";
            endif;
        endif;

        if ($integrationObject instanceof ConfigFormSyncInterface):
            echo $view['form']->row($form['featureSettings']['sync']['objects']);
            echo $view['form']->row($form['featureSettings']['sync']['updateBlanks']);

            if (isset($form['featureSettings']['sync']['custom'])):
                echo $view['form']->row($form['featureSettings']['sync']['integration']);
            endif;
        endif;
        ?>
    </div>
    <?php endif; ?>
    <!-- Features -->

    <!-- Field Mapping -->
    <?php if ($integrationObject instanceof ConfigFormSyncInterface): ?>
    <?php foreach ($form['featureSettings']['sync']['fieldMappings'] as $object => $objectFieldMapping): ?>
    <div class="tab-pane fade <?php if ($activeTab == "field-mapping-{$object}"): echo 'in active'; endif; ?> bdr-w-0" id="<?php echo "field-mappings-{$object}"; ?>-container">
        <?php echo $view['form']->row($objectFieldMapping['filter-keyword']); ?>

        <div id="<?php echo "field-mappings-{$object}"; ?>">
        <?php
        echo $view->render('IntegrationsBundle:Config:field_mapping.html.php',
            [
                'form'        => $form['featureSettings']['sync']['fieldMappings'][$object],
                'integration' => $integrationObject->getName(),
                'object'      => $object,
            ]
        ); ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    <!-- Field Mapping -->
</div>

<?php echo $view['form']->end($form); ?>
