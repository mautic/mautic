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
    $view->extend('MauticLeadBundle:SubscribedEvents\BuilderToken:index.html.php');
}
?>
<div id="leadEmailTokens">
    <?php if (count($items)): ?>
    <div class="list-group">
        <?php
        foreach ($items as $i):
        $token = $view->escape(\Mautic\CoreBundle\Helper\BuilderTokenHelper::getVisualTokenHtml('{contactfield='.$i['alias'].'}', $i['label']))
        ?>
            <a href="#" class="list-group-item" data-token="<?php echo $token; ?>">
                <span><?php echo $i['label']; ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', [
        'totalItems'        => count($items),
        'page'              => $page,
        'limit'             => $limit,
        'fixedLimit'        => true,
        'baseUrl'           => $view['router']->path('mautic_contact_emailtoken_index'),
        'paginationWrapper' => 'text-center',
        'paginationClass'   => 'sm',
        'sessionVar'        => 'lead.emailtoken',
        'ignoreFormExit'    => true,
        'queryString'       => 'tmpl=list',
        'target'            => '#leadEmailTokens',
    ]); ?>
    <?php endif; ?>
</div>