<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticLeadBundle:SubscribedEvents\EmailToken:index.html.php');
}
?>
<div id="leadEmailTokens">
    <ul class="list-group">
        <?php
        if (count($items)):
        foreach ($items as $i):?>
            <li class="list-group-item" data-token="{leadfield=<?php echo $i['alias']; ?>}">
                <div>
                    <span><?php echo $i['label']; ?></span>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "totalItems"        => count($items),
        "page"              => $page,
        "limit"             => $limit,
        "fixedLimit"        => true,
        "baseUrl"           => $view['router']->generate('mautic_lead_emailtoken_index'),
        "paginationWrapper" => 'text-center',
        "paginationClass"   => "sm",
        'sessionVar'        => 'lead.emailtoken',
        'ignoreFormExit'    => true,
        'queryString'       => 'tmpl=list',
        'target'            => '#leadEmailTokens'
    )); ?>
    <?php endif; ?>
</div>