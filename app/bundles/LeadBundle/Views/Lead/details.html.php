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
            <?php if(isset($fields['core']['position']['value'])): ?>
                <?php  echo $fields['core']['position']['value']; ?>
            <?php endif; ?>
            at
            <?php if(isset($fields['core']['company']['value'])): 
                echo $fields['core']['company']['value'];
            endif; ?></h4>
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
        <div class="panel-toolbar">
            <?php $groups = array_keys($fields); ?>
            <ul class="nav nav-tabs" role="tablist">
                <?php foreach ($groups as $k => $group): ?>
                <li<?php echo ($k === 0) ? ' class="active"' : ''; ?>>
                    <a href="#<?php echo $group; ?>" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.lead.field.group.'.$group); ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="panel-body">
        <div class="tab-content">
            <?php $count = 0; ?>
            <?php foreach ($groups as $key => $group): ?>
            <div class="tab-pane<?php echo ($count === 0) ? ' active': ''; ?>" id="<?php echo $group; ?>">
                <?php /** if ($lead->getOwner()): ?>
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
                <?php endif; **/ ?>
                <div class="col-sm-6">
                <?php $total = count($fields[$group]); 
                    $current = 1;
                ?>
                <?php foreach ($fields[$group] as $field): ?>
                    <?php if($current == $total/2): ?>
                        </div>
                        <div class="col-sm-6">
                    <?php endif; ?>
                    <?php if (empty($field['value'])) continue; ?>
                        <strong>
                            <?php echo $field['label']; ?>
                        </strong><br />
                        <div>
                            <?php echo $view->render('MauticLeadBundle:Lead:info_value.html.php', array(
                                'value'             => $field['value'],
                                'name'              => $field['alias'],
                                'type'              => $field['type'],
                                'dateFormats'       => $dateFormats,
                                'socialProfileUrls' => $socialProfileUrls
                            )); ?>
                        </div>
                    <?php $current++; ?>
                <?php endforeach; ?>
                </div>
            </div>
            <?php $count++; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
