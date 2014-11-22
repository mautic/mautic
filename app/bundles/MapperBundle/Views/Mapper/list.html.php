<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {

    $view->extend('MauticCoreBundle:Default:content.html.php');
    $view['slots']->set('mauticContent', $application);
    $view['slots']->set("headerTitle", $view['translator']->trans('mautic.mapper.title.mapper'));
}
?>
<?php if (count($items)): ?>
    <div class="table-responsive page-list">
        <table class="table table-hover table-striped table-bordered category-list" id="categoryTable">
            <thead>
            <tr>
                <?php

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'mapper',
                    'orderBy'    => 'e.title',
                    'text'       => 'mautic.mapper.thead.title',
                    'class'      => 'col-category-title',
                    'default'    => true
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'mapper',
                    'orderBy'    => 'e.id',
                    'text'       => 'mautic.mapper.thead.id',
                    'class'      => 'visible-md visible-lg col-page-id'
                ));
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <a href="<?php echo $this->container->get('router')->generate(
                            'mautic_mapper_client_object_action', array(
                            "application"  => $application,
                            "client" => $client,
                            "object" => $item->getBaseName(),
                            "objectAction" => 'edit'
                        )); ?>"><?php echo $item->getBaseName(); ?></a>
                    </td>
                    <?php $entity = $item->getEntity(); ?>
                    <td class="visible-md visible-lg"><?php echo !$entity == null ? $entity->getId() : ''; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
