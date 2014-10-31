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
    <ul class="draggable scrollable">
        <?php
        if (count($items)):
        foreach ($items as $i):?>
            <li class="page-list-item has-click-event" id="form-<?php echo $i[0]->getId(); ?>">
                <div class="panel">
                    <div class="panel-body np box-layout">
                        <div class="height-auto icon bdr-r bg-dark-xs col-xs-1 text-center">
                            <i class="fa fa-fw fa-list"></i>
                        </div>
                        <div class="media-body col-xs-11 pa-10">
                            <?php echo $i[0]->getName(); ?>
                        </div>
                    </div>
                </div>
                <input type="hidden" class="page-token" value="{form=<?php echo $i[0]->getId(); ?>}" />
            </li>
        <?php endforeach; ?>
    </ul>

    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "totalItems"      => count($items),
        "page"            => $page,
        "limit"           => $limit,
        "fixedLimit"      => true,
        "baseUrl"         => $view['router']->generate('mautic_formtoken_index'),
        "paginationClass" => "xs",
        'sessionVar'      => 'formtoken',
        'ignoreFormExit'  => true,
        'queryString'     => 'tmpl=list'
    )); ?>
    <?php endif; ?>
</div>