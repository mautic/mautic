<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>
<td>
    <a href="<?php echo $view['router']->path('mautic_contact_action', ['objectAction' => 'view', 'objectId' => $item->getId()]); ?>" data-toggle="ajax">
        <?php if (in_array($item->getId(), array_keys($noContactList)))  : ?>
            <div class="pull-right">
                <?php echo $view->render('MauticLeadBundle:Lead:dnc_small.html.php', [
                    'dncList'         => $noContactList[$item->getId()],
                ]); ?>
            </div>
        <?php endif; ?>
        <?php $primaryIdentifier = $view->escape(($item->isAnonymous() ? $view['translator']->trans($item->getPrimaryIdentifier()) : $item->getPrimaryIdentifier())); ?>
        <div><?php echo $primaryIdentifier; ?></div>
        <?php if (!array_key_exists('company', $columns) && $primaryIdentifier != $item->getSecondaryIdentifier() && $item->getSecondaryIdentifier()): ?>
            <div class="small"><?php echo $view->escape($item->getSecondaryIdentifier()); ?></div>
        <?php endif; ?>
    </a>
</td>
