<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$security = $view->container->get('mautic.security');

$display = ($security->isGranted("user:users:view") || $security->isGranted("user:roles:view") ||
    $security->isGranted("api:clients:view"));
?>

<?php if ($display): ?>
<ul class="nav nav-pills navbar-left admin-menu" role="navigation">
    <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
            <?php echo $view['translator']->trans('mautic.core.admin'); ?><span class="caret"></span>
        </a>
        <ul class="dropdown-menu">
            <?php if ($security->isGranted("user:users:view")): ?>
            <li>
                <a href="javascript:void(0);"
                   onclick="Mautic.loadContent('<?php echo $view['router']->generate("mautic_user_index"); ?>');">
                    <i class="fa fa-users fa-lg fa-fw"></i><span><?php echo $view["translator"]->trans("mautic.user.user.menu.index"); ?></span>
                </a>
            </li>
            <?php endif; ?>
            <?php if ($security->isGranted("user:roles:view")): ?>
                <li>
                    <a href="javascript:void(0);"
                       onclick="Mautic.loadContent('<?php echo $view['router']->generate("mautic_role_index"); ?>');">
                        <i class="fa fa-lock fa-lg fa-fw"></i><span><?php echo $view["translator"]->trans("mautic.user.role.menu.index"); ?></span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if ($security->isGranted("api:clients:view")): ?>
                <li>
                    <a href="javascript:void(0);"
                       onclick="Mautic.loadContent('<?php echo $view['router']->generate("mautic_client_index"); ?>');">
                        <i class="fa fa-puzzle-piece fa-lg fa-fw"></i><span><?php echo $view["translator"]->trans("mautic.api.client.menu.index"); ?></span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </li>
</ul>
<?php endif; ?>