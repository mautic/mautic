<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\Form\FormView;

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'email');

$dynamicContentPrototype = $form['dynamicContent']->vars['prototype'];

if (empty($form['dynamicContent']->children[0]['filters']->vars['prototype'])) {
    $filterBlockPrototype = null;
} else {
    $filterBlockPrototype = $form['dynamicContent']->children[0]['filters']->vars['prototype'];
}

if (empty($form['dynamicContent']->children[0]['filters']->children[0]['filters']->vars['prototype'])) {
    $filterSelectPrototype = null;
} else {
    $filterSelectPrototype = $form['dynamicContent']->children[0]['filters']->children[0]['filters']->vars['prototype'];
}

$variantParent = $email->getVariantParent();
$isExisting    = $email->getId();

$subheader = ($variantParent) ? '<div><span class="small">'.$view['translator']->trans('mautic.core.variant_of', [
    '%name%'   => $email->getName(),
    '%parent%' => $variantParent->getName(),
]).'</span></div>' : '';

$header = $isExisting ?
    $view['translator']->trans('mautic.email.header.edit',
        ['%name%' => $email->getName()]) :
    $view['translator']->trans('mautic.email.header.new');

$view['slots']->set('headerTitle', $header.$subheader);

$emailType = $form['emailType']->vars['data'];

if (!isset($attachmentSize)) {
    $attachmentSize = 0;
}

$templates = [
    'select'    => 'select-template',
    'countries' => 'country-template',
    'regions'   => 'region-template',
    'timezones' => 'timezone-template',
    'stages'    => 'stage-template',
    'locales'   => 'locale-template',
];

$attr = $form->vars['attr'];

$isCodeMode = ($email->getTemplate() === 'mautic_code_mode');

?>

