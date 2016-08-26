<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'company');

$header = ($entity->getId()) ?
    $view['translator']->trans('mautic.company.menu.edit',
        array('%name%' => $view['translator']->trans($entity->getName()))) :
    $view['translator']->trans('mautic.company.menu.new');
$view['slots']->set("headerTitle", $header);

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
                <div class="col-md-6">
                    <div class="pa-md">
                        <?php
                        echo $view['form']->row($form['companyNumber']);

                        ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="pa-md">
                        <?php
                        echo $view['form']->row($form['companySource']);

                        ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="pa-md">
                        <?php
                        echo $view['form']->row($form['annualRevenue']);

                        ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="pa-md">
                        <?php
                        echo $view['form']->row($form['email']);

                        ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="pa-md">
                        <?php
                        echo $view['form']->row($form['website']);

                        ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="pa-md">
                        <?php
                        echo $view['form']->row($form['phone']);

                        ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="pa-md">
                        <?php
                        echo $view['form']->row($form['fax']);

                        ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="pa-md">
                        <?php
                        echo $view['form']->row($form['numberOfEmployees']);

                        ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="pa-md">
                        <?php
                        echo $view['form']->row($form['score']);

                        ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="pa-md">
                        <?php
                        echo $view['form']->row($form['address1']);

                        ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="pa-md">
                        <?php
                        echo $view['form']->row($form['address2']);

                        ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="pa-md">
                        <?php
                        echo $view['form']->row($form['city']);

                        ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="pa-md">
                        <?php
                        echo $view['form']->row($form['state']);

                        ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="pa-md">
                        <?php
                        echo $view['form']->row($form['zipcode']);

                        ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="pa-md">
                        <?php
                        echo $view['form']->row($form['country']);

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
                <?php
                echo $view['form']->row($form['isPublished']);
                echo $view['form']->row($form['publishUp']);
                echo $view['form']->row($form['publishDown']);
                echo $view['form']->row($form['owner']);
                ?>
            </div>
        </div>
    </div>
<?php echo $view['form']->end($form); ?>