<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set("headerTitle", $appIntegration->getAppName());
$view['slots']->set('mauticContent', $appIntegration->getAppName());
?>
<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <div class="page-list">
        <table class="table table-hover table-striped table-bordered">
            <thead>
                <tr>
                    <th>
                        Object
                    </th>
                    <th>
                        Mapped
                    </th>
                    <th>
                        Points
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appIntegration->getMappedObjects() as $object): ?>
                    <?php
                    $object_link = $router->generate('mautic_mapper_integration_object',array('application' => $appIntegration->getAppAlias(), 'object' => $object->getObjectName()));
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo $object_link; ?>"><?php echo $object->getObjectName(); ?></a>
                        </td>
                        <td class="text-center">
                        </td>
                        <td class="text-center">

                            <span class="label label-default"><?php echo $object->getPoints(); ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>