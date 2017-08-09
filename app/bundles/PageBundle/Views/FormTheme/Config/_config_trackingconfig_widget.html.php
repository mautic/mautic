<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.config.tab.pagetracking'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="form-group">
            <p><?php echo $view['translator']->trans('mautic.config.tab.pagetracking.info'); ?></p>
            <pre>&lt;script&gt;
    (function(w,d,t,u,n,a,m){w['MauticTrackingObject']=n;
        w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)},a=d.createElement(t),
        m=d.getElementsByTagName(t)[0];a.async=1;a.src=u;m.parentNode.insertBefore(a,m)
    })(window,document,'script','<?php echo $view['router']->url('mautic_js'); ?>','mt');

    mt('send', 'pageview');
&lt;/script&gt;</pre>
        </div>
    </div>
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.config.tab.tracking.pixels'); ?></h3>

    </div>
    <div class="panel-body">
        <?php echo $view['form']->row($form['pixel_in_campaign_enabled']); ?>

        <hr>
        <h4><?php echo $view['translator']->trans('mautic.config.tab.facebook.pixel'); ?></h4>
        <br>
        <?php echo $view['form']->row($form['facebook_pixel_id']); ?>
        <?php echo $view['form']->row($form['facebook_pixel_landingpage_enabled']); ?>
        <?php echo $view['form']->row($form['facebook_pixel_trackingpage_enabled']); ?>
        <hr>
        <h4><?php echo $view['translator']->trans('mautic.config.tab.google.analytics'); ?></h4>
        <br>
        <?php echo $view['form']->row($form['google_analytics_id']); ?>
        <?php echo $view['form']->row($form['google_analytics_landingpage_enabled']); ?>
        <?php echo $view['form']->row($form['google_analytics_trackingpage_enabled']); ?>
        <hr>
        <h4><?php echo $view['translator']->trans('mautic.config.tab.google.adwords'); ?></h4>
        <br>
        <?php echo $view['form']->row($form['google_adwords_id']); ?>
        <?php echo $view['form']->row($form['google_adwords_landingpage_enabled']); ?>
        <?php echo $view['form']->row($form['google_adwords_trackingpage_enabled']); ?>


    </div>
</div>