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
    <div class="col-sm-10">
        <h1 class="mt0"><?php echo $lead->getName(); ?></h1>
        <h4 class="mt0">
            <?php if(isset($fields['position'])): ?>
                <?php echo $fields['position']; ?>
            <?php endif; ?>
            at 
            <a href="#">Company Name</a></h4>
    </div>
    <div class="col-sm-2">
        <div class="alert alert-success text-center pa15">
            <h2 class="nm pa5">31</h2>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.lead.lead.header.leadinfo'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="col-sm-12">
            <?php if ($lead->getOwner()): ?>
                <div class="row">
                    <div class="col-xs-3 field-label">
                        <?php echo $view['translator']->trans('mautic.lead.lead.field.owner'); ?>
                    </div>
                    <div class="col-xs-9 field-value">
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
            <?php foreach ($fields['core'] as $field): ?>
                <?php if (empty($field['value'])) continue; ?>
                <div class="row">
                    <div class="col-xs-3 field-label">
                        <?php echo $field['label']; ?>
                    </div>
                    <div class="col-xs-9 field-value">
                        <?php echo $view->render('MauticLeadBundle:Lead:info_value.html.php', array(
                            'value'             => $field['value'],
                            'name'              => $field['alias'],
                            'type'              => $field['type'],
                            'dateFormats'       => $dateFormats,
                            'socialProfileUrls' => $socialProfileUrls
                        )); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        </div>
    </div>
