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
$view['slots']->set('mauticContent', 'tagmanager');
$id     = $form->vars['data']->getId();

if (!empty($id)) {
    $header = $view['translator']->trans('mautic.tagmanager.menu.edit', ['%name%' => $view['translator']->trans($entity->getTag())]);
} else {
    $header = $view['translator']->trans('mautic.tagmanager.menu.new');
}

$view['slots']->set('headerTitle', $header);

echo $view['form']->start($form);
?>

<div class="box-layout">
    <div class="col-md-9 bg-white height-auto">
        <div class="row">
            <div class="col-xs-12">
                <ul class="bg-auto nav nav-tabs pr-md pl-md">
                    <li class="active">
                        <a href="#details" role="tab" data-toggle="tab">
                            <?php echo $view['translator']->trans('mautic.core.details'); ?>
                        </a>
                    </li>
                </ul>

                <!-- start: tab-content -->
                <div class="tab-content pa-md">
                    <div class="tab-pane fade in active bdr-w-0" id="details">
                        <div class="row">
                            <div class="col-xs-12">
                                <?php echo $view['form']->row($form['tag']); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <?php echo $view['form']->row($form['description']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php echo $view['form']->end($form); ?>