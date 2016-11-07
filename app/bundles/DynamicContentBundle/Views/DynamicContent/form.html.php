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
$view['slots']->set('mauticContent', 'dynamicContent');

$dynamicContent = $form->vars['data'];

$header = ($dynamicContent->getId()) ?
    $view['translator']->trans('mautic.dynamicContent.header.edit',
        ['%name%' => $dynamicContent->getName()]) :
    $view['translator']->trans('mautic.dynamicContent.header.new');

$view['slots']->set('headerTitle', $header);

?>

<?php echo $view['form']->start($form); ?>
    <div class="box-layout">
        <div class="col-md-9 height-auto bg-auto">
            <div class="row">
                <div class="col-xs-12">
                    <!-- tabs controls -->
                    <!--/ tabs controls -->
                    <div class="tab-content pa-md">
                        <div class="tab-pane fade in active bdr-w-0" id="dynamicContent-container">
                            <div class="row">
                                <div class="col-md-6">
                                    <?php echo $view['form']->row($form['name']); ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <?php echo $view['form']->row($form['content']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 bg-white height-auto bdr-l">
            <div class="pr-lg pl-lg pt-md pb-md">
                <?php echo $view['form']->row($form['category']); ?>
                <?php echo $view['form']->row($form['language']); ?>
                <?php echo $view['form']->row($form['translationParent']); ?>
                <div class="hide">
                    <div id="publishStatus">
                        <?php echo $view['form']->row($form['isPublished']); ?>
                        <?php echo $view['form']->row($form['publishUp']); ?>
                        <?php echo $view['form']->row($form['publishDown']); ?>
                    </div>

                    <?php echo $view['form']->rest($form); ?>
                </div>
            </div>
        </div>
    </div>
<?php echo $view['form']->end($form); ?>