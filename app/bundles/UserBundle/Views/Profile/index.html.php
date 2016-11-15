<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/** @var \Mautic\UserBundle\Entity\User $me */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'user');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.user.account.settings'));
?>
<!-- start: box layout -->
<div class="box-layout">
           <!-- step container -->
    <div class="col-md-3 bg-white height-auto">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php if ($me->getId()): ?>
                <div class="media">
                    <div class="pull-left">
                        <img class="img-rounded img-bordered media-object" src="<?php echo $view['gravatar']->getImage($me->getEmail()); ?>" alt="" width="65px">
                    </div>
                    <div class="media-body">
                        <h4><?php echo $me->getName(); ?></h4>
                        <h5><?php echo $me->getPosition(); ?></h5>
                    </div>
                </div>
                <hr />
            <?php endif; ?>

            <ul class="list-group list-group-tabs">
                <li class="list-group-item active">
                    <a href="#profile" class="steps" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.user.account.header.details'); ?>
                    </a>
                </li>
                <?php if ($permissions['apiAccess']): ?>
                <li class="list-group-item">
                    <a href="#clients" class="steps" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.user.account.header.authorizedclients'); ?>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <!--/ step container -->

    <!-- container -->
    <div class="col-md-9 bg-auto height-auto bdr-l">
        <div class="tab-content">
            <div class="tab-pane fade in active bdr-rds-0 bdr-w-0" id="profile">
                <?php echo $view['form']->start($userForm); ?>
                <div class="pa-md bg-auto bg-light-xs bdr-b">
                    <h4 class="fw-sb"><?php echo $view['translator']->trans('mautic.user.account.header.details'); ?></h4>
                </div>
                <div class="pa-md">
                    <div class="col-md-6">
                        <?php
                        echo ($permissions['editUsername']) ? $view['form']->row($userForm['username']) : $view['form']->row($userForm['username_unbound']);
                        echo ($permissions['editName']) ? $view['form']->row($userForm['firstName']) : $view['form']->row($userForm['firstName_unbound']);
                        echo ($permissions['editName']) ? $view['form']->row($userForm['lastName']) : $view['form']->row($userForm['lastName_unbound']);
                        echo ($permissions['editPosition']) ? $view['form']->row($userForm['position']) : $view['form']->row($userForm['position_unbound']);
                        echo ($permissions['editEmail']) ? $view['form']->row($userForm['email']) : $view['form']->row($userForm['email_unbound']);
                        ?>
                    </div>
                    <div class="col-md-6">
                        <?php
                        echo $view['form']->row($userForm['timezone']);
                        echo $view['form']->row($userForm['locale']);
                        echo $view['form']->row($userForm['plainPassword']['password']);
                        echo $view['form']->row($userForm['plainPassword']['confirm']);
                        echo $view['form']->row($userForm['signature']);
                        ?>
                    </div>
                </div>
                <?php echo $view['form']->end($userForm); ?>
            </div>

            <?php if ($permissions['apiAccess']): ?>
                <div class="tab-pane fade bdr-rds-0 bdr-w-0" id="clients">
                    <div class="pa-md bg-auto bg-light-xs bdr-b">
                        <h4 class="fw-sb"><?php echo $view['translator']->trans('mautic.user.account.header.authorizedclients'); ?></h4>
                    </div>
                    <div class="pa-md">
                        <?php echo $authorizedClients; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!--/ end: container -->
</div>
<!--/ end: box layout -->
