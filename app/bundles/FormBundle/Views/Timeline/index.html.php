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
	<div class="figure"><span class="icon fa fa-pencil-square-o"></span></div>
	<div class="panel">
	    <div class="panel-body">
	    	<h3>
	    		<a href="<?php echo $view['router']->generate('mautic_form_action',
				    array("objectAction" => "view", "objectId" => $form->getId())); ?>"
				   data-toggle="ajax">
				    <?php echo $form->getName(); ?>
				</a>
			</h3>
	        <p class="mb-0">At <?php echo $view['date']->toFullConcat($event['timestamp']); ?>, <?php echo $event['event']; ?>.</p>
	    </div>
	    <?php if (isset($event['extra'])) : ?>
	        <div class="panel-footer">
	        	<?php if ($page->getId()) : ?>
	            <p>
	            	From submitted from
	            	<a href="<?php echo $view['router']->generate('mautic_page_action',
					    array("objectAction" => "view", "objectId" => $page->getId())); ?>"
					   data-toggle="ajax">
					    <?php echo $page->getTitle(); ?>
					</a>
				</p>
				<?php endif; ?>
				<?php if (isset($event['extra'])) : ?>
	            <p>
	            	<strong>Form description:</strong>
	            	<?php echo $form->getDescription(); ?>
				</p>
				<?php endif; ?>
	        </div>
	    <?php endif; ?>
	</div>
</li>
