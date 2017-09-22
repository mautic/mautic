<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/** @var \Mautic\LeadBundle\Entity\Import $item */
?>
<?php foreach ($items as $item): ?>
    <tr>
        <td class="col-actions text-center">
            <span class="label label-<?php echo $item->getSatusLabelClass(); ?>">
                <?php echo $view['translator']->trans('mautic.lead.import.status.'.$item->getStatus()); ?>
            </span>
        </td>
        <td>
            <div>
                <?php if (!in_array($item->getStatus(), [$item::FAILED, $item::IMPORTED, $item::MANUAL]) && $permissions[$permissionBase.':publish']): ?>
                <?php echo $view->render(
                    'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                    ['item' => $item, 'model' => 'lead.import']
                ); ?>
                <?php endif; ?>
                <?php if ($view['security']->hasEntityAccess(true, $permissions[$permissionBase.':viewother'], $item->getCreatedBy())) : ?>
                    <a href="<?php echo $view['router']->path(
                        $actionRoute,
                        ['objectAction' => 'view', 'objectId' => $item->getId(), 'object' => $app->getRequest()->get('object', 'contacts')]
                    ); ?>" data-toggle="ajax">
                        <?php echo $item->getName(); ?>
                    </a>
                <?php else : ?>
                    <?php echo $item->getName(); ?>
                <?php endif; ?>
            </div>
        </td>
        <td class="visible-md visible-lg"><?php echo $item->getRunTime() ? $view['date']->formatRange($item->getRunTime()) : ''; ?></td>
        <td class="visible-md visible-lg"><?php echo $item->getProgressPercentage(); ?>%</td>
        <td class="visible-md visible-lg"><?php echo $item->getLineCount(); ?></td>
        <td class="visible-md visible-lg"><?php echo $item->getInsertedCount(); ?></td>
        <td class="visible-md visible-lg"><?php echo $item->getUpdatedCount(); ?></td>
        <td class="visible-md visible-lg"><?php echo $item->getIgnoredCount(); ?></td>
        <td class="visible-md visible-lg">
            <abbr title="<?php echo $view['date']->toFull($item->getDateAdded()); ?>">
                <?php echo $view['date']->toText($item->getDateAdded()); ?>
            </abbr>
        </td>
        <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
    </tr>
<?php endforeach; ?>
