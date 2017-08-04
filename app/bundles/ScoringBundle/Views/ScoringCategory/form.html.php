<?php
/*
 * @author      Captivea (QCH)
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'scoring');

$header = ($entity->getId()) ?
        $view['translator']->trans('mautic.scoring.menu.edit', ['%name%' => $view['translator']->trans($entity->getName())]) :
        $view['translator']->trans('mautic.scoring.menu.new');
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
                        echo $view['form']->row($form['orderIndex']);
                    ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="pa-md">
                    <?php
                        echo $view['form']->row($form['updateGlobalScore']);
                        echo $view['form']->row($form['globalScoreModifier']);
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