<?php echo $view['form']->start($form, ['attr' => $attr]); ?>
<div class="box-layout">
    <div class="col-md-9 height-auto bg-white">
        <div class="row">
            <div class="col-xs-12">
                <!-- tabs controls -->
                <ul class="bg-auto nav nav-tabs pr-md pl-md">
                    <li class="active">
                        <a href="#email-container" role="tab" data-toggle="tab">
                            <?php echo $view['translator']->trans('mautic.core.form.theme'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#advanced-container" role="tab" data-toggle="tab">
                            <?php echo $view['translator']->trans('mautic.core.advanced'); ?>
                        </a>
                    </li>
                    <li id="dynamic-content-tab" <?php echo (!$isCodeMode) ? 'class="hidden"' : ''; ?>>
                        <a href="#dynamic-content-container" role="tab" data-toggle="tab">
                            <?php echo $view['translator']->trans('mautic.core.dynamicContent'); ?>
                        </a>
                    </li>
                </ul>
                <!--/ tabs controls -->
                <div class="tab-content pa-md">
                    <div class="tab-pane fade in active bdr-w-0" id="email-container">
                        <div class="row">
                            <div class="col-md-12">
                                <?php echo $view['form']->row($form['template']); ?>
                            </div>
                        </div>
                        <?php echo $view->render('MauticCoreBundle:Helper:theme_select.html.php', [
                            'type'   => 'email',
                            'themes' => $themes,
                            'active' => $form['template']->vars['value'],
                        ]); ?>
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

                        <br>
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
                    <div class="tab-pane fade bdr-w-0" id="dynamic-content-container">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                <?php
                                $tabHtml = '<div class="col-xs-3 dynamicContentFilterContainer">';
                                $tabHtml .= '<ul class="nav nav-tabs tabs-left" id="dynamicContentTabs">';
                                $tabHtml .= '<li><a href="javascript:void(0);" role="tab" class="btn btn-primary" id="addNewDynamicContent"><i class="fa fa-plus text-success"></i> '.$view['translator']->trans('mautic.core.form.new').'</a></li>';
                                $tabContentHtml = '<div class="tab-content pa-md col-xs-9" id="dynamicContentContainer">';

                                foreach ($form['dynamicContent'] as $i => $dynamicContent) {
                                    $linkText = $dynamicContent['tokenName']->vars['value'] ?: $view['translator']->trans('mautic.core.dynamicContent').' '.($i + 1);

                                    $tabHtml .= '<li class="'.($i === 0 ? ' active' : '').'"><a role="tab" data-toggle="tab" href="#'.$dynamicContent->vars['id'].'">'.$linkText.'</a></li>';

                                    $tabContentHtml .= $view['form']->widget($dynamicContent);
                                }

                                $tabHtml .= '</ul></div>';
                                $tabContentHtml .= '</div>';

                                echo $tabHtml;
                                echo $tabContentHtml;
                                ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto bdr-l">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php echo $view['form']->row($form['subject']); ?>
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
            <div id="segmentTranslationParent"<?php echo ($emailType == 'template') ? ' class="hide"' : ''; ?>>
                <?php echo $view['form']->row($form['segmentTranslationParent']); ?>
            </div>
            <div id="templateTranslationParent"<?php echo ($emailType == 'list') ? ' class="hide"' : ''; ?>>
                <?php echo $view['form']->row($form['templateTranslationParent']); ?>
            </div>
            <?php endif; ?>

            <?php if (!$isVariant): ?>
                <?php echo $view['form']->row($form['isPublished']); ?>
                <?php echo $view['form']->row($form['publishUp']); ?>
                <?php echo $view['form']->row($form['publishDown']); ?>
            <?php endif; ?>

            <?php echo $view['form']->row($form['unsubscribeForm']); ?>
            <hr />
            <h5><?php echo $view['translator']->trans('mautic.email.utm_tags'); ?></h5>
            <br />
            <?php
            foreach ($form['utmTags'] as $i => $utmTag):
                echo $view['form']->row($utmTag);
            endforeach;
            ?>
        </div>
        <div class="hide">
            <?php echo $view['form']->rest($form); ?>
        </div>
    </div>
</div>

<?php echo $view['form']->row($form['customHtml']); ?>
<?php echo $view['form']->end($form); ?>

<div id="dynamicContentPrototype" data-prototype="<?php echo $view->escape($view['form']->widget($dynamicContentPrototype)); ?>"></div>
<?php if ($filterBlockPrototype instanceof FormView) : ?>
<div id="filterBlockPrototype" data-prototype="<?php echo $view->escape($view['form']->widget($filterBlockPrototype)); ?>"></div>
<?php endif; ?>
<?php if ($filterSelectPrototype instanceof FormView) : ?>
<div id="filterSelectPrototype" data-prototype="<?php echo $view->escape($view['form']->widget($filterSelectPrototype)); ?>"></div>
<?php endif; ?>

<div class="hide" id="templates">
    <?php foreach ($templates as $dataKey => $template): ?>
        <?php $attr = ($dataKey == 'tags') ? ' data-placeholder="'.$view['translator']->trans('mautic.lead.tags.select_or_create').'" data-no-results-text="'.$view['translator']->trans('mautic.lead.tags.enter_to_create').'" data-allow-add="true" onchange="Mautic.createLeadTag(this)"' : ''; ?>
        <select class="form-control not-chosen <?php echo $template; ?>" name="emailform[dynamicContent][__dynamicContentIndex__][filters][__dynamicContentFilterIndex__][filters][__name__][filter]" id="emailform_dynamicContent___dynamicContentIndex___filters___dynamicContentFilterIndex___filters___name___filter"<?php echo $attr; ?>>
            <?php
            if (isset($form->vars[$dataKey])):
                foreach ($form->vars[$dataKey] as $value => $label):
                    if (is_array($label)):
                        echo "<optgroup label=\"$value\">\n";
                        foreach ($label as $optionValue => $optionLabel):
                            echo "<option value=\"$optionValue\">$optionLabel</option>\n";
                        endforeach;
                        echo "</optgroup>\n";
                    else:
                        if ($dataKey == 'lists' && (isset($currentListId) && (int) $value === (int) $currentListId)) {
                            continue;
                        }
                        echo "<option value=\"$value\">$label</option>\n";
                    endif;
                endforeach;
            endif;
            ?>
        </select>
    <?php endforeach; ?>
</div>

<?php echo $view->render('MauticCoreBundle:Helper:builder.html.php', [
    'type'          => 'email',
    'isCodeMode'    => $isCodeMode,
    'sectionForm'   => $sectionForm,
    'builderAssets' => $builderAssets,
    'slots'         => $slots,
    'sections'      => $sections,
    'objectId'      => $email->getSessionId(),
]); ?>

<?php
$type = $email->getEmailType();
if ((empty($updateSelect) && !$isExisting && !$view['form']->containsErrors($form) && !$variantParent) || empty($type) || !empty($forceTypeSelection)):
    echo $view->render('MauticCoreBundle:Helper:form_selecttype.html.php',
        [
            'item'       => $email,
            'mauticLang' => [
                'newListEmail'     => 'mautic.email.type.list.header',
                'newTemplateEmail' => 'mautic.email.type.template.header',
            ],
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
        ]);
endif;
