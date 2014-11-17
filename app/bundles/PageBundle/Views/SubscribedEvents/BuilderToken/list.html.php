<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticPageBundle:SubscribedEvents\BuilderToken:index.html.php');
}
?>
<div id="pageBuilderTokens">
    <ul class="list-group">
        <?php
        if (count($items)):
        foreach ($items as $i):?>
            <li class="list-group-item" data-token="{pagelink=<?php echo $i->getId(); ?>}">
                <div>
                    <span><i class="fa fa-fw fa-file-text-o"></i><?php echo $i->getName() . ' (' . $i->getLanguage() . ')'; ?></span>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "totalItems"        => count($items),
        "page"              => $page,
        "limit"             => $limit,
        "fixedLimit"        => true,
        "baseUrl"           => $view['router']->generate('mautic_page_buildertoken_index'),
        "paginationWrapper" => 'text-center',
        "paginationClass"   => "sm",
        'sessionVar'        => 'page.buildertoken',
        'ignoreFormExit'    => true,
        'queryString'       => 'tmpl=list',
        'target'            => '#pageBuilderTokens'
    )); ?>
    <?php endif; ?>
</div>