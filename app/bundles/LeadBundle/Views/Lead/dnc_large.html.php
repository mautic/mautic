<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div id="bounceLabel<?php echo $doNotContact->getId(); ?>">
    <div class="panel-heading text-center">
        <h4 class="fw-sb">
            <?php if (\Mautic\LeadBundle\Entity\DoNotContact::UNSUBSCRIBED == $doNotContact->getReason()): ?>
                <span class="label label-danger" data-toggle="tooltip" title="<?php echo $view['lead_dnc_reason']->toText($doNotContact->getReason()); ?>">
                                <?php echo $view['translator']->trans('mautic.lead.do.not.contact_channel', ['%channel%'=> strtoupper($doNotContact->getChannel())]); ?>
                            </span>

            <?php elseif (\Mautic\LeadBundle\Entity\DoNotContact::MANUAL == $doNotContact->getReason()): ?>
                <span class="label label-danger" data-toggle="tooltip" title="<?php echo $view['lead_dnc_reason']->toText($doNotContact->getReason()); ?>">
                                <?php echo $view['translator']->trans('mautic.lead.do.not.contact_channel', ['%channel%'=> strtoupper($doNotContact->getChannel())]); ?>
                                    <span data-toggle="tooltip" data-placement="bottom" title="<?php echo $view['translator']->trans('mautic.lead.remove_dnc_status'); ?>">
                                    <i class="fa fa-times has-click-event" onclick="Mautic.removeBounceStatus(this, <?php echo $doNotContact->getId(); ?>);"></i>
                                </span>
                            </span>

            <?php elseif (\Mautic\LeadBundle\Entity\DoNotContact::BOUNCED == $doNotContact->getReason()): ?>
                <span class="label label-warning" data-toggle="tooltip" title="<?php echo $view->escape($doNotContact->getComments()); ?>">
                                <?php echo $view['translator']->trans('mautic.lead.do.not.contact_bounced_channel', ['%channel%'=> strtoupper($doNotContact->getChannel())]); ?>
                                    <span data-toggle="tooltip" data-placement="bottom" title="<?php echo $view['translator']->trans('mautic.lead.remove_dnc_status'); ?>">
                                    <i class="fa fa-times has-click-event" onclick="Mautic.removeBounceStatus(this, <?php echo $doNotContact->getId(); ?>);"></i>
                                </span>
                            </span>
            <?php endif; ?>
        </h4>
        <hr>
    </div>
</div>