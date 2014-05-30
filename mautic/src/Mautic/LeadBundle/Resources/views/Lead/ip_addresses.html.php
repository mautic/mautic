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
<div class="panel-heading"><?php echo $view['translator']->trans('mautic.lead.lead.header.ipaddress'); ?></div>
<div class="panel-body">
    <div class="table-responsive">
        <table class="table table-striped table-condensed">
            <tbody>
            <?php foreach ($lead->getIpAddresses() as $ip): ?>
                <?php $details = $ip->getIpDetails(); ?>
                <tr>
                    <td><strong><?php echo $ip->getIpAddress(); ?></strong></td>
                    <td><?php echo $details->city; ?></td>
                    <td><?php echo $details->region_name; ?></td>
                    <td><?php echo $details->country_name; ?></td>
                    <td>
                        <?php if ($details->latitude): ?>
                            <a href="http://maps.google.com/maps?q=<?php echo $details->latitude; ?>+<?php echo $details->longitude; ?>"
                               target="_blank">
                                <i class="fa fa-map-marker"></i>
                            </a>
                        <?php endif; ?>
                        <?php
                        $info = "";
                        foreach ($details as $k => $d):
                            $info .= "$k: $d<br />";
                        endforeach;
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
</div>