<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel-body">
    <?php echo $view->render('MauticLeadBundle:Social/Instagram:photos.html.php', array(
        'activity'   => $details['activity']['photos']
    )); ?>
</div>