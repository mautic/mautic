<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view["slots"]->set("headerTitle", $view['translator']->trans('mautic.user.account.header.index'));
//populate JS functions only required for page refreshes
$view['slots']->set("jsDeclarations", "Mautic.ajaxifyForms(['user']);\n");
?>

<div class="account-wrapper">
    <div class="row padding-md">
        <div class="col-md-3 col-sm-12">
            <div class="body-white padding-md profile-details rounded-corners text-center">
                <img class="img img-responsive img-thumbnail"
                     src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($me->getEmail()))); ?>?&s=250" />
                <h3 class="margin-sm-sides margin-md-top"><?php echo $me->getName(); ?></h3>
                <h4 class="margin-sm-sides"><?php echo $me->getPosition(); ?></h4>
            </div>
        </div>

        <div class="col-lg-9 col-md-9 col-sm-12 padding-md-sides">
            <?php echo $view['form']->start($userForm); ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <?php echo $view['translator']->trans('mautic.user.account.header.details'); ?>
                    <div class="pull-right">
                        <button type="submit"
                                id ="btn-save-profile"
                                class="btn btn-success btn-xs"
                                data-toggle="tooltip"
                                data-container="body"
                                data-placement="top"
                                data-original-title="<?php echo $view['translator']->trans('mautic.core.form.save'); ?>">
                            <i class="fa fa-check"></i>
                        </button>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="col-md-6">
                        <?php
                        echo ($permissions['editUsername']) ? $view['form']->row($userForm['username']) : $view['form']->row($userForm['username_unbound']);
                        echo ($permissions['editName']) ? $view['form']->row($userForm['firstName']) : $view['form']->row($userForm['firstName_unbound']);
                        echo ($permissions['editName']) ? $view['form']->row($userForm['lastName']) : $view['form']->row($userForm['lastName_unbound']);
                        echo ($permissions['editPosition']) ? $view['form']->row($userForm['position']) : $view['form']->row($userForm['position_unbound']);
                        ?>
                    </div>
                    <div class="col-md-6">
                        <?php
                        echo ($permissions['editEmail']) ? $view['form']->row($userForm['email']) : $view['form']->row($userForm['email_unbound']);
                        echo $view['form']->row($userForm['currentPassword']);
                        echo $view['form']->row($userForm['plainPassword']['password']);
                        echo $view['form']->row($userForm['plainPassword']['confirm']);
                        ?>
                    </div>
                </div>
            </div>
            <?php echo $view['form']->end($userForm); ?>

            <?php if ($permissions['apiAccess']): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <?php echo $view['translator']->trans('mautic.user.account.header.authorizedclients'); ?>
                </div>
                <div class="panel-body">
                    <?php echo $view['actions']->render(
                        new Symfony\Component\HttpKernel\Controller\ControllerReference(
                            'MauticApiBundle:Client:authorizedClients'
                        ));
                        ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>