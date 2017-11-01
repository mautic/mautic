<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
use \Mautic\CoreBundle\Templating\Helper\ButtonHelper;

$view['buttons']->reset($app->getRequest(), ButtonHelper::LOCATION_LIST_ACTIONS, ButtonHelper::TYPE_DROPDOWN, $item);
include 'action_button_helper.php';

if (is_array($item)) {
    $id   = $item['id'];
    $name = $item['name'];
} else {
    $id   = $item->getId();
    $name = $item->$nameGetter();
}

?>
<div class="input-group input-group-sm">
    <span class="input-group-addon">
        <input type="checkbox" data-target="tbody" data-toggle="selectrow" class="list-checkbox" name="cb<?php echo $id; ?>" value="<?php echo $id; ?>"/>
    </span>

    <div class="input-group-btn">
        <button type="button" class="btn btn-default btn-sm dropdown-toggle btn-nospin" data-toggle="dropdown">
            <i class="fa fa-angle-down "></i>
        </button>
        <?php if (!empty($tooltip)): ?> <i class="fa fa-question-circle"></i><?php endif; ?>
        <ul class="pull-<?php echo $pull; ?> page-list-actions dropdown-menu" role="menu">
            <?php
            if (!empty($templateButtons['edit'])):
                $view['buttons']->addButton(
                    [
                        'attr' => array_merge(
                            [
                                'class' => 'hidden-xs btn btn-default btn-sm btn-nospin',
                                'href'  => $view['router']->path(
                                    $actionRoute,
                                    array_merge(['objectAction' => 'edit', 'objectId' => $id], $query)
                                ),
                                'data-toggle' => $editMode,
                            ],
                            $editAttr
                        ),
                        'iconClass' => 'fa fa-pencil-square-o',
                        'btnText'   => $view['translator']->trans('mautic.core.form.edit'),
                        'primary'   => true,
                    ]
                );
            endif;
            if (!empty($templateButtons['clone'])):
                $view['buttons']->addButton(
                    [
                        'attr' => array_merge(
                            [
                                'class' => 'hidden-xs btn btn-default btn-sm btn-nospin',
                                'href'  => $view['router']->path(
                                    $actionRoute,
                                    array_merge(['objectAction' => 'clone', 'objectId' => $id], $query)
                                ),
                                'data-toggle' => 'ajax',
                            ],
                            $editAttr
                        ),
                        'iconClass' => 'fa fa-copy',
                        'btnText'   => $view['translator']->trans('mautic.core.form.clone'),
                        'priority'  => 200,
                    ]
                );
            endif;
            if (!empty($templateButtons['delete'])):
                $view['buttons']->addButton(
                    [
                        'confirm' => [
                            'btnClass'      => false,
                            'btnText'       => $view['translator']->trans('mautic.core.form.delete'),
                            'message'       => $view['translator']->trans($translationBase.'.form.confirmdelete', ['%name%' => $name.' ('.$id.')']),
                            'confirmAction' => $view['router']->path(
                                $actionRoute,
                                array_merge(['objectAction' => 'delete', 'objectId' => $id], $query)
                            ),
                            'template' => 'delete',
                        ],
                        'priority' => -1,
                    ]
                );
            endif;

            echo $view['buttons']->renderButtons();
            ?>
        </ul>
    </div>
</div>
