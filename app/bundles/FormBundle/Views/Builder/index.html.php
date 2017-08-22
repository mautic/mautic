<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'form');

$header = ($activeForm->getId())
    ?
    $view['translator']->trans(
        'mautic.form.form.header.edit',
        ['%name%' => $view['translator']->trans($activeForm->getName())]
    )
    :
    $view['translator']->trans('mautic.form.form.header.new');
$view['slots']->set('headerTitle', $header);

$formId = $form['sessionId']->vars['data'];

if (!isset($inBuilder)) {
    $inBuilder = false;
}

?>
<?php echo $view['form']->start($form); ?>
<div class="box-layout">
    <div class="col-md-9 height-auto bg-white">
        <div class="row">
            <div class="col-xs-12">
                <!-- tabs controls -->
                <ul class="bg-auto nav nav-tabs pr-md pl-md">
                    <li class="active"><a href="#details-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans(
                                'mautic.core.details'
                            ); ?></a></li>
                    <li id="fields-tab"><a href="#fields-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans(
                                'mautic.form.tab.fields'
                            ); ?></a></li>
                    <li id="actions-tab"><a href="#actions-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans(
                                'mautic.form.tab.actions'
                            ); ?></a></li>
                </ul>
                <!--/ tabs controls -->
                <div class="tab-content pa-md">
                    <div class="tab-pane fade in active bdr-w-0" id="details-container">
                        <div class="row">
                            <div class="col-md-6">
                                <?php
                                echo $view['form']->row($form['name']);
                                echo $view['form']->row($form['description']);
                                ?>
                            </div>
                            <div class="col-md-6">
                                <?php
                                echo $view['form']->row($form['postAction']);
                                echo $view['form']->row($form['postActionProperty']);
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade bdr-w-0" id="fields-container">
                        <?php echo $view->render('MauticFormBundle:Builder:style.html.php'); ?>
                        <div id="mauticforms_fields">
                            <div class="row">
                                <div class="available-fields mb-md col-sm-4">
                                    <select class="chosen form-builder-new-component" data-placeholder="<?php echo $view['translator']->trans('mautic.form.form.component.fields'); ?>">
                                        <option value=""></option>
                                        <?php foreach ($fields as $fieldType => $field): ?>

                                            <option data-toggle="ajaxmodal"
                                                    data-target="#formComponentModal"
                                                    data-href="<?php echo $view['router']->path(
                                                        'mautic_formfield_action',
                                                        [
                                                            'objectAction' => 'new',
                                                            'type'         => $fieldType,
                                                            'tmpl'         => 'field',
                                                            'formId'       => $formId,
                                                            'inBuilder'    => $inBuilder,
                                                        ]
                                                    ); ?>">
                                                <?php echo $field; ?>
                                            </option>
                                        <?php endforeach; ?>

                                    </select>
                                </div>
                            </div>
                            <div class="drop-here">
                            <?php foreach ($formFields as $field): ?>
                                <?php if (!in_array($field['id'], $deletedFields)) : ?>
                                    <?php if (!empty($field['isCustom'])):
                                        $params   = $field['customParameters'];
                                        $template = $params['template'];
                                    else:
                                        $template = 'MauticFormBundle:Field:'.$field['type'].'.html.php';
                                    endif; ?>
                                    <?php echo $view->render(
                                        'MauticFormBundle:Builder:fieldwrapper.html.php',
                                        [
                                            'template'      => $template,
                                            'field'         => $field,
                                            'inForm'        => true,
                                            'id'            => $field['id'],
                                            'formId'        => $formId,
                                            'contactFields' => $contactFields,
                                            'inBuilder'     => $inBuilder,
                                        ]
                                    ); ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            </div>
                            <?php if (!count($formFields)): ?>
                            <div class="alert alert-info" id="form-field-placeholder">
                                <p><?php echo $view['translator']->trans('mautic.form.form.addfield'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="tab-pane fade bdr-w-0" id="actions-container">
                        <div id="mauticforms_actions">
                            <div class="row">
                                <div class="available-actions mb-md col-sm-4">
                                    <select class="chosen form-builder-new-component" data-placeholder="<?php echo $view['translator']->trans('mautic.form.form.component.submitactions'); ?>">
                                        <option value=""></option>
                                        <?php foreach ($actions as $group => $groupActions): ?>
                                            <?php
                                                $campaignActionFound = false;
                                                $actionOptions       = '';
                                                foreach ($groupActions as $k => $e):
                                                    $actionOptions .= $view->render(
                                                        'MauticFormBundle:Action:option.html.php',
                                                        [
                                                            'action'       => $e,
                                                            'type'         => $k,
                                                            'isStandalone' => $activeForm->isStandalone(),
                                                            'formId'       => $form['sessionId']->vars['data'],
                                                        ]
                                                    )."\n\n";
                                                if (!empty($e['allowCampaignForm'])) {
                                                    $campaignActionFound = true;
                                                }
                                                endforeach;
                                            $class = (empty($campaignActionFound)) ? ' action-standalone-only' : '';
                                            if (!$campaignActionFound && !$activeForm->isStandalone()) {
                                                $class .= ' hide';
                                            }
                                            ?>
                                            <optgroup class=<?php echo $class; ?> label="<?php echo $view['translator']->trans($group); ?>"></optgroup>
                                            <?php echo $actionOptions; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="drop-here">
                            <?php foreach ($formActions as $action): ?>
                                <?php if (!in_array($action['id'], $deletedActions)) : ?>
                                    <?php $template = (isset($actionSettings[$action['type']]['template']))
                                        ? $actionSettings[$action['type']]['template']
                                        :
                                        'MauticFormBundle:Action:generic.html.php';
                                    $action['settings'] = $actionSettings[$action['type']];
                                    echo $view->render(
                                        $template,
                                        [
                                            'action' => $action,
                                            'inForm' => true,
                                            'id'     => $action['id'],
                                            'formId' => $formId,
                                        ]
                                    ); ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            </div>
                            <?php if (!count($formActions)): ?>
                            <div class="alert alert-info" id="form-action-placeholder">
                                <p><?php echo $view['translator']->trans('mautic.form.form.addaction'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto bdr-l">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php
            echo $view['form']->row($form['category']);
            echo $view['form']->row($form['isPublished']);
            echo $view['form']->row($form['publishUp']);
            echo $view['form']->row($form['publishDown']);
            echo $view['form']->row($form['inKioskMode']);
            echo $view['form']->row($form['renderStyle']);
            echo $view['form']->row($form['template']);
            ?>
        </div>
    </div>
</div>
<?php

echo $view['form']->end($form);

if ($activeForm->getFormType() === null || !empty($forceTypeSelection)):
    echo $view->render(
        'MauticCoreBundle:Helper:form_selecttype.html.php',
        [
            'item'       => $activeForm,
            'mauticLang' => [
                'newStandaloneForm' => 'mautic.form.type.standalone.header',
                'newCampaignForm'   => 'mautic.form.type.campaign.header',
            ],
            'typePrefix'         => 'form',
            'cancelUrl'          => 'mautic_form_index',
            'header'             => 'mautic.form.type.header',
            'typeOneHeader'      => 'mautic.form.type.campaign.header',
            'typeOneIconClass'   => 'fa-cubes',
            'typeOneDescription' => 'mautic.form.type.campaign.description',
            'typeOneOnClick'     => "Mautic.selectFormType('campaign');",
            'typeTwoHeader'      => 'mautic.form.type.standalone.header',
            'typeTwoIconClass'   => 'fa-list',
            'typeTwoDescription' => 'mautic.form.type.standalone.description',
            'typeTwoOnClick'     => "Mautic.selectFormType('standalone');",
        ]
    );
endif;

$view['slots']->append(
    'modal',
    $this->render(
        'MauticCoreBundle:Helper:modal.html.php',
        [
            'id'            => 'formComponentModal',
            'header'        => false,
            'footerButtons' => true,
        ]
    )
);
?>
