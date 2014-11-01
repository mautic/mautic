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
<div id="form-page-tokens">
    <ul class="draggable scrollable list-unstyled">
        <?php
        if (count($items)):
        foreach ($items as $i):?>
            <li class="ma-5 page-list-item has-click-event" id="form-<?php echo $i[0]->getId(); ?>">
                <div class="panel pa-5">
                    <i class="fa fa-fw fa-list"></i>
                    <?php echo $i[0]->getName(); ?>
                </div>
                <input type="hidden" class="page-token" value="{form=<?php echo $i[0]->getId(); ?>}" />
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
        'queryString'       => 'tmpl=list'
    )); ?>
    <?php endif; ?>
</div>