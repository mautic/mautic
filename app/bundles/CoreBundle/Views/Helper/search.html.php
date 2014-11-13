<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$searchValue = (empty($searchValue)) ? '' : $searchValue;
$target      = (empty($target)) ? '.page-list' : $target;
?>

<div class="row">
	<div class="col-sm-8 nm pa5">
		<div class="input-group">
			<?php	// $view['slots']->set('searchHelp', $view['translator']->trans('mautic.lead.lead.help.searchcommands')); ?>
		<!--     <div class="input-group-btn">
		        <button class="btn btn-default" data-toggle="modal" data-target="#search-help">
		            <i class="fa fa-question-circle"></i>
		        </button>
		    </div> -->

            <input type="search" class="form-control search" id="list-search" name="search" placeholder="<?php echo $view['translator']->trans('mautic.core.form.search'); ?>" value="<?php echo $searchValue; ?>" autocomplete="off" data-toggle="livesearch" data-target="<?php echo $target; ?>" data-action="<?php echo $action; ?>" data-overlay="true" data-overlay-text="<?php echo $view['translator']->trans('mautic.core.search.livesearch'); ?>" />
			<div class="input-group-btn">
		        <button type="button" class="btn btn-default btn-search btn-nospin" id="btn-filter" data-livesearch-parent="list-search">
		            <i class="fa fa-search fa-fw"></i>
		        </button>
			</div>
		</div>

        <?php
        $view['slots']->append('modal', $view->render('MauticCoreBundle:Helper:modal.html.php', array(
            'id'     => 'search-help',
            'header' => $view['translator']->trans('mautic.core.search.header'),
            'body'   => $view['translator']->trans('mautic.core.search.help') .
                $view['slots']->get('searchHelp', '')
        )));
        ?>
	</div>
</div>