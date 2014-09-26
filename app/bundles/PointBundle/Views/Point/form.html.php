<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'point');

$header = ($entity->getId()) ?
    $view['translator']->trans('mautic.point.header.edit',
        array('%name%' => $view['translator']->trans($entity->getName()))) :
    $view['translator']->trans('mautic.point.header.new');
$view['slots']->set("headerTitle", $header);
echo $view['form']->start($form);
?>
<div class="col-md-8" id="pointActionProperties">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h4 class="panel-title">
				<?php echo $header; ?>
			</h4>
		</div>
		<div class="panel-body">
		    <?php		    
			echo $view['form']->row($form['name']);
			echo $view['form']->row($form['description']);
			echo $view['form']->row($form['type']);

		    if (isset($form['properties'])):
		    	echo $view['form']->row($form['properties']);
		    endif;
		    ?>
		</div>
	</div>
</div>
<div class="col-md-4">
	<?php
	echo $view['form']->row($form['category_lookup']);
	echo $view['form']->row($form['category']);
	echo $view['form']->row($form['isPublished']);
	echo $view['form']->row($form['publishUp']);
	echo $view['form']->row($form['publishDown']);
	?>
</div>
<?php echo $view['form']->end($form); ?>