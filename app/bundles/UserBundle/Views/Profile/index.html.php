<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/** @var \Mautic\UserBundle\Entity\User $me */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'user');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.user.account.header.index'));
?>

    <!-- start: box layout -->
    <div class="box-layout">
        <!-- left section -->
        <div class="col-md-3 bg-white height-auto">
            <div class="panel bdr-rds-0 bdr-w-0 shd-none mb-0">
                <!-- profile -->
                <div class="panel-body text-center pt-lg pb-lg">
                    <span class="img-wrapper img-rounded mb-md" style="width:72px;">
                        <img src="<?php echo $view['gravatar']->getImage($me->getEmail()); ?>" alt="">
                    </span>
                    <h4 class="fw-sb"><?php echo $me->getName(); ?></h4>
                    <p class="mb-md"><em><?php echo $me->getPosition(); ?></em></p>
                    <a href="" class="btn btn-danger">Button</a>
                </div>
                <!--/ profile -->

                <!-- menu -->
                <div class="list-group bdr-b-xs">
                    <a href="" class="list-group-item">
                        <span class="fa fa-user mr-xs fs-14"></span> Profile
                    </a>
                    <a href="" class="list-group-item">
                        <span class="fa fa-briefcase mr-xs fs-14"></span> Account
                    </a>
                    <a href="" class="list-group-item">
                        <span class="fa fa-shield mr-xs fs-14"></span> Security And Privacy
                    </a>
                    <a href="" class="list-group-item">
                        <span class="fa fa-sliders mr-xs fs-14"></span> Applications
                    </a>
                    <a href="" class="list-group-item">
                        <span class="fa fa-key mr-xs fs-14"></span> Password
                    </a>
                </div>
                <!--/ menu -->
            </div>
        </div>
        <!--/ left section -->

        <!-- right section -->
        <div class="col-md-9 bg-auto bdr-l height-auto">
            <!-- header -->
            <div class="page-header">
                <!-- profile heading -->
                <div ng-show="$state.includes('default.app.setting.profile')">
                    <h4 class="fw-sb">Profile</h4>
                    <p class="text-muted mb-0">This information appears on your public profile, search results, and beyond.</p>
                </div>
                <!--/ profile heading -->
            </div>
            <!--/ header -->

            <!-- wrapper -->
            <div class="pa-md">
                <!-- form -->
                <form class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Photo</label>
                        <div class="col-sm-6">
                            <span class="img-wrapper img-rounded" style="width:32px">
                                <img src="<?php echo $view['gravatar']->getImage($me->getEmail()); ?>" alt="" />
                            </span>
                            <div class="btn-group va-t">
                                <button type="button" class="btn btn-default">Change Photo</button>
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a href="">Upload photo</a></li>
                                    <li><a href="">Take photo</a></li>
                                    <li class="divider"></li>
                                    <li><a href=""><span class="text-danger">Remove</span></a></li>
                                </ul>
                            </div>
                            <span class="help-block mb-0 mt-0">This photo is your identity on this site</span>
                        </div>
                    </div>

                    <hr class="mnr-md mnl-md"><!-- horizontal rule -->

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Name</label>
                        <div class="col-sm-6">
                            <input type="text" name="name" class="form-control" value="<?php echo $me->getName(); ?>">
                            <span class="help-block mb-0">Enter your real name, so people you know can recognize you.</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Location</label>
                        <div class="col-sm-6">
                            <input type="text" name="location" class="form-control" value="">
                            <span class="help-block mb-0">Where in the world are you?</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Website</label>
                        <div class="col-sm-6">
                            <input type="text" name="website" class="form-control" placeholder="http://www.site.com/">
                            <span class="help-block mb-0">Have a homepage or a blog? Put the address here.</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Bio</label>
                        <div class="col-sm-6">
                            <textarea class="form-control" rows="3" name="bio"></textarea>
                            <span class="help-block mb-0">About yourself in <strong>160</strong> characters or less.</span>
                        </div>
                    </div>

                    <hr class="mnr-md mnl-md"><!-- horizontal rule -->

                    <h4 class="fw-sb mb-lg">Facebook</h4>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"></label>
                        <div class="col-sm-6">
                            <span class="img-wrapper img-rounded" style="width:32px;"><img src="https://s3.amazonaws.com/uifaces/faces/twitter/adhamdannaway/128.jpg" alt=""></span>
                            <a href="" class="btn btn-default va-t">FACEBOOK LOGIN</a>
                            <span class="help-block mb-0">to manage your connection with Facebook.</span>
                        </div>
                    </div>

                    <hr class="mnr-md mnl-md"><!-- horizontal rule -->

                    <div class="form-group mb-0">
                        <label class="col-sm-2 control-label"></label>
                        <div class="col-sm-10">
                            <button type="submit" class="btn btn-primary">Save change</button>
                        </div>
                    </div>
                </form>
                <!--/ form -->
            </div>
            <!--/ wrapper -->
        </div>
        <!--/ right section -->
    </div>
    <!--/ end: box layout -->

