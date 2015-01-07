<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticFormBundle:SubscribedEvents\PageToken:index.html.php');
}
?>
<div id="formPageTokens">
    <div class="list-group ma-5">
        <?php
        if (count($items)):
        foreach ($items as $i):?>
            <a href="#" class="list-group-item" data-token="{form=<?php echo $i[0]->getId(); ?>}">
                <div>
                    <span><i class="fa fa-fw fa-list"></i><?php echo $i[0]->getName(); ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "totalItems"        => count($items),
        "page"              => $page,
        "limit"             => $limit,
        "fixedLimit"        => true,
        "baseUrl"           => $view['router']->generate('mautic_form_pagetoken_index'),
        "paginationWrapper" => 'text-center',
        "paginationClass"   => "sm",
        'sessionVar'        => 'form.pagetoken',
        'ignoreFormExit'    => true,
        'queryString'       => 'tmpl=list',
        'target'            => '#formEmailTokens'
    )); ?>
    <?php endif; ?>
</div>