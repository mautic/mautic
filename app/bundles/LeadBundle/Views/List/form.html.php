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
$view['slots']->set('mauticContent', 'leadlist');
$fields = $form->vars['fields'];
$id     = $form->vars['data']->getId();
$index  = count($form['filters']->vars['value']) ? max(array_keys($form['filters']->vars['value'])) : 0;

if (!empty($id)) {
    $name   = $form->vars['data']->getName();
    $header = $view['translator']->trans('mautic.lead.list.header.edit', ['%name%' => $name]);
} else {
    $header = $view['translator']->trans('mautic.lead.list.header.new');
}
$view['slots']->set('headerTitle', $header);

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
                    <li data-toggle="tooltip" title="" data-placement="top" data-original-title="<?php echo $view['translator']->trans('mautic.lead.lead.segment.add.help'); ?>">
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
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['alias']); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo $view['form']->row($form['publicName']); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <?php echo $view['form']->row($form['description']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade bdr-w-0" id="filters">
                        <div class="alert alert-info"><p><?php echo $view['translator']->trans('mautic.lead.lead.segment.filter.info'); ?></p></div>
                        <div class="form-group">
                            <div class="available-filters mb-md pl-0 col-md-4" data-prototype="<?php echo $view->escape($view['form']->widget($form['filters']->vars['prototype'])); ?>" data-index="<?php echo $index + 1; ?>">
                                <select class="chosen form-control" id="available_segment_filters">
                                    <option value=""></option>
                                    <?php
                                    foreach ($fields as $object => $field):
                                        $header = $object;
                                        $icon   = ('company' == $object) ? 'building' : 'user';
                                        $header = $view['translator']->hasId($translationId = 'mautic.lead.'.$header)
                                            ? $view['translator']->trans($translationId)
                                            : $view['translator']->trans($header);
                                    ?>
                                    <optgroup label="<?php echo $header; ?>">
                                        <?php foreach ($field as $value => $params):
                                            $operators = (!empty($params['operators'])) ? $view->escape(json_encode($params['operators'])) : '{}';
                                            ?>
                                            <option value="<?php echo $view->escape($value); ?>"
                                                    id="available_<?php echo $object.'_'.$value; ?>"
                                                    data-field-object="<?php echo $object; ?>"
                                                    data-field-type="<?php echo $params['properties']['type']; ?>"
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
                        <div class="selected-filters" id="leadlist_filters">
                            <?php if ($filterErrors): ?>
                                <div class="alert alert-danger has-error">
                                    <?php echo $view['form']->errors($form['filters']); ?>
                                </div>
                            <?php endif; ?>
                            <?php echo $view['form']->widget($form['filters']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto bdr-l">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php echo $view['form']->row($form['category']); ?>
            <?php echo $view['form']->row($form['isGlobal']); ?>
            <?php echo $view['form']->row($form['isPreferenceCenter']); ?>
            <?php echo $view['form']->row($form['isPublished']); ?>
        </div>
    </div>
</div>
<?php echo $view['form']->end($form); ?>

