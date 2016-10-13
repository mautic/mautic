<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$searchBtnClass = (!empty($searchValue)) ? 'fa-eraser' : 'fa-search';
?>
    <div class="input-group ma-5">
        <input type="search" class="form-control" id="focusPageTokenSearch" name="search" placeholder="<?php echo $view['translator']->trans(
            'mautic.core.search.placeholder'
        ); ?>" value="<?php echo $searchValue; ?>" autocomplete="off" data-toggle="livesearch" data-target="#focusPageTokens" data-action="<?php echo $view['router']->path(
            'mautic_focus_pagetoken_index',
            ['page' => $page]
        ); ?>"/>
        <div class="input-group-btn">
            <button type="button" class="btn btn-default btn-search btn-filter" data-livesearch-parent="focusPageTokenSearch">
                <i class="fa <?php echo $searchBtnClass; ?> fa-fw"></i>
            </button>
        </div>
    </div>
<?php $view['slots']->output('_content'); ?>