<!--<div class="account-wrapper scrollable">
    <div class="row padding-md">
        <div class="col-sm-3 col-xs-12">
            <div class="body-white padding-md profile-details rounded-corners text-center">
                <img class="img img-responsive img-thumbnail"
                     src="<?php //echo $view['gravatar']->getImage($me->getEmail()); ?>" />
                <h3 class="margin-sm-sides margin-md-top"><?php //echo $me->getName(); ?></h3>
                <h4 class="margin-sm-sides"><?php //echo $me->getPosition(); ?></h4>
            </div>
        </div>

        <div class="col-sm-9 col-xs-12 padding-md-sides">
            <?php //echo $view['form']->start($userForm); ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <?php //echo $view['translator']->trans('mautic.user.account.header.details'); ?>
                    <div class="pull-right">
                        <button type="submit"
                                id ="btn-save-profile"
                                class="btn btn-success btn-xs"
                                data-toggle="tooltip"
                                data-container="body"
                                data-placement="top"
                                data-original-title="<?php //echo $view['translator']->trans('mautic.core.form.save'); ?>">
                            <i class="fa fa-check"></i>
                        </button>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="col-md-6">
                        <?php
                        //echo ($permissions['editUsername']) ? $view['form']->row($userForm['username']) : $view['form']->row($userForm['username_unbound']);
                        //echo ($permissions['editName']) ? $view['form']->row($userForm['firstName']) : $view['form']->row($userForm['firstName_unbound']);
                        //echo ($permissions['editName']) ? $view['form']->row($userForm['lastName']) : $view['form']->row($userForm['lastName_unbound']);
                        //echo ($permissions['editPosition']) ? $view['form']->row($userForm['position']) : $view['form']->row($userForm['position_unbound']);
                        //echo ($permissions['editEmail']) ? $view['form']->row($userForm['email']) : $view['form']->row($userForm['email_unbound']);
                        ?>
                    </div>
                    <div class="col-md-6">
                        <?php
                        //echo $view['form']->row($userForm['timezone']);
                        //echo $view['form']->row($userForm['locale']);
                        //echo $view['form']->row($userForm['currentPassword']);
                        //echo $view['form']->row($userForm['plainPassword']['password']);
                        //echo $view['form']->row($userForm['plainPassword']['confirm']);
                        ?>
                    </div>
                </div>
            </div>
            <?php //echo $view['form']->end($userForm); ?>

            <?php //if ($permissions['apiAccess']): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <?php //echo $view['translator']->trans('mautic.user.account.header.authorizedclients'); ?>
                </div>
                <div class="panel-body">
                    <?php //echo $view['actions']->render(
                        //new Symfony\Component\HttpKernel\Controller\ControllerReference(
                            //'MauticApiBundle:Client:authorizedClients'
                        //));
                        ?>
                </div>
            </div>
            <?php //endif; ?>
        </div>
    </div>
    '
</div>-->
