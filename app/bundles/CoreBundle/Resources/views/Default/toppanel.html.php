<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="page-header-section">
    <?php echo $view->render('MauticCoreBundle:Default:breadcrumbs.html.php'); ?>
</div>

<?php if ($view['blocks']->has("actions")): ?>
<div class="page-header-section">
    <div class="toolbar">
        <?php echo $view['blocks']->output('actions'); ?>
    </div>
</div>
<?php endif; ?>

<?php /*

        <div class="page-header-section">
            <h4 class="title semibold"><?php $view['blocks']->output("headerTitle"); ?></h4>
        </div>


<div class="panel-toggle left-panel-toggle pull-left">
    <a href="javascript: void(0);" onclick="Mautic.toggleSidePanel('left');"><i class="fa fa-bars fa-2x"></i></a>
</div>

            <li class="dropdown">
                <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                    <?php echo $view['translator']->trans('mautic.core.admin'); ?><i class="fa fa-lg fa-fw fa-angle-double-down"></i>
                </a>
                <ul class="dropdown-menu pull-right">
                    <?php if ($security->isGranted("user:users:view")): ?>
                        <li>
                            <a href="<?php echo $view['router']->generate("mautic_user_index"); ?>" data-toggle="ajax">
                                <i class="fa fa-users fa-lg fa-fw"></i><span><?php echo $view["translator"]->trans("mautic.user.user.menu.index"); ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($security->isGranted("user:roles:view")): ?>
                        <li>
                            <a href="<?php echo $view['router']->generate("mautic_role_index"); ?>" data-toggle="ajax">
                                <i class="fa fa-lock fa-lg fa-fw"></i><span><?php echo $view["translator"]->trans("mautic.user.role.menu.index"); ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($security->isGranted("api:clients:view")): ?>
                        <li>
                            <a href="<?php echo $view['router']->generate("mautic_client_index"); ?>" data-toggle="ajax">
                                <i class="fa fa-puzzle-piece fa-lg fa-fw"></i><span><?php echo $view["translator"]->trans("mautic.api.client.menu.index"); ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>



            </li>
        </ul>
 */