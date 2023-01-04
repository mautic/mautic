<?php

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'league');

$header = ($entity->getId()) ?
    $view['translator']->trans('mautic.point.league.menu.edit', ['%name%' => $view['translator']->trans($entity->getName())]) :
    $view['translator']->trans('mautic.point.league.menu.new');
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
                <div class="col-md-12">
                    <div class="pa-md">
                        <div id="scoringActionProperties">
                            <?php
                            if (isset($form['properties'])):
                                echo $view['form']->row($form['properties']);
                            endif;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 bg-white height-auto">
            <div class="pr-lg pl-lg pt-md pb-md">
                <?php
                echo $view['form']->row($form['isPublished']);
                ?>
            </div>
        </div>
    </div>
<?php echo $view['form']->end($form); ?>