<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticAssetBundle:SubscribedEvents\BuilderToken:index.html.php');
}
?>
<div id="assetBuilderTokens">
    <?php if (count($items)) : ?>
    <div class="list-group">
        <?php foreach ($items as $i) : ?>
            <a href="#" class="list-group-item" data-token='<a href="%url={assetlink=<?php echo $i->getId(); ?>}%">%text=<?php echo $view->escape($i->getName()); ?>%</a>' data-drop="showBuilderLinkModal">
                <div>
                    <span><i class="fa fa-fw fa-file-o"></i><?php echo $view->escape($i->getName()).' ('.$i->getLanguage().')'; ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', [
        'totalItems'        => count($items),
        'page'              => $page,
        'limit'             => $limit,
        'fixedLimit'        => true,
        'baseUrl'           => $view['router']->path('mautic_asset_buildertoken_index'),
        'paginationWrapper' => 'text-center',
        'paginationClass'   => 'sm',
        'sessionVar'        => 'asset.buildertoken',
        'ignoreFormExit'    => true,
        'queryString'       => 'tmpl=list',
        'target'            => '#assetBuilderTokens',
    ]); ?>
    <?php endif; ?>
</div>