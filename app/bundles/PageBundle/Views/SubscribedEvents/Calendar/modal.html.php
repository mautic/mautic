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
<?php echo $view['form']->start($form); ?>
<?php echo $view['form']->errors($form) ?>
<?php echo $view['form']->row($form['publishUp']); ?>
<?php echo $view['form']->row($form['publishDown']); ?>
<?php echo $view['form']->end($form); ?>

<br />
<div class="text-center">
	<a href="<?php echo $view['router']->path('mautic_page_action',
        ['objectAction' => 'edit', 'objectId' => $entity->getId()]); ?>"
	    data-toggle="ajax">
	    <?php echo $view['translator']->trans('mautic.page.menu.edit'); ?>
	</a>
	|
	<a href="<?php echo $view['router']->path('mautic_page_action',
        ['objectAction' => 'view', 'objectId' => $entity->getId()]); ?>"
	    data-toggle="ajax">
	    <?php echo $view['translator']->trans('mautic.core.details'); ?>
	</a>
	|
	<a href="<?php echo $model->generateUrl($entity); ?>" target="_blank">
	    <?php echo $view['translator']->trans('mautic.page.menu.view'); ?>
	</a>
</div>
