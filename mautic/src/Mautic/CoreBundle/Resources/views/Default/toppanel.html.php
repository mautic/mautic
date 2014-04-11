<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel-toggle left-panel-toggle pull-left">
    <a href="javascript: void(0);" onclick="Mautic.toggleSidePanel('left');"><i class="fa fa-bars fa-2x"></i></a>
</div>

<div class="top-panel-main pull-left"></div>

<div class="pull-right account-menu">
    <ul class="nav nav-pills navbar-right" role="navigation">
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                <?php echo $app->getUser()->getName();?><span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
                <li>
                    <a href="javascript:void(0);"
                       onclick="Mautic.loadContent('<?php echo $view['router']->generate("mautic_user_account"); ?>');">
                        <i class="fa fa-cog fa-lg"></i><span><?php echo $view["translator"]->trans("mautic.user.account.settings"); ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $view['router']->generate("mautic_user_logout"); ?>">
                        <i class="fa fa-sign-out fa-lg"></i><span><?php echo $view["translator"]->trans("mautic.user.auth.logout"); ?></span>
                    </a>
                </li>
            </ul>
        </li>
        <li class="panel-toggle right-panel-toggle">
            <a href="javascript: void(0);" onclick="Mautic.toggleSidePanel('right');"><i class="fa fa-bars fa-2x"></i></a>
        </li>
    </ul>
</div>