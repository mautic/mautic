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
<div class="hidden-shelf">
    <div class="shelf-contents collapse">
        <div class="input-group">
            <input type="search"
                   class="form-control"
                   id="form-token-search" name="search"
                   placeholder="<?php echo $view['translator']->trans('mautic.core.form.search'); ?>"
                   value="<?php echo $searchValue; ?>"
                   autocomplete="off"
                   data-toggle="livesearch"
                   data-target="#form-page-tokens"
                   data-action="<?php echo $view['router']->generate('mautic_formtoken_index', array('page' => $page)); ?>"
                   data-overlay="false"
                />
            <div class="input-group-btn">
                <button class="btn btn-default btn-search btn-filter"
                        data-livesearch-parent="form-token-search">
                    <i class="fa <?php echo $searchBtnClass; ?> fa-fw"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="shelf-handle">
        <i class="fa fa-chevron-circle-down"></i>
    </div>
</div>
<?php $view['slots']->output('_content'); ?>