<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


$view['slots']->set('searchString', $app->getSession()->get('mautic.lead.filter'));

$bundle        = strtolower($app->getRequest()->get('bundle'));
$searchString  = $view['slots']->get('searchString', '');
$searchClass   = (!empty($searchString) ? 'show-search' : 'hide-search');

?>

<div class="row">
	<div class="col-sm-6 pull-right nm pa5">
		<div class="input-group">
			<?php	// $view['slots']->set('searchHelp', $view['translator']->trans('mautic.lead.lead.help.searchcommands')); ?>
		<!--     <div class="input-group-btn">
		        <button class="btn btn-default" data-toggle="modal" data-target="#search-help">
		            <i class="fa fa-question-circle"></i>
		        </button>
		    </div> -->
		    <input type="search"
		           class="form-control search"
		           id="list-search"
		           name="search"
		           placeholder="<?php echo $view['translator']->trans('mautic.core.form.search'); ?>"
		           value="<?php echo $searchString; ?>"
		           autocomplete="off"
		           data-toggle="livesearch"
		           data-target=".page-list"
		           data-action="<?php echo $app->getRequest()->getUri(); ?>"
		           data-overlay-text="<?php echo $view['translator']->trans('mautic.core.search.livesearch'); ?>"
		           data-overlay-target="#main-content"
		           onmouseover="Mautic.showSearchInput()"
		           onmouseout="Mautic.hideSearchInput('list-search');"
		           onblur="Mautic.hideSearchInput('list-search');"
		        />

			<div class="input-group-btn">
		        <button class="btn btn-default btn-search"
		                id="btn-filter"
		                data-livesearch-parent="list-search"
		                onmouseover="Mautic.showSearchInput();"
		                onmouseout="Mautic.hideSearchInput('list-search')">
		            <i class="fa fa-search fa-fw"></i>
		        </button>
			</div>

			<?php
				$view['slots']->start('modal');
		        echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
		            'id'     => 'search-help',
		            'header' => $view['translator']->trans('mautic.core.search.header'),
		            'body'   => $view['translator']->trans('mautic.core.search.help') .
		                $view['slots']->get('searchHelp', '')
		        ));
		        $view['slots']->stop();
		    ?>
		</div>
	</div>
</div>