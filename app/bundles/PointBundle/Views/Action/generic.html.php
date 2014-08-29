<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$containerClass = (!empty($deleted)) ? ' bg-danger' : '';
?>

<div class="point-row <?php echo $containerClass; ?>" id="point_action_<?php echo $id; ?>">
    <?php
    if (!empty($inForm))
        echo $view->render('MauticPointBundle:Builder:actions.html.php', array(
            'deleted'  => (!empty($deleted)) ? $deleted : false,
            'id'       => $id,
            'route'   => 'mautic_pointaction_action'
        ));
    ?>
    <span class="action-label"><?php echo $action['name']; ?></span>
    <?php if (!empty($action['description'])): ?>
    <span class="action-descr"><?php echo $action['description']; ?></span>
    <?php endif; ?>
</div>