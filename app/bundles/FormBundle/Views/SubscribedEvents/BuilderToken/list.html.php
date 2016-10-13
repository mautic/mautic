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
    $view->extend('MauticFormBundle:SubscribedEvents\BuilderToken:index.html.php');
}
?>
<div id="formPageTokens">
    <?php if (count($items)): ?>
    <div class="list-group ma-5">
        <?php foreach ($items as $i):
        $token = $view->escape(\Mautic\CoreBundle\Helper\BuilderTokenHelper::getVisualTokenHtml('{form='.$i[0]->getId().'}', $i[0]->getName()))
        ?>
            <a href="#" class="list-group-item" data-token="<?php echo $token; ?>">
                <div>
                    <span><i class="fa fa-fw fa-list"></i><?php echo $i[0]->getName(); ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', [
        'totalItems'        => count($items),
        'page'              => $page,
        'limit'             => $limit,
        'fixedLimit'        => true,
        'baseUrl'           => $view['router']->path('mautic_form_pagetoken_index'),
        'paginationWrapper' => 'text-center',
        'paginationClass'   => 'sm',
        'sessionVar'        => 'form.pagetoken',
        'ignoreFormExit'    => true,
        'queryString'       => 'tmpl=list',
        'target'            => '#formEmailTokens',
    ]); ?>
    <?php endif; ?>
</div>