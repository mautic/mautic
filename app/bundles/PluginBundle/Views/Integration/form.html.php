<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!$hasSupportedFeatures = (isset($form['supportedFeatures']) && count($form['supportedFeatures']))) {
    if (isset($form['supportedFeatures'])) {
        $form['supportedFeatures']->setRendered();
    }
}

if (!$hasFields = (!empty($formSettings['dynamic_contact_fields']) || isset($form['featureSettings']) && count($form['featureSettings']['leadFields']))) {
    // Unset if set to prevent features tab from showing when there's no feature to show
    unset($form['featureSettings']['leadFields']);
} else {
    $hideContactFieldTab = (!empty($formSettings['dynamic_contact_fields']) && !count($form['featureSettings']['leadFields']));
}

if (!$hasFeatureSettings = (isset($form['featureSettings'])
    && (($hasFields && count($form['featureSettings']) > 1)
        || (!$hasFields
            && count(
                $form['featureSettings']
            ))))
) {
    if (isset($form['featureSettings'])) {
        $form['featureSettings']->setRendered();
    }
}
$hasCompanyFields      = (isset($form['featureSettings']['companyFields']) && count($form['featureSettings']['companyFields']));
$companyFieldHtml      = ($hasCompanyFields) ? $view['form']->row($form['featureSettings']['companyFields']) : '';
$fieldHtml             = ($hasFields) ? $view['form']->row($form['featureSettings']['leadFields']) : '';
$fieldLabel            = ($hasFields) ? $form['featureSettings']['leadFields']->vars['label'] : '';
$fieldTabClass         = ($hasFields && empty($hideContactFieldTab)) ? '' : ' hide';
$hasLeadFieldErrors    = ($hasFields && $view['form']->containsErrors($form['featureSettings']['leadFields']));
$hasCompanyFieldErrors = ($hasCompanyFields && $view['form']->containsErrors($form['featureSettings']['companyFields']));

unset($form['featureSettings']['leadFields']);
unset($form['featureSettings']['companyFields']);

$hasFeatureErrors =
    ($hasSupportedFeatures && $view['form']->containsErrors($form['supportedFeatures'])) ||
    ($hasFeatureSettings && $view['form']->containsErrors($form['featureSettings']));
?>

<?php if (!empty($description)) : ?>
    <div class="alert alert-info">
        <?php echo $description; ?>
    </div>
<?php endif; ?>
<ul class="nav nav-tabs">
    <li class="<?php if (isset($activeTab) && $activeTab == 'details-container'): echo 'active'; endif; ?> " id="details-tab">
        <a href="#details-container" role="tab" data-toggle="tab">
            <?php echo $view['translator']->trans('mautic.plugin.integration.tab.details'); ?>
        </a>
    </li>
    <?php if ($hasSupportedFeatures || $hasFeatureSettings): ?>
        <li class="" id="features-tab">
            <a href="#features-container" role="tab" data-toggle="tab">
                <?php echo $view['translator']->trans('mautic.plugin.integration.tab.features'); ?>
                <?php if ($hasFeatureErrors): ?>
                <i class="fa fa-fw fa-warning text-danger"></i>
                <?php endif; ?>
            </a>
        </li>
    <?php endif; ?>
    <!--<?php if ($hasFields): ?>-->
        <li class="<?php echo $fieldTabClass; ?> <?php if (isset($activeTab) && $activeTab == 'leadFieldsContainer'): echo 'active'; endif; ?> " id="fields-tab">
            <a href="#fields-container" role="tab" data-toggle="tab">
                <?php echo $view['translator']->trans('mautic.plugin.integration.tab.fieldmapping'); ?>
                <?php if ($hasLeadFieldErrors): ?>
                    <i class="fa fa-fw fa-warning text-danger"></i>
                <?php endif; ?>
            </a>
        </li>
    <!--<?php endif; ?>-->
    <?php if (!empty($companyFieldHtml)) : ?>
        <li class="<?php echo $fieldTabClass; ?> <?php if (isset($activeTab) && $activeTab == 'companyFieldsContainer'): echo 'active'; endif; ?> " id="company-fields-tab">
            <a href="#company-fields-container" role="tab" data-toggle="tab">
                <?php echo $view['translator']->trans('mautic.plugin.integration.tab.companyfieldmapping'); ?>
                <?php if ($hasCompanyFieldErrors): ?>
                    <i class="fa fa-fw fa-warning text-danger"></i>
                <?php endif; ?>
            </a>
        </li>
    <?php endif; ?>
