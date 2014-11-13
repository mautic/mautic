<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$searchBtnClass = (!empty($searchValue)) ? "fa-eraser" : "fa-search";
?>

<div class="input-group ma-5">
    <input type="search" class="form-control" id="assetBuilderTokenSearch" name="search" placeholder="<?php echo $view['translator']->trans('mautic.core.form.search'); ?>" value="<?php echo $searchValue; ?>" autocomplete="off" data-toggle="livesearch" data-target="#assetBuilderTokens" data-action="<?php echo $view['router']->generate('mautic_asset_buildertoken_index', array('page' => $page)); ?>" />
    <div class="input-group-btn">
        <button type="button" class="btn btn-default btn-search btn-filter btn-nospin" data-livesearch-parent="assetBuilderTokenSearch">
            <i class="fa <?php echo $searchBtnClass; ?> fa-fw"></i>
        </button>
    </div>
</div>
<?php $view['slots']->output('_content'); ?>