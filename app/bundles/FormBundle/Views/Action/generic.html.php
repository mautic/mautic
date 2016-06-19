<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$containerClass = (!empty($deleted)) ? ' bg-danger' : '';
?>

<div class="mauticform-row <?php echo $containerClass; ?>" id="mauticform_action_<?php echo $id; ?>">
    <?php
    if (!empty($inForm))
        echo $view->render('MauticFormBundle:Builder:actions.html.php', array(
            'deleted'    => (!empty($deleted)) ? $deleted : false,
            'id'         => $id,
            'route'      => 'mautic_formaction_action',
            'actionType' => 'action',
            'formId'     => $formId
        ));
    ?>
    <a data-toggle="ajaxmodal" data-target="#formComponentModal" href="<?php echo $view['router']->path('mautic_formaction_action', array('objectAction' => 'edit', 'objectId' => $id, 'formId' => $formId)); ?>"><span class="action-label"><?php echo $action['name']; ?></span></a>
    <?php if (!empty($action['description'])): ?>
    <span class="action-descr"><?php echo $action['description']; ?></span>
    <?php endif; ?>
</div>