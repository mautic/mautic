<?php

/*
 * @copyright   2010 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php foreach ($dncList as $channnel => $reason):
    $dncTitle = $view['translator']->trans('mautic.lead.event.donotcontact_channel', ['%channel%' => $channnel]);
    ?>
    <span class="label label-danger">
    <i title="<?php echo $dncTitle; ?>" class="fa fa-ban ico-<?php echo $channnel; ?>"> </i>
   </span>
<?php endforeach; ?>
