<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<ul class="nav nav-pills navbar-right" role="navigation">
    <li class="dropdown">
        <a class="dropdown-toggle user-menu" data-toggle="dropdown" href="#">
            <span class="user-menu-username"><?php echo $app->getUser()->getName();?></span>
            <span class="user-menu-icon"><i class="fa fa-lg fa-fw fa-user"></i></span>
        </a>
        <ul class="pull-right dropdown-menu">
            <li>
                <a href="<?php echo $view['router']->generate("mautic_user_account"); ?>" data-toggle="ajax">
                    <i class="fa fa-cog fa-lg fa-fw"></i><span><?php echo $view["translator"]->trans("mautic.user.account.settings"); ?></span>
                </a>
            </li>
            <li>
                <a href="<?php echo $view['router']->generate("mautic_user_logout"); ?>">
                    <i class="fa fa-sign-out fa-lg fa-fw"></i><span><?php echo $view["translator"]->trans("mautic.user.auth.logout"); ?></span>
                </a>
            </li>
        </ul>
    </li>
    <li class="panel-toggle right-panel-toggle">
        <a href="javascript: void(0);" onclick="Mautic.toggleSidePanel('right');"><i class="fa fa-bars fa-2x"></i></a>
    </li>
</ul>