<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel panel-success">
    <div class="panel-heading"><?php echo $view['translator']->trans('mautic.lead.lead.header.leadinfo'); ?></div>
    <div class="panel-body">
        <?php if ($lead->getOwner()): ?>
            <div class="row">
                <div class="col-sm-3 field-label">
                    <?php echo $view['translator']->trans('mautic.lead.lead.field.owner'); ?>
                </div>
                <div class="col-sm-9 field-value">
                    <a href="<?php echo $view['router']->generate('mautic_user_action', array(
                        'objectAction' => 'contact',
                        'objectId'     => $lead->getOwner()->getId(),
                        'entity'       => 'lead',
                        'id'           => $lead->getId(),
                        'returnUrl'    => $view['router']->generate('mautic_lead_action', array(
                            'objectAction' => 'view',
                            'objectId'     => $lead->getId()
                        ))
                        )); ?>">
                    <?php echo $lead->getOwner()->getName(); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
        <?php foreach ($lead->getFields() as $field): ?>
        <?php if (!$field->getValue()) continue; ?>
            <div class="row">
                <div class="col-sm-3 field-label">
                    <?php echo $field->getField()->getLabel(); ?>
                </div>
                <div class="col-sm-9 field-value">
                    <?php echo $view->render('MauticLeadBundle:Lead:info_value.html.php', array('field' => $field)); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>