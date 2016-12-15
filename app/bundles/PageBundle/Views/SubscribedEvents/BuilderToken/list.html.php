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
    $view->extend('MauticPageBundle:SubscribedEvents\BuilderToken:index.html.php');
}
?>
<div id="pageBuilderTokens">
    <?php if (count($items)): ?>
    <div class="list-group ma-5">
        <?php foreach ($items as $i): ?>
            <a href="#" class="list-group-item" data-token='<a href="%url={pagelink=<?php echo $i->getId(); ?>}%">%text=<?php echo $i->getName(); ?>%</a>' data-drop="showBuilderLinkModal">
                <span><i class="fa fa-fw fa-file-text-o"></i><?php echo $i->getName().' ('.$i->getLanguage().')'; ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', [
        'totalItems'        => count($items),
        'page'              => $page,
        'limit'             => $limit,
        'fixedLimit'        => true,
        'baseUrl'           => $view['router']->path('mautic_page_buildertoken_index'),
        'paginationWrapper' => 'text-center',
        'paginationClass'   => 'sm',
        'sessionVar'        => 'page.buildertoken',
        'ignoreFormExit'    => true,
        'queryString'       => 'tmpl=list',
        'target'            => '#pageBuilderTokens',
    ]); ?>
    <?php endif; ?>
</div>