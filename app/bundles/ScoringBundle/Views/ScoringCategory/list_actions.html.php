<?php
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
    <span class="input-group-addon"><?php if(!is_array($item) && $item->isUsedAnywhere()) { ?>
        <input type="checkbox" disabled="disabled" readonly="readonly" />
        <?php } else { ?>
        <input type="checkbox" data-target="tbody" data-toggle="selectrow" class="list-checkbox" name="cb<?php echo $id; ?>" value="<?php echo $id; ?>"/>
        <?php } ?>
    </span>

    <div class="input-group-btn">
        <button type="button" class="btn btn-default btn-sm dropdown-toggle btn-nospin" data-toggle="dropdown">
            <i class="fa fa-angle-down "></i>
        </button>
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
            if (!empty($templateButtons['delete'])) {
                if(!is_array($item) && $item->isUsedAnywhere()) {
                    $listelems = array();
                    foreach($item->getUsedByEvents() as $ue) {
                        $listelems[] = $ue->getName().' ('.$view['translator']->trans('mautic.scoring.scoringCategory.usedin.events').')';
                    }
                    foreach($item->getUsedByPoints() as $up) {
                        $listelems[] = $up->getName().' ('.$view['translator']->trans('mautic.scoring.scoringCategory.usedin.points').')';
                    }
                    foreach($item->getUsedByTriggers() as $ut) {
                        $listelems[] = $ut->getName().' ('.$view['translator']->trans('mautic.scoring.scoringCategory.usedin.triggers').')';
                    }
                    $message = $view['translator']->trans('mautic.scoring.scoringCategory.deleteused', ['%listing%' => implode("\r\n"."\r\n", $listelems)]); // don't ask me for the nl2br
                    $view['buttons']->addButton(
                        [
                            'confirm' => [
                                'btnClass'      => false,
                                'btnText'       => $view['translator']->trans('mautic.core.form.delete'),
                                'confirmText'   => $view['translator']->trans('mautic.core.form.cancel'),
                                'confirmCallback' => 'dismissConfirmation',
                                'cancelText'   => false,
                                'iconClass' => 'fa fa-fw fa-trash-o text-danger',
                                'message'       => $message,
                            ],
                            'priority' => -1,
                        ]
                    );
                } else {
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
                }
            }

            echo $view['buttons']->renderButtons();
            ?>
        </ul>
    </div>
</div>
