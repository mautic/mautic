<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticCrmBundle:Integration:index.html.php');
}
?>

<ul class="notes" id="deals">
    <?php foreach ($deals as $deal): ?>
        <?php echo $view->render('MauticCrmBundle:Integration:deal.html.php', [
            'deal'        => $deal,
            'lead'        => $lead,
        ]); ?>
    <?php endforeach; ?>
</ul>
