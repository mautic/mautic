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
$view->addGlobal('translationBase', 'mautic.dynamicContent');
$view->addGlobal('mauticContent', 'dynamicContent');

$fields = $form->vars['fields'];
$index  = count($form['filters']->vars['value']) ? max(array_keys($form['filters']->vars['value'])) : 0;
$id     = $form->vars['data']->getId();

if (!empty($id)) {
    $name   = $form->vars['data']->getName();
    $header = $view['translator']->trans('mautic.dynamicContent.header.edit', ['%name%' => $name]);
} else {
    $header = $view['translator']->trans('mautic.dynamicContent.header.new');
}
$view['slots']->set('headerTitle', $header);

$templates = [
    'countries'      => 'country-template',
    'regions'        => 'region-template',
    'timezones'      => 'timezone-template',
    'select'         => 'select-template',
    'lists'          => 'leadlist-template',
    'deviceTypes'    => 'device_type-template',
    'deviceBrands'   => 'device_brand-template',
    'deviceOs'       => 'device_os-template',
    'emails'         => 'lead_email_received-template',
    'tags'           => 'tags-template',
    'stage'          => 'stage-template',
    'locales'        => 'locale-template',
    'globalcategory' => 'globalcategory-template',
];

$mainErrors   = ($view['form']->containsErrors($form, ['filters'])) ? 'class="text-danger"' : '';
$filterErrors = ($view['form']->containsErrors($form['filters'])) ? 'class="text-danger"' : '';
?>

<?php echo $view['form']->start($form); ?>
<div class="box-layout">
    <div class="col-md-9 bg-white height-auto">
        <div class="row">
            <div class="col-xs-12">
                <ul class="bg-auto nav nav-tabs pr-md pl-md">
                    <li class="active">
                        <a href="#details" role="tab" data-toggle="tab"<?php echo $mainErrors; ?>>
                            <?php echo $view['translator']->trans('mautic.core.details'); ?>
                            <?php if ($mainErrors): ?>
                                <i class="fa fa-warning"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="<?php echo ($form->vars['value']->isCampaignBased() || isset($form['updateSelect'])) ? 'hide' : ''; ?>" id="dwcFiltersTab">
                        <a href="#filters" role="tab" data-toggle="tab"<?php echo $filterErrors; ?>>
                            <?php echo $view['translator']->trans('mautic.core.filters'); ?>
                            <?php if ($filterErrors): ?>
                                <i class="fa fa-warning"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>

                <!-- start: tab-content -->
                <div class="tab-content pa-md">
                    <div class="tab-pane fade in active bdr-w-0" id="details">
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['name']); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <?php echo $view['form']->row($form['content']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade bdr-w-0" id="filters">
                        <?php if ($filterErrors && '' !== $view['form']->errors($form['filters'])) : ?>
                            <div class="has-error alert alert-danger" role="alert" style="padding:5px 10px 0 10px;">
                                <?php echo $view['form']->errors($form['filters']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="dwc-filter bdr-w-0" id="<?php echo $form->vars['id'] ?>">
                            <div class="row">
                                <div class="col-xs-7">
                                    <label><?php echo $view['translator']->trans('Filters'); ?></label>
                                </div>
                                <div class="col-xs-5">
                                    <div class="form-group">
                                        <div class="available-filters mb-md pl-0"
                                             data-prototype="<?php echo $view->escape($view['form']->widget($form['filters']->vars['prototype'])); ?>"
                                             data-index="<?php echo $index + 1; ?>">
                                            <select class="chosen form-control" id="available_filters">
                                                <option value=""></option>
                                                <?php
                                                foreach ($fields as $object => $field):
                                                    $header = $object;
                                                    $icon   = ($object == 'company') ? 'building' : 'user';
                                                    ?>
                                                    <optgroup label="<?php echo $view['translator']->trans('mautic.lead.'.$header); ?>">
                                                        <?php foreach ($field as $value => $params):
                                                            $list    = (!empty($params['properties']['list'])) ? $params['properties']['list'] : [];
                                                            $choices = \Mautic\LeadBundle\Helper\FormFieldHelper::parseList(
                                                                $list,
                                                                true,
                                                                ('boolean' === $params['properties']['type'])
                                                            );
                                                            $list     = json_encode($choices);
                                                            $callback = (!empty($params['properties']['callback']))
                                                                ? $params['properties']['callback'] : '';
                                                            $operators = (!empty($params['operators'])) ? $view->escape(
                                                                json_encode($params['operators'])
                                                            ) : '{}';
                                                            ?>
                                                            <option value="<?php echo $value; ?>"
                                                                    id="available_<?php echo $value; ?>"
                                                                    data-field-object="<?php echo $object; ?>"
                                                                    data-field-type="<?php echo $params['properties']['type']; ?>"
                                                                    data-field-list="<?php echo $view->escape($list); ?>"
                                                                    data-field-callback="<?php echo $callback; ?>"
                                                                    data-field-operators="<?php echo $operators; ?>"
                                                                    class="segment-filter <?php echo $icon; ?>">
                                                                <?php echo $view['translator']->trans($params['label']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </optgroup>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="selected-filters" id="dwc_filters" data-filter-container>
                                        <?php echo $view['form']->widget($form['filters']); ?>
                                    </div>
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
            <?php echo $view['form']->row($form['category']); ?>
            <?php echo $view['form']->row($form['language']); ?>
            <?php echo $view['form']->row($form['translationParent']); ?>
            <div id="publishStatus">
                <?php echo $view['form']->row($form['isPublished']); ?>
            </div>
            <?php if (!isset($form['updateSelect'])) : ?>
                <?php echo $view['form']->row($form['isCampaignBased']); ?>
            <?php endif ?>
            <div id="slotNameDiv" class="<?php echo $form->vars['value']->isCampaignBased() ? 'hide' : ''; ?>">
                <?php echo $view['form']->row($form['slotName']); ?>
            </div>
            <div class="hide">
                <?php echo $view['form']->row($form['publishUp']); ?>
                <?php echo $view['form']->row($form['publishDown']); ?>
                <?php echo $view['form']->rest($form); ?>
            </div>
        </div>
    </div>
</div>
<?php echo $view['form']->end($form); ?>

<div class="hide" id="templates">
    <?php foreach ($templates as $dataKey => $template): ?>
        <?php $attr = ($dataKey == 'tags') ? ' data-placeholder="'.$view['translator']->trans('mautic.lead.tags.select_or_create')
            .'" data-no-results-text="'.$view['translator']->trans('mautic.lead.tags.enter_to_create')
            .'" data-allow-add="true" onchange="Mautic.createLeadTag(this)"' : ''; ?>
        <select class="form-control not-chosen <?php echo $template; ?>" name="dwc[filters][__name__][filter]"
                id="dwc_filters___name___filter"<?php echo $attr; ?>>
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