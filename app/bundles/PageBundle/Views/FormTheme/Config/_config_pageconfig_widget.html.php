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
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.config.tab.pageconfig'); ?></h3>
    </div>
    <div class="panel-body">
        <?php foreach ($form->children as $name => $f): ?>
            <?php if ('track_contact_by_ip' == $name) {
    continue;
} ?>
            <div class="row">
                <div class="col-md-6">
                    <?php echo $view['form']->row($f); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.config.tab.pagetracking'); ?></h3>
    </div>
    <div class="panel-body">
    <p><?php echo $view['translator']->trans('mautic.config.tab.pagetracking.info'); ?></p>
<pre>&lt;script&gt;
    (function(w,d,t,u,n,a,m){w['MauticTrackingObject']=n;
        w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)},a=d.createElement(t),
        m=d.getElementsByTagName(t)[0];a.async=1;a.src=u;m.parentNode.insertBefore(a,m)
    })(window,document,'script','<?php echo $view['router']->url('mautic_js'); ?>','mt');

    mt('send', 'pageview');
&lt;/script&gt;</pre>
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($form['track_contact_by_ip']); ?>
            </div>
        </div>
    </div>
</div>