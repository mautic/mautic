<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.lead.lead.header.ipaddress'); ?></h3>
    </div>
    <div class="table-responsive panel-collapse pull out">
            <table class="table table-hover table-bordered table-striped table-condensed">
                <thead>
                    <tr>
                        <th class="col-leadip-ip"><?php echo $view['translator']->trans('mautic.lead.lead.thead.ip'); ?></th>
                        <th class="col-leadip-city"><?php echo $view['translator']->trans('mautic.lead.lead.thead.city'); ?></th>
                        <th class="col-leadip-state"><?php echo $view['translator']->trans('mautic.lead.lead.thead.state'); ?></th>
                        <th class="col-leadip-country"><?php echo $view['translator']->trans('mautic.lead.lead.thead.country'); ?></th>
                        <th class="col-leadip-country"><?php echo $view['translator']->trans('mautic.lead.lead.thead.timezone'); ?></th>
                        <th class="col-leadip-icons"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lead->getIpAddresses() as $ip): ?>
                    <?php $details = $ip->getIpDetails(); ?>
                    <tr>
                        <td><strong><?php echo $ip->getIpAddress(); ?></strong></td>
                        <td><?php echo $details['city']; ?></td>
                        <td><?php echo $details['region']; ?></td>
                        <td><?php echo $details['country']; ?></td>
                        <td><?php echo $details['timezone']; ?></td>
                        <td>
                            <?php if ($details['latitude']): ?>
                                <a href="http://maps.google.com/maps?q=<?php echo $details['latitude']; ?>+<?php echo $details['longitude']; ?>"
                                   target="_blank">
                                    <i class="fa fa-map-marker"></i>
                                </a>
                            <?php endif; ?>
                            <?php
                            $info = "";
                            if (!empty($details)):
                                foreach ($details as $k => $d):
                                    $info .= "$k: $d<br />";
                                endforeach;
                            endif;
                            ?>
                            <i class="fa fa-info-circle"
                               data-toggle="tooltip"
                               data-container="body"
                               data-placement="auto left"
                               data-original-title="<?php echo $info; ?>"></i>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
</div>