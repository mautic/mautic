<?php declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

echo $view['assets']->includeScript('app/bundles/IntegrationsBundle/Assets/js/integrations.js', 'integrationsConfigOnLoad', 'integrationsConfigOnLoad');

/** @var \Mautic\IntegrationsBundle\Integration\Interfaces\IntegrationInterface $integrationObject Set through buildView */
$activeTab = $activeTab ?: 'details-container';
?>

<?php echo $view['form']->start($form); ?>
<ul class="nav nav-tabs">
    <!-- Enabled\Auth -->
    <li class="<?php if ('details-container' === $activeTab): echo 'active'; endif; ?> " id="details-tab">
        <a href="#details-container" role="tab" data-toggle="tab">
            <?php echo $view['translator']->trans('mautic.plugin.integration.tab.details'); ?>
            <?php if ($hasAuthErrors): ?>
                <i class="fa fa-fw fa-warning text-danger"></i>
            <?php endif; ?>
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
    <?php if ($useSyncFeatures): ?>
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
    <div class="tab-pane fade <?php if ('details-container' == $activeTab): echo 'in active'; endif; ?> bdr-w-0" id="details-container">
        <?php echo $view['form']->row($form['isPublished']); ?>
        <?php if ($integrationObject instanceof \Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface): ?>
        <hr />
        <?php echo $view['form']->row($form['apiKeys']); ?>
            <?php if ($useAuthorizationUrl): ?>
            <div class="alert alert-warning">
                <?php echo $view['translator']->trans($integrationObject->getCallbackHelpMessageTranslationKey()); ?>
            </div>
            <?php if ($callbackUrl): ?>
            <div class="well well-sm">
                <?php echo $view['translator']->trans('mautic.integration.callbackuri'); ?><br/>
                <input type="text" name="callback_url" readonly onclick="this.setSelectionRange(0, this.value.length);" value="<?php echo $view->escape($callbackUrl); ?>" class="form-control"/>
            </div>
            <?php endif; ?>
            <div class="row">
                <div class="col-xs-12 text-center">
                    <input type="hidden" id="integration_details_in_auth" name="integration_details[in_auth]" autocomplete="false">
                    <button type="button" id="integration_details_authButton" name="integration_details[authButton]" class="btn btn-success btn-lg" onclick="Mautic.authorizeIntegration()">
                        <i class="fa fa-key "></i>
                        <?php if ($integrationObject->isAuthorized()): ?>
                            <?php echo $view['translator']->trans('mautic.integration.form.reauthorize'); ?>
                        <?php else: ?>
                            <?php echo $view['translator']->trans('mautic.integration.form.authorize'); ?>
                        <?php endif; ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <!-- Enabled\Auth -->

    <!-- Features -->
    <?php if ($showFeaturesTab): ?>
    <div class="tab-pane fade <?php if ('features-container' == $activeTab): echo 'in active'; endif; ?> bdr-w-0" id="features-container">
        <?php
        echo $view['form']->rowIfExists($form, 'supportedFeatures');

        if ($useFeatureSettings || $useSyncFeatures):
            echo '<hr />';
        endif;

        if ($useSyncFeatures):
            echo $view['form']->row($form['featureSettings']['sync']['objects']);
            // @todo echo $view['form']->row($form['featureSettings']['sync']['updateBlanks']);

            if (isset($form['featureSettings']['sync']['integration'])):
                echo $view['form']->row($form['featureSettings']['sync']['integration']);
            endif;

            if ($useFeatureSettings):
                echo '<hr />';
            endif;
        endif;

        if ($useFeatureSettings):
            echo $view['form']->row($form['featureSettings']['integration']);
        endif;

        ?>
    </div>
    <?php endif; ?>
    <!-- Features -->

    <!-- Field Mapping -->
    <?php if ($useSyncFeatures): ?>
    <?php foreach ($form['featureSettings']['sync']['fieldMappings'] as $object => $objectFieldMapping): ?>
    <div class="tab-pane fade <?php if ($activeTab == "field-mapping-{$object}"): echo 'in active'; endif; ?> bdr-w-0" id="<?php echo "field-mappings-{$object}"; ?>-container">
        <div class="has-error">
            <?php echo $view['form']->errors($objectFieldMapping); ?>
        </div>
        <?php echo $view['form']->row($objectFieldMapping['filter-keyword']); ?>

        <div id="<?php echo "field-mappings-{$object}"; ?>">
        <?php
        echo $view->render('IntegrationsBundle:Config:field_mapping.html.php',
            [
                'form'        => $form['featureSettings']['sync']['fieldMappings'][$object],
                'integration' => $integrationObject->getName(),
                'object'      => $object,
                'page'        => 1,
            ]
        ); ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    <!-- Field Mapping -->
</div>

<?php echo $view['form']->end($form); ?>
