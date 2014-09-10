<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'leadlist');

$id = $form->vars['data']->getId();
if (!empty($id)) {
    $name   = $form->vars['data']->getName();
    $header = $view['translator']->trans('mautic.lead.list.header.edit', array("%name%" => $name));
} else {
    $header = $view['translator']->trans('mautic.lead.list.header.new');
}
$view['slots']->set("headerTitle", $header);

$glueOptions = array(
    'and' => 'mautic.lead.list.form.glue.and',
    'or'  => 'mautic.lead.list.form.glue.or'
);

//Generate lists for select boxes
$countries = \Symfony\Component\Intl\Intl::getRegionBundle()->getCountryNames();
$tz        = new \Symfony\Component\Form\Extension\Core\Type\TimezoneType();
$timezones = $tz->getTimezones();
?>
<div class="row">
    <div class="col-md-9">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <?php echo $view['translator']->trans('mautic.lead.list.header.index');?>
                </h3>
            </div>
        <div class="panel-body">
            <?php echo $view['form']->start($form); ?>
            <div class="row">
                <div class="col-xs-12 col-sm-6">
                    <?php echo $view['form']->row($form['name']); ?>
                    <?php echo $view['form']->row($form['alias']); ?>
                    <?php echo $view['form']->row($form['description']); ?>
                    <?php echo $view['form']->row($form['isGlobal']); ?>
                    <?php echo $view['form']->row($form['isPublished']); ?>
                </div>

                <?php
                $filterForm   = $form['filters'];
                $filterValues = $filterForm->vars['data'] ?: array();
                $form['filters']->setRendered();
                $feedbackClass = ($app->getRequest()->getMethod() == 'POST' && !empty($filterForm->vars['errors'])) ? " has-error" : "";
                ?>
            </div>
            <div class="row">
                <div class="form-group col-xs-12 col-sm-8<?php echo $feedbackClass; ?>">
                    <?php echo $view['form']->label($filterForm); ?>
                    <?php echo $view['form']->errors($filterForm); ?>
                    <div class="row">
                        <div class="col-xs-4 available-filters">
                            <h4><?php echo $view['translator']->trans('mautic.core.form.filters.available'); ?></h4>
                            <div class="rounded-corners body-white padding-md">
                                <?php foreach ($choices as $value => $params): ?>
                                <div id="available_<?php echo $value; ?>">
                                    <a href="javascript:void(0);" onclick="Mautic.addLeadListFilter('<?php echo $value; ?>');">
                                        <div class="leadlist-filter">
                                            <div class="padding-sm">
                                                <div class="pull-left padding-sm">
                                                    <span class="leadlist-filter-name"><?php echo $view['translator']->trans($params['label']); ?></span>
                                                </div>
                                                <div class="pull-right padding-sm">
                                                    <i class="fa fa-fw fa-plus fa-lg"></i>
                                                </div>
                                                <div class="clearfix"></div>
                                            </div>
                                        </div>
                                    </a>
                                    <input type="hidden" class="field_alias" value="<?php echo $value; ?>" />
                                    <input type="hidden" class="field_type" value="<?php echo $params['properties']['type']; ?>" />
                                    <?php $list = (!empty($params['properties']['list'])) ? $params['properties']['list'] : ''; ?>
                                    <input type="hidden" class="field_list" value="<?php echo $list; ?>" />
                                    <?php $callback = (!empty($params['properties']['callback'])) ? $params['properties']['callback'] : ''; ?>
                                    <input type="hidden" class="field_callback" value="<?php echo $callback; ?>" />
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-xs-8 selected-filters">
                            <h4><?php echo $view['translator']->trans('mautic.core.form.filters.selected'); ?></h4>
                            <div class="rounded-corners body-white padding-md">
                                <ul class="padding-none no-bullet" id="<?php echo $filterForm->vars['id']; ?>_right">
                                    <?php foreach ($filterValues as $filter): ?>
                                    <?php if (!isset($choices[$filter['field']])) continue; ?>
                                    <?php $randomId = "id_" . uniqid(); ?>
                                    <li class="padding-sm">
                                        <i class="fa fa-fw fa-ellipsis-v sortable-handle"></i><i class="fa fa-fw fa-trash-o remove-selected"></i>
                                        <?php echo $choices[$filter['field']]['label']; ?>
                                        <div class="filter-container">
                                            <div class="col-xs-6 col-sm-3 padding-none">
                                                <select name="leadlist[filters][glue][]" class="form-control ">
                                                    <?php
                                                    foreach ($glueOptions as $v => $l):
                                                    $selected = ($v == $filter['glue']) ? ' selected' : '';
                                                    ?>
                                                        <option value="<?php echo $v; ?>"<?php echo $selected; ?>><?php echo $view['translator']->trans($l); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-xs-6 col-sm-3 padding-none">
                                                <select name="leadlist[filters][operator][]" class="form-control ">
                                                    <?php
                                                    foreach ($operatorOptions as $v => $l):
                                                    $selected = ($v == $filter['operator']) ? ' selected' : '';
                                                    ?>
                                                    <option value="<?php echo $v; ?>"<?php echo $selected; ?>><?php echo $view['translator']->trans($l['label']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-xs-12 col-sm-6 padding-none">
                                                <?php switch ($choices[$filter['field']]['properties']['type']):
                                                case 'lookup':
                                                case 'select':
                                                ?>
                                                <input type="text" class="form-control"
                                                       name="leadlist[filters][filter][]"
                                                       data-toggle="field-lookup"
                                                       data-target="<?php echo $filter['field']; ?>"
                                                       <?php if (isset($choices[$filter['field']]['properties']['list'])):?>
                                                       data-options="<?php echo $choices[$filter['field']]['properties']['list']; ?>"
                                                       <?php endif; ?>
                                                       placeholder="<?php echo $view['translator']->trans('mautic.lead.list.form.filtervalue'); ?>"
                                                       value="<?php echo $filter['filter']; ?>"
                                                       id="<?php echo $randomId; ?>" />
                                                <input type="hidden" name="leadlist[filters][display][]" />
                                                <?php
                                                break;
                                                case 'timezone':
                                                ?>
                                                <select class="form-control" name="leadlist[filters][filter][]">
                                                <?php foreach ($timezones as $continent => $zones): ?>
                                                    <optgroup label="<?php echo $continent; ?>" />
                                                    <?php foreach ($zones as $t): ?>
                                                    <?php $selected = ($filter['filter'] == $t) ? ' selected="selected"' : ''; ?>
                                                    <option value="<?php echo $t; ?>"<?php echo $selected; ?>><?php echo $t; ?></option>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>
                                                </select>
                                                <input type="hidden" name="leadlist[filters][display][]" />
                                                <?php
                                                break;
                                                case 'country':
                                                ?>
                                                <select class="form-control" name="leadlist[filters][filter][]">
                                                <?php foreach ($countries as $c): ?>
                                                    <?php $selected = ($filter['filter'] == $c) ? ' selected="selected"' : ''; ?>
                                                    <option value="<?php echo $c; ?>"<?php echo $selected; ?>><?php echo $c; ?></option>
                                                <?php endforeach; ?>
                                                </select>
                                                <input type="hidden" name="leadlist[filters][display][]" />
                                                <?php
                                                break;
                                                case 'time':
                                                case 'date':
                                                case 'datetime':
                                                ?>
                                                <input type="<?php echo $choices[$filter['field']]['properties']['type']; ?>"
                                                       class="form-control"
                                                       name="leadlist[filters][filter][]"
                                                       data-toggle="<?php echo $choices[$filter['field']]['properties']['type']; ?>"
                                                       value="<?php echo $filter['filter'] ?>"
                                                       id="<?php echo $randomId; ?>" />
                                                <?php
                                                break;
                                                case 'lookup_id':
                                                case 'boolean':
                                                ?>
                                                <input type="text" class="form-control"
                                                       name="leadlist[filters][display][]"
                                                       data-toggle="field-lookup"
                                                       data-target="<?php echo $filter['field']; ?>"
                                                        <?php if (isset($choices[$filter['field']]['properties']['list'])):?>
                                                        data-options="<?php echo $choices[$filter['field']]['properties']['list']; ?>"
                                                        <?php endif; ?>
                                                       placeholder="<?php echo $view['translator']->trans('mautic.lead.list.form.filtervalue'); ?>"
                                                       value="<?php echo $filter['display']; ?>"
                                                       id="<?php echo $randomId; ?>" />
                                                <input type="hidden"
                                                       name="leadlist[filters][filter][]"
                                                       value="<?php echo $filter['filter']; ?>"
                                                       id="<?php echo $randomId."_id"; ?>" />
                                                <?php break; ?>
                                                <?php default: ?>
                                                <input type="<?php echo $choices[$filter['field']]['properties']['type']; ?>"
                                                       class="form-control"
                                                       name="leadlist[filters][filter][]"
                                                       data-toggle="field-lookup"
                                                       data-target="<?php echo $filter['field']; ?>"
                                                       placeholder="<?php echo $view['translator']->trans('mautic.lead.list.form.filtervalue'); ?>"
                                                       value="<?php echo $filter['filter']; ?>"
                                                       id="<?php echo $randomId; ?>" />
                                                <input type="hidden" name="leadlist[filters][display][]" />
                                                <?php break; ?>
                                                <?php endswitch; ?>
                                            </div>
                                            <input type="hidden" name="leadlist[filters][field][]" value="<?php echo $filter['field']; ?>" />
                                            <input type="hidden" name="leadlist[filters][type][]" value="<?php echo $filter['type']; ?>" />
                                            <div class="clearfix"></div>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php echo $view['form']->end($form); ?>

            <div id="filter-template" class="hide">
                <div class="col-xs-6 col-sm-2 padding-none">
                    <select name="leadlist[filters][glue][]" class="form-control ">
                        <?php foreach ($glueOptions as $v => $l): ?>
                            <option value="<?php echo $v; ?>"><?php echo $view['translator']->trans($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xs-6 col-sm-3 padding-none">
                    <select name="leadlist[filters][operator][]" class="form-control ">
                        <?php foreach ($operatorOptions as $v => $l): ?>
                            <option value="<?php echo $v; ?>"><?php echo $view['translator']->trans($l['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xs-12 col-sm-7 padding-none">
                    <input type="text" class="form-control" name="leadlist[filters][filter][]"
                           placeholder="<?php echo $view['translator']->trans('mautic.lead.list.form.filtervalue'); ?>" />
                    <input type="hidden" name="leadlist[filters][display][]" />
                </div>
                <input type="hidden" name="leadlist[filters][field][]" />
                <input type="hidden" name="leadlist[filters][type][]" />
                <div class="clearfix"></div>
            </div>

            <div id="filter-country-template" class="hide">
                <div class="col-xs-6 col-sm-2 padding-none">
                    <select name="leadlist[filters][glue][]" class="form-control ">
                        <?php foreach ($glueOptions as $v => $l): ?>
                            <option value="<?php echo $v; ?>"><?php echo $view['translator']->trans($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xs-6 col-sm-3 padding-none">
                    <select name="leadlist[filters][operator][]" class="form-control ">
                        <?php foreach ($operatorOptions as $v => $l): ?>
                            <option value="<?php echo $v; ?>"><?php echo $view['translator']->trans($l['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xs-12 col-sm-7 padding-none">
                    <select class="form-control" name="leadlist[filters][filter][]">
                    <?php foreach ($countries as $c): ?>
                        <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                    <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="leadlist[filters][display][]" />
                </div>
                <input type="hidden" name="leadlist[filters][field][]" />
                <input type="hidden" name="leadlist[filters][type][]" />
                <div class="clearfix"></div>
            </div>

            <div id="filter-timezone-template" class="hide">
                <div class="col-xs-6 col-sm-2 padding-none">
                    <select name="leadlist[filters][glue][]" class="form-control ">
                        <?php foreach ($glueOptions as $v => $l): ?>
                            <option value="<?php echo $v; ?>"><?php echo $view['translator']->trans($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xs-6 col-sm-3 padding-none">
                    <select name="leadlist[filters][operator][]" class="form-control ">
                        <?php foreach ($operatorOptions as $v => $l): ?>
                            <option value="<?php echo $v; ?>"><?php echo $view['translator']->trans($l['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xs-12 col-sm-7 padding-none">
                    <select class="form-control" name="leadlist[filters][filter][]">
                        <?php foreach ($timezones as $continent => $zones): ?>
                            <optgroup label="<?php echo $continent; ?>" />
                            <?php foreach ($zones as $t): ?>
                            <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="leadlist[filters][display][]" />
                </div>
                <input type="hidden" name="leadlist[filters][field][]" />
                <input type="hidden" name="leadlist[filters][type][]" />
                <div class="clearfix"></div>
            </div>
        </div>
    </div>
</div>

