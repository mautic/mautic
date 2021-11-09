<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<?php echo $view['content']->getCustomContent('details.top', $mauticTemplateVars); ?>
<?php if (method_exists($entity, 'getCategory')): ?>
<tr>
    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.core.category'); ?></span></td>
    <td><?php echo is_object($entity->getCategory()) ? $entity->getCategory()->getTitle() : $view['translator']->trans('mautic.core.form.uncategorized'); ?></td>
</tr>
<?php endif; ?>

<?php if (method_exists($entity, 'getCreatedByUser')): ?>
<tr>
    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.core.createdby'); ?></span></td>
    <td><?php echo $entity->getCreatedByUser(); ?></td>
</tr>
<tr>
    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.core.created'); ?></span></td>
    <td><?php echo $view['date']->toFull($entity->getDateAdded()); ?></td>
</tr>
<?php endif; ?>
<?php
if (method_exists($entity, 'getModifiedByUser')):
$modified = $entity->getModifiedByUser();
if ($modified):
    ?>
    <tr>
        <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.core.modifiedby'); ?></span></td>
        <td><?php echo $entity->getModifiedByUser(); ?></td>
    </tr>
    <tr>
        <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.core.modified'); ?></span></td>
        <td><?php echo $view['date']->toFull($entity->getDateModified()); ?></td>
    </tr>
<?php endif; ?>
<?php endif; ?>
<?php if (method_exists($entity, 'getPublishUp')): ?>
<tr>
    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.page.publish.up'); ?></span></td>
    <td><?php echo (!is_null($entity->getPublishUp())) ? $view['date']->toFull($entity->getPublishUp()) : $view['date']->toFull($entity->getDateAdded()); ?></td>
</tr>
<tr>
    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.page.publish.down'); ?></span></td>
    <td><?php echo (!is_null($entity->getPublishDown())) ? $view['date']->toFull($entity->getPublishDown()) : $view['translator']->trans('mautic.core.never'); ?></td>
</tr>
<?php endif; ?>
<?php if (method_exists($entity, 'getId')): ?>
    <tr>
        <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.core.id'); ?></span></td>
        <td><?php echo $entity->getId(); ?></td>
    </tr>
<?php endif; ?>
<?php echo $view['content']->getCustomContent('details.bottom', $mauticTemplateVars); ?>