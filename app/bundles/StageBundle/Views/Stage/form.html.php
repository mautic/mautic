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
$view['slots']->set('mauticContent', 'stage');

$header = ($entity->getId()) ?
    $view['translator']->trans('mautic.stage.menu.edit',
        ['%name%' => $view['translator']->trans($entity->getName())]) :
    $view['translator']->trans('mautic.stage.menu.new');
$view['slots']->set('headerTitle', $header);

echo $view['form']->start($form);
?>
    <!-- start: box layout -->
    <div class="box-layout">
        <!-- container -->
        <div class="col-md-9 bg-auto height-auto bdr-r">
            <div class="row">
                <div class="col-md-6">
                    <div class="pa-md">
                        <?php
                        echo $view['form']->row($form['name']);

                        ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <div class="pa-md">
                        <?php echo $view['form']->row($form['description']); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 bg-white height-auto">
            <div class="pr-lg pl-lg pt-md pb-md">
                <?php echo $view['form']->row($form['weight']); ?>
                <?php
                echo $view['form']->row($form['category']);
                echo $view['form']->row($form['isPublished']);
                echo $view['form']->row($form['publishUp']);
                echo $view['form']->row($form['publishDown']);
                ?>
            </div>
        </div>
    </div>
<?php echo $view['form']->end($form); ?>