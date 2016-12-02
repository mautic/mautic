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
    $view->extend('MauticFocusBundle:SubscribedEvents\BuilderToken:index.html.php');
}

$icons = [
    'form'         => 'fa-list',
    'click'        => 'fa-hand-o-right',
    'link'         => 'fa-hand-o-right',
    'notification' => 'fa-bullhorn',
];

?>
<div id="focusPageTokens">
    <?php if (count($items)): ?>
        <div class="list-group ma-5">
            <?php foreach ($items as $item):
                $token = $view->escape(
                    \Mautic\CoreBundle\Helper\BuilderTokenHelper::getVisualTokenHtml('{focus='.$item->getId().'}', $item->getName())
                )
                ?>
                <a href="#" class="list-group-item" data-token="<?php echo $token; ?>">
                    <div>
                        <span><i class="fa fa-fw <?php echo (array_key_exists($item->getType(), $icons)) ? $icons[$item->getType()] : ''; ?>"></i><?php echo $item->getName(); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php echo $view->render(
            'MauticCoreBundle:Helper:pagination.html.php',
            [
                'totalItems'        => count($items),
                'page'              => $page,
                'limit'             => $limit,
                'fixedLimit'        => true,
                'baseUrl'           => $view['router']->path('mautic_focus_pagetoken_index'),
                'paginationWrapper' => 'text-center',
                'paginationClass'   => 'sm',
                'sessionVar'        => 'mautic.focus.pagetoken',
                'ignoreFormExit'    => true,
                'queryString'       => 'tmpl=list',
                'target'            => '#focusPageTokens',
            ]
        ); ?>
    <?php endif; ?>
</div>