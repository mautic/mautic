<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="row">
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.form.form.header.copy'); ?></h3>
            </div>
            <div class="panel-body">
                <h2><?php echo $view['translator']->trans('mautic.form.form.header.landingpages'); ?></h2>
                <p><?php echo $view['translator']->trans('mautic.form.form.help.landingpages'); ?></p>
                <br />

                <h2><?php echo $view['translator']->trans('mautic.form.form.header.automaticcopy'); ?></h2>
                <p><?php echo $view['translator']->trans('mautic.form.form.help.automaticcopy'); ?></p>
                <textarea class="form-html form-control" readonly onclick="this.setSelectionRange(0, this.value.length);">&lt;script type="text/javascript" src="<?php echo $view['router']->generate('mautic_form_generateform', array('id' => $form->getId()), true); ?>"&gt;&lt;/script&gt;</textarea>
                <br />
                <h2><?php echo $view['translator']->trans('mautic.form.form.header.manualcopy'); ?></h2>
                <p><?php echo $view['translator']->trans('mautic.form.form.help.manualcopy'); ?></p>
                <textarea class="form-html form-control" readonly onclick="this.setSelectionRange(0, this.value.length);">
                    <?php echo htmlentities($form->getCachedHtml()); ?>
                </textarea>
            </div>
        </div>
        <?php
        echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
            'id'     => 'form-preview',
            'header' => $view['translator']->trans('mautic.form.form.header.preview'),
            'body'   => $view->render('MauticFormBundle:Form:preview.html.php', array('form' => $form)),
            'size'   => 'lg'
        ));
        ?>
    </div>
    <div class="col-md-3">
                        <div class="panel panel-minimal">
                            <div class="panel-heading"><h5 class="panel-title"><i class="ico-health mr5"></i>Latest Activity</h5></div>
                        
                            <!-- Media list feed -->
                            <ul class="media-list media-list-feed nm">
                                <li class="media">
                                    <div class="media-object pull-left">
                                        <i class="ico-pencil bgcolor-success"></i>
                                    </div>
                                    <div class="media-body">
                                        <p class="media-heading">EDIT EXISTING PAGE</p>
                                        <p class="media-text"><span class="text-primary semibold">Service Page</span> has been edited by Tamara Moon.</p>
                                        <p class="media-meta">Just Now</p>
                                    </div>
                                </li>
                                <li class="media">
                                    <div class="media-object pull-left">
                                        <i class="ico-file-plus bgcolor-success"></i>
                                    </div>
                                    <div class="media-body">
                                        <p class="media-heading">CREATE A NEW PAGE</p>
                                        <p class="media-text"><span class="text-primary semibold">Service Page</span> has been created by Tamara Moon.</p>
                                        <p class="media-meta">2 Hour Ago</p>
                                    </div>
                                </li>
                                <li class="media">
                                    <div class="media-object pull-left">
                                        <i class="ico-upload22 bgcolor-success"></i>
                                    </div>
                                    <div class="media-body">
                                        <p class="media-heading">UPLOAD CONTENT</p>
                                        <p class="media-text">Tamara Moon has uploaded 8 new item to the directory</p>
                                        <p class="media-meta">3 Hour Ago</p>
                                    </div>
                                </li>
                                <li class="media">
                                    <div class="media-object pull-left">
                                        <img src="../image/avatar/avatar6.jpg" class="media-object img-circle" alt="">
                                    </div>
                                    <div class="media-body">
                                        <p class="media-heading">NEW MESSAGE</p>
                                        <p class="media-text">Arthur Abbott send you a message</p>
                                        <p class="media-meta">3 Hour Ago</p>
                                    </div>
                                </li>
                                <li class="media">
                                    <div class="media-object pull-left">
                                        <i class="ico-upload22 bgcolor-success"></i>
                                    </div>
                                    <div class="media-body">
                                        <p class="media-heading">UPLOAD CONTENT</p>
                                        <p class="media-text">Tamara Moon has uploaded 3 new item to the directory</p>
                                        <p class="media-meta">7 Hour Ago</p>
                                    </div>
                                </li>
                                <li class="media">
                                    <div class="media-object pull-left">
                                        <i class="ico-link5 bgcolor-success"></i>
                                    </div>
                                    <div class="media-body">
                                        <p class="media-heading">NEW UPDATE AVAILABLE</p>
                                        <p class="media-text">3 new update is available to download</p>
                                        <p class="media-meta">Yesterday</p>
                                    </div>
                                </li>
                                <li class="media">
                                    <div class="media-object pull-left">
                                        <i class="ico-loop4"></i>
                                    </div>
                                    <div class="media-body">
                                        <a href="javascript:void(0);" class="media-heading text-primary">Load more feed</a>
                                    </div>
                                </li>
                            </ul>
                            <!--/ Media list feed -->
                        </div>
                    </div>
</div>