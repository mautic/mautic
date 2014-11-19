<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$form = $event['extra']['form'];
$page = $event['extra']['page'];

?>

<li class="wrapper form-submitted">
	<div class="figure"><span class="fa <?php echo isset($icons['form']) ? $icons['form'] : '' ?>"></span></div>
	<div class="panel">
	    <div class="panel-body">
	    	<h3>
	    		<a href="<?php echo $view['router']->generate('mautic_form_action',
				    array("objectAction" => "view", "objectId" => $form->getId())); ?>"
				   data-toggle="ajax">
				    <?php echo $form->getName(); ?>
				</a>
			</h3>
	        <p class="mb-0"><?php echo $view['translator']->trans('mautic.form.timeline.event.time', array('%date%' => $view['date']->toFullConcat($event['timestamp']), '%event%' => $event['eventLabel'])); ?></p>
	    </div>
	    <?php if (isset($event['extra'])) : ?>
	        <div class="panel-footer">
	        	<?php if ($page->getId()) : ?>
                <?php $link = '<a href="' . $view['router']->generate('mautic_page_action', array("objectAction" => "view", "objectId" => $page->getId())) . '" data-toggle="ajax">' . $page->getTitle() . '</a>'; ?>
	            <p><?php echo $view['translator']->trans('mautic.form.timeline.event.submitted', array('%link%' => $link)); ?></p>
				<?php endif; ?>
				<?php if (isset($event['extra'])) : ?>
	            <p><?php echo $view['translator']->trans('mautic.form.timeline.event.description', array('%description%' => $form->getDescription())); ?></p>
				<?php endif; ?>
	        </div>
	    <?php endif; ?>
	</div>
</li>
