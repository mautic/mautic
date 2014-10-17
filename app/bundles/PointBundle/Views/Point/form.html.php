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
<!-- start: box layout -->
<div class="box-layout">
    <!-- container -->
    <div class="col-md-9 bg-auto height-auto bdr-r">
        <div class="bdr-rds-0 bdr-w-0">
            <div class="pa-md bg-auto bg-light-xs bdr-b">
                <h4 class="fw-sb"><?php echo $header; ?></h4>
            </div>
            <div class="pa-md">
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
 	<div class="col-md-3 bg-white height-auto">
		<div class="pr-lg pl-lg pt-md pb-md">
			<?php
				echo $view['form']->row($form['category_lookup']);
				echo $view['form']->row($form['category']);
				echo $view['form']->row($form['isPublished']);
				echo $view['form']->row($form['publishUp']);
				echo $view['form']->row($form['publishDown']);
			?>
		</div>
	</div>
</div>
<?php echo $view['form']->end($form); ?>