</ul>

<?php echo $view['form']->start($form); ?>
<!--/ tabs controls -->
<div class="tab-content pa-md bg-white">
    <div class="tab-pane fade <?php if (isset($activeTab) && $activeTab == 'details-container'): echo 'in active'; endif; ?> bdr-w-0" id="details-container">
        <?php echo $view['form']->row($form['isPublished']); ?>
        <?php echo $view['form']->rowIfExists($form, 'virtual'); ?>
        <?php echo $view['form']->row($form['apiKeys']); ?>
        <?php if (isset($formNotes['authorization'])): ?>
            <div class="alert alert-<?php echo $formNotes['authorization']['type']; ?>">
                <?php echo $view['translator']->trans($formNotes['authorization']['note']); ?>
            </div>
        <?php endif; ?>
        <?php if (count($form['apiKeys']) && !empty($callbackUrl)): ?>
            <div class="well well-sm">
                <?php echo $view['translator']->trans('mautic.integration.callbackuri'); ?><br/>
                <input type="text" readonly onclick="this.setSelectionRange(0, this.value.length);" value="<?php echo $callbackUrl; ?>" class="form-control"/>
            </div>
        <?php endif; ?>
        <?php if (isset($form['authButton'])): ?>
            <div class="row">
                <div class="col-xs-12 text-center">
                    <?php
                    $attr          = $form['authButton']->vars['attr'];
                    $attr['class'] = 'btn btn-success btn-lg';
                    echo $view['form']->widget($form['authButton'], ['attr' => $attr]);
                    ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if (isset($formNotes['custom'])):
            if (is_string($formNotes['custom'])):
                echo $formNotes['custom'];
            elseif (!empty(isset($formNotes['custom']['template']))):
                $template = $formNotes['custom']['template'];
                $params   = isset($formNotes['custom']['parameters']) ? $formNotes['custom']['parameters'] : [];

                echo $this->render($template, $params);
            endif;
        endif;
        ?>
    </div>

    <?php if ($hasSupportedFeatures || $hasFeatureSettings): ?>
        <div class="tab-pane fade bdr-w-0" id="features-container">
            <?php if ($hasSupportedFeatures): ?>
                <?php echo $view['form']->row($form['supportedFeatures'], ['formSettings' => $formSettings, 'formNotes' => $formNotes]); ?>
            <?php endif; ?>
            <?php if ($hasFeatureSettings): ?>
                <?php echo $view['form']->row($form['featureSettings'], ['formSettings' => $formSettings, 'formNotes' => $formNotes]); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($hasFields): ?>
        <div class="tab-pane fade <?php if (isset($activeTab) && $activeTab == 'leadFieldsContainer'): echo 'in active'; endif; ?>  bdr-w-0" id="fields-container">
            <h4 class="mb-sm"><?php echo $view['translator']->trans($fieldLabel); ?></h4>
            <?php echo $fieldHtml; ?>
        </div>
    <?php endif; ?>
    <?php if ($hasCompanyFields): ?>
    <div class="tab-pane fade <?php if (isset($activeTab) && $activeTab == 'companyFieldsContainer'): echo 'in active'; endif; ?> bdr-w-0" id="company-fields-container">
        <h4 class="mb-sm"><?php echo $view['translator']->trans('mautic.integration.companyfield_matches'); ?></h4>
        <?php echo $companyFieldHtml; ?>
    </div>
    <?php endif; ?>
</div>
<?php echo $view['form']->end($form); ?>
