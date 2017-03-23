<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="tab-pane dynamic-content bdr-w-0<?php echo $form->vars['name'] === '0' ? ' active' : ' fade'; ?>" id="<?php echo $form->vars['id'] ?>">
    <div class="panel">
        <?php
        $tabHtml = '<ul class="bg-auto nav nav-tabs pr-md pl-md" id="dynamicContentFilterTabs_'.$form->vars['name'].'">';
        $tabHtml .= '<li class="active"><a data-toggle="tab" href="#emailform_dynamicContent_'.$form->vars['name'].'_default" role="tab">Default</a></li>';
        $tabContentHtml = '<div class="tab-content pa-md"><div class="tab-pane bdr-w-0 active" id="emailform_dynamicContent_'.$form->vars['name'].'_default">';
        $tabContentHtml .= '<div class="row"><div class="col-xs-10">';
        $tabContentHtml .= $view['form']->row($form['tokenName']);
        $tabContentHtml .= '</div><div class="col-xs-2"><label>&nbsp;<!--for alignment--></label><a href="javascript: void(0);" class="remove-item btn btn-default text-danger"><i class="fa fa-trash-o"></i></a></div></div>';
        $tabContentHtml .= $view['form']->row($form['content']);
        $tabContentHtml .= '</div>';

        foreach ($form['filters'] as $i => $filter) {
            $isFirst = $i === 0 ? ' active' : '';
            $tabHtml .= '<li><a role="tab" data-toggle="tab" href="#'.$filter->vars['id'].'">'.$view['translator']->trans('mautic.core.dynamicContent.tab').' '.($i + 1).'</a></li>';

            $tabContentHtml .= $view['form']->widget($filter);
        }

        $tabHtml .= '</ul>';
        $tabContentHtml .= '</div>';

        echo $tabHtml;
        echo $tabContentHtml;
        ?>
    </div>
</div>