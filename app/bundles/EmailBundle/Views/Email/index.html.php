<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'email');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.email.emails'));

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array(
    'templateButtons' => array(
        'new'    => $permissions['email:emails:create']
    ),
    'routeBase' => 'email'
)));

$tabs = array('template', 'list');
?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <!-- tabs controls -->
    <ul class="nav nav-tabs pr-md pl-md bg-auto">
        <?php foreach ($tabs as $k => $tab): ?>
        <li<?php if ($k === 0) echo ' class="active"'; ?>>
            <a href="#tab-<?php echo $tab; ?>" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.email.type.' . $tab); ?></a>
        </li>
        <?php endforeach; ?>
    </ul>
    <!--/ tabs controls -->
    <div class="tab-content">
        <?php foreach ($tabs as $k => $tab): ?>
        <div class="tab-pane fade bdr-w-0<?php if ($k === 0) echo ' in active'; ?>" id="tab-<?php echo $tab; ?>">
            <div class="pl-0 pr-0 bg-auto bdr-l">
                <div class="panel panel-default bdr-t-wdh-0 bdr-l-wdh-0 mb-0">
                    <?php echo $view->render('MauticCoreBundle:Helper:list_toolbar.html.php', array(
                        'searchValue' => ${$tab}['searchValue'],
                        'searchHelp'  => 'mautic.email.help.searchcommands',
                        'searchId'    => $tab . '-search',
                        'action'      => $currentRoute,
                        'routeBase'   => 'email',
                        'templateButtons' => array(
                            'delete' => $permissions['email:emails:deleteown'] || $permissions['email:emails:deleteother']
                        ),
                        'target'      => '.' . $tab . '-container',
                        'tmpl'        => $tab,
                        'filters'     => ${$tab}['filters']
                    )); ?>

                    <div class="<?php echo $tab; ?>-container">
                        <?php echo $view->render('MauticEmailBundle:Email:' . $tab . '.html.php', array(
                            'items'       => ${$tab}['items'],
                            'totalItems'  => ${$tab}['totalItems'],
                            'page'        => ${$tab}['page'],
                            'limit'       => ${$tab}['limit'],
                            'permissions' => $permissions,
                            'security'    => $security,
                            'model'       => $model
                        ));?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

