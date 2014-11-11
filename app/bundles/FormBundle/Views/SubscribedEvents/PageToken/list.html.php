<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticFormBundle:SubscribedEvents\PageToken:index.html.php');
}
?>
<div id="formTokens">
    <ul class="list-group">
        <?php
        if (count($items)):
        foreach ($items as $i):?>
            <li class="list-group-item" data-token="{form=<?php echo $i[0]->getId(); ?>}">
                <div class="padding-sm">
                    <span><i class="fa fa-fw fa-list"></i><?php echo $i[0]->getName(); ?></span>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "totalItems"        => count($items),
        "page"              => $page,
        "limit"             => $limit,
        "fixedLimit"        => true,
        "baseUrl"           => $view['router']->generate('mautic_formtoken_index'),
        "paginationWrapper" => 'text-center',
        "paginationClass"   => "xs",
        'sessionVar'        => 'formtoken',
        'ignoreFormExit'    => true,
        'queryString'       => 'tmpl=list',
        'target'            => '#formTokens'
    )); ?>
    <?php endif; ?>
</div>