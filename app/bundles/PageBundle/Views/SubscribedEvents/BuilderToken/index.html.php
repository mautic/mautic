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
    <input type="search" class="form-control" id="pageBuilderTokenSearch" name="search" placeholder="<?php echo $view['translator']->trans('mautic.core.form.search'); ?>" value="<?php echo $searchValue; ?>" autocomplete="off" data-toggle="livesearch" data-target="#pageBuilderTokens" data-action="<?php echo $view['router']->generate('mautic_page_buildertoken_index', array('page' => $page)); ?>" />
    <div class="input-group-btn">
        <button type="button" class="btn btn-default btn-search btn-filter btn-nospin" data-livesearch-parent="pageBuilderTokenSearch">
            <i class="fa <?php echo $searchBtnClass; ?> fa-fw"></i>
        </button>
    </div>
</div>
<?php $view['slots']->output('_content'); ?>

<ul class="list-group mt-sm">
    <li class="list-group-item" data-token="{externallink=%url%}" data-predrop="showPageBuilderTokenExternalLinkModal">
        <div class="padding-sm">
            <span><i class="fa fa-external-link fa-fw"></i><?php echo $view['translator']->trans('mautic.page.builder.externallink'); ?></span>
        </div>
    </li>
</ul>

<?php echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id'     => 'ExternalLinkModal',
    'header' => false,
    'body'   =>
<<<BODY
<div class="row">
    <div class="col-lg-12">
        <div class="input-group">
            <input name="link" type="text" class="form-control" placeholder="{$view['translator']->trans('mautic.page.builder.externallink.placeholder')}" />
            <span class="input-group-btn">
                <button class="btn btn-default" onclick="Mautic.insertPageBuilderTokenExternalUrl();" type="button">{$view['translator']->trans('mautic.page.builder.externallink.insert')}</button>
            </span>
        </div>
    </div>
    <input type="hidden" name="editor" value="" />
    <input type="hidden" name="token" value="" />
</div>
BODY
)); ?>