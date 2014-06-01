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
$view["slots"]->set("headerTitle", $header);

$glueOptions = array(
    'and' => 'mautic.lead.list.form.glue.and',
    'or'  => 'mautic.lead.list.form.glue.or'
);
?>
<?php echo $view['form']->start($form); ?>
<div class="row">
    <div class="col-sm-12 col-md-6">
        <?php echo $view['form']->row($form['name']); ?>
        <?php echo $view['form']->row($form['alias']); ?>
        <?php echo $view['form']->row($form['description']); ?>
        <?php echo $view['form']->row($form['isGlobal']); ?>
        <?php echo $view['form']->row($form['isActive']); ?>

        <?php
        $filterForm   = $form['filters'];
        $filterValues = $filterForm->vars['data'] ?: array();
        unset($form['filters']);
        $feedbackClass = ($app->getRequest()->getMethod() == 'POST' && !empty($filterForm->vars['errors'])) ? " has-error" : "";
        ?>
        <div class="row">
            <div class="form-group col-sm-12<?php echo $feedbackClass; ?>">
                <?php echo $view['form']->label($filterForm); ?>
                <?php echo $view['form']->errors($filterForm); ?>
                <div class="row draggable-container">
                    <div class="col-sm-4 available-filters">
                        <span><?php echo $view['translator']->trans('mautic.core.form.draggable.left'); ?></span>
                        <ul class="draggable" id="<?php echo $filterForm->vars['id']; ?>_left">
                            <?php foreach ($choices as $value => $params): ?>
                            <li>
                                <i class="fa fa-fw fa-arrows"></i><?php echo $view['translator']->trans($params['label']); ?>
                                <input type="hidden" class="field_alias" value="<?php echo $value; ?>" />
                                <input type="hidden" class="field_type" value="<?php echo $params['properties']['type']; ?>" />
                                <?php $list = (!empty($params['properties']['list'])) ? $params['properties']['list'] : ''; ?>
                                <input type="hidden" class="field_list" value="<?php echo $list; ?>" />
                                <?php $callback = (!empty($params['properties']['callback'])) ? $params['properties']['callback'] : ''; ?>
                                <input type="hidden" class="field_callback" value="<?php echo $callback; ?>" />
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-sm-8 selected-filters">
                        <span><?php echo $view['translator']->trans('mautic.core.form.draggable.right'); ?></span>
                        <ul class="droppable padding-none" id="<?php echo $filterForm->vars['id']; ?>_right">
                            <?php $class = (!empty($filterValues)) ? ' hide' : ''; ?>
                            <li class="placeholder<?php echo $class; ?>"><?php echo $view['translator']->trans('mautic.core.droppable.placeholder'); ?></li>
                            <?php
                            foreach ($filterValues as $filter):?>
                            <?php if (!isset($choices[$filter['field']])) continue; ?>
                            <?php $randomId = "id_" . uniqid(); ?>
                            <li class="padding-sm">
                                <i class="fa fa-fw fa-arrows sortable-handle"></i><i class="fa fa-fw fa-trash-o remove-selected"></i>
                                <?php echo $choices[$filter['field']]['label']; ?>
                                <div class="filter-container">
                                    <div class="col-sm-6 col-md-3 padding-none">
                                        <select name="leadlist[filters][glue][]" class="form-control ">
                                            <?php
                                            foreach ($glueOptions as $v => $l):
                                            $selected = ($v == $filter['glue']) ? ' selected' : '';
                                            ?>
                                                <option value="<?php echo $v; ?>"<?php echo $selected; ?>><?php echo $view['translator']->trans($l); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-6 col-md-4 padding-none">
                                        <select name="leadlist[filters][operator][]" class="form-control ">
                                            <?php
                                            foreach ($operatorOptions as $v => $l):
                                            $selected = ($v == $filter['operator']) ? ' selected' : '';
                                            ?>
                                            <option value="<?php echo $v; ?>"<?php echo $selected; ?>><?php echo $view['translator']->trans($l['label']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-12 col-md-4 padding-none">
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
                                        <?php break; ?>
                                        <?php
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
                                    <input type="hidden" name="leadlist[filters][field][]" readonly value="<?php echo $filter['field']; ?>"/>
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
</div>
<?php echo $view['form']->end($form); ?>

<div id="filter-template" class="hide">
    <div class="col-sm-6 col-md-3 padding-none">
        <select name="leadlist[filters][glue][]" class="form-control ">
            <?php foreach ($glueOptions as $v => $l): ?>
                <option value="<?php echo $v; ?>"><?php echo $view['translator']->trans($l); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-6 col-md-4 padding-none">
        <select name="leadlist[filters][operator][]" class="form-control ">
            <?php foreach ($operatorOptions as $v => $l): ?>
                <option value="<?php echo $v; ?>"><?php echo $view['translator']->trans($l['label']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-12 col-md-4 padding-none">
        <input type="text" class="form-control" name="leadlist[filters][filter][]"
               placeholder="<?php echo $view['translator']->trans('mautic.lead.list.form.filtervalue'); ?>" />
        <input type="hidden" name="leadlist[filters][display][]" />
    </div>
    <input type="hidden" name="leadlist[filters][field][]" readonly />
    <div class="clearfix"></div>
</div>