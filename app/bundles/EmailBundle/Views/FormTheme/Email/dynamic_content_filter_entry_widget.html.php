<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$fields = $form->vars['fields'];

?>
<div class="tab-pane bdr-w-0<?php echo ($form->vars['name'] === '0' ? ' active' : ' fade') ?>" id="<?php echo $form->vars['id'] ?>">
    <div class="row">
        <div class="col-xs-8">
            <?php echo $view['form']->widget($form['content']); ?>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
                <div class="available-filters mb-md pl-0">
                    <select class="chosen form-control" id="available_filters">
                        <option value=""></option>
                        <?php
                        foreach ($fields as $value => $params):
                            $list      = (!empty($params['properties']['list'])) ? $params['properties']['list'] : array();
                            $choices   = \Mautic\LeadBundle\Helper\FormFieldHelper::parseListStringIntoArray($list);
                            $list      = json_encode($choices);
                            $callback  = (!empty($params['properties']['callback'])) ? $params['properties']['callback'] : '';
                            $operators = (!empty($params['operators'])) ? $view->escape(json_encode($params['operators'])) : '{}';
                            ?>
                            <option value="<?php echo $value; ?>" id="available_<?php echo $value; ?>" data-field-type="<?php echo $params['properties']['type']; ?>" data-field-list="<?php echo $view->escape($list); ?>" data-field-callback="<?php echo $callback; ?>" data-field-operators="<?php echo $operators; ?>"><?php echo $view['translator']->trans($params['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>
    <div data-filter-container>
    <?php
    foreach ($form['filters'] as $i => $filter) {
        $isPrototype = ($filter->vars['name'] == '__name__');
        if ($isPrototype || isset($filter->vars['fields'][$filter->vars['value']['field']])) {
            echo $view['form']->widget($filter, ['first' => ($i === 0)]);
        }
    }
    ?>
    </div>
</div>
