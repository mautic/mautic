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

<div class="panel-body">
    <?php echo $view->render('MauticSocialBundle:Integration/Facebook/Profile:profile.html.php', [
        'lead'    => $lead,
        'profile' => $details['profile'],
    ]); ?>
</div>