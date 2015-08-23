<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'leadlist');
$fields = $form->vars['fields'];
$id = $form->vars['data']->getId();
if (!empty($id)) {
    $name   = $form->vars['data']->getName();
    $header = $view['translator']->trans('mautic.lead.list.header.edit', array("%name%" => $name));
} else {
    $header = $view['translator']->trans('mautic.lead.list.header.new');
}
$view['slots']->set("headerTitle", $header);

$templates = array(
    'countries' => 'country-template',
    'regions'   => 'region-template',
    'timezones' => 'timezone-template'
);
?>

<?php echo $view['form']->start($form); ?>
    <div class="box-layout">
        <div class="col-md-9 bg-white height-auto">
            <div class="row">
                <div class="col-xs-12">
                    <ul class="bg-auto nav nav-tabs pr-md pl-md">
                        <li class="active">
                            <a href="#details" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.core.details'); ?></a>
                        </li>
                        <li>
                            <a href="#filters" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.core.filters'); ?></a></a>
                        </li>
                    </ul>

                    <!-- start: tab-content -->
                    <div class="tab-content pa-md">
                        <div class="tab-pane fade in active bdr-w-0" id="details">
                            <div class="row">
                                <div class="col-md-6">
                                    <?php echo $view['form']->row($form['name']); ?>
                                </div>
                                <div class="col-md-6">
                                    <?php echo $view['form']->row($form['alias']); ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12">
                                    <?php echo $view['form']->row($form['description']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade bdr-w-0" id="filters">
                            <div class="form-group">
                                <?php echo $view['form']->errors($form['filters']); ?>
                                <div class="available-filters mb-md" data-prototype="<?php echo $view->escape($view['form']->row($form['filters']->vars['prototype'])); ?>" data-index="<?php echo count($form['filters']) + 1; ?>">
                                    <div class="dropdown">
                                        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                                            <?php echo $view['translator']->trans('mautic.lead.list.form.filters.add'); ?>
                                            <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu scrollable-menu" role="menu">
                                            <?php foreach ($fields as $value => $params): ?>
                                                <?php $list = (!empty($params['properties']['list'])) ? $params['properties']['list'] : ''; ?>
                                                <?php $callback = (!empty($params['properties']['callback'])) ? $params['properties']['callback'] : ''; ?>
                                                <li>
                                                    <a id="available_<?php echo $value; ?>" class="list-group-item" href="javascript:void(0);" onclick="Mautic.addLeadListFilter('<?php echo $value; ?>');" data-field-type="<?php echo $params['properties']['type']; ?>" data-field-list="<?php echo $list; ?>" data-field-callback="<?php echo $callback; ?>">
                                                        <span class="leadlist-filter-name"><?php echo $view['translator']->trans($params['label']); ?></span>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                                <div class="selected-filters">
                                    <?php echo $view['form']->widget($form['filters']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 bg-white height-auto bdr-l">
            <div class="pr-lg pl-lg pt-md pb-md">
                <?php echo $view['form']->row($form['isGlobal']); ?>
                <?php echo $view['form']->row($form['isPublished']); ?>
            </div>
        </div>
    </div>
<?php echo $view['form']->end($form); ?>

<div class="templates hide">
<?php foreach ($templates as $dataKey => $template): ?>
    <select class="form-control not-chosen <?php echo $template; ?>" name="leadlist[filters][__name__][filter]" id="leadlist_filters___name___filter">
        <option value=""></option>
        <?php
        foreach ($form->vars[$dataKey] as $value => $label):
        if (is_array($label)):
            echo "<optgroup label=\"$value\">$value</optgroup>\n";
            foreach ($label as $optionValue => $optionLabel):
                echo "<option value=\"$optionValue\">$optionLabel</option>\n";
            endforeach;
            echo "</optgroup>\n";
        else:
            echo "<option value=\"$value\">$label</option>\n";
        endif;
        endforeach;
        ?>
    </select>
<?php endforeach; ?>
</div>