<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$searchUri   = $view['slots']->get('searchUri');
$actions     = $view['slots']->get('actions');
$toolbar     = $view['slots']->get('toolbar');
if (!empty($searchUri)):
    $bundle        = strtolower($app->getRequest()->get('bundle'));
    $searchString  = $view['slots']->get('searchString', '');
    $searchClass   = (!empty($searchString) ? 'show-search' : 'hide-search');
else:
    $searchClass = "no-search";
endif;
?>
<div class="toolbar-bundle-buttons pull-left"><?php echo $toolbar; ?></div>
<div class="toolbar-form-buttons hide pull-right"></div>
<div class="pull-right toolbar <?php echo $searchClass; ?>">
    <div class="input-group toolbar-action-buttons">
        <?php if (!empty($searchUri)): ?>
        <div class="input-group-btn">
            <button class="btn btn-default" data-toggle="modal" data-target="#search-help">
                <i class="fa fa-question-circle"></i>
            </button>
        </div>
        <input type="search"
            class="form-control search"
            id="list-search"
            name="search"
            placeholder="<?php echo $view['translator']->trans('mautic.core.form.search'); ?>"
            value="<?php echo $searchString; ?>"
            autocomplete="off"
            data-toggle="livesearch"
            data-target=".bundle-list"
            data-action="<?php echo $searchUri; ?>"
            data-overlay-text="<?php echo $view['translator']->trans('mautic.core.search.livesearch'); ?>"
            onmouseover="Mautic.showSearchInput()"
            onmouseout="Mautic.hideSearchInput('list-search');"
            onblur="Mautic.hideSearchInput('list-search');"
            />
        <?php endif; ?>
        <div class="input-group-btn">
            <?php if (!empty($searchUri)): ?>
            <button class="btn btn-default btn-search"
                    id="btn-filter"
                    data-livesearch-parent="list-search"
                    onmouseover="Mautic.showSearchInput();"
                    onmouseout="Mautic.hideSearchInput('list-search')">
                   <i class="fa fa-search fa-fw"></i>
            </button>
            <?php endif; ?>

            <?php if (!empty($actions)): ?>
            <button type="button" class="btn btn-default dropdown-toggle action-buttons" data-toggle="dropdown">
                <span><?php echo $view['translator']->trans('mautic.core.form.actions'); ?></span>
                <i class="fa fa-angle-down"></i>
            </button>

            <ul class="dropdown-menu pull-right">
                <?php echo $actions; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="clearfix"></div>
<?php
if (!empty($searchUri)):
    $view['slots']->start('modal');
    echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
        'id'     => 'search-help',
        'header' => $view['translator']->trans('mautic.core.search.header'),
        'body'   => $view['translator']->trans('mautic.core.search.help') .
            $view['slots']->get('searchHelp', '')
    ));
    $view['slots']->stop();
endif;
?>
