<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>

    <?php $count = 0; ?>
    <div class="row">
    <?php foreach ($socialProfiles as $network => $details): ?>
        <?php if ($count > 0 && $count%2 == 0): echo '</div><div class="row">'; endif; ?>
        <div class="col-md-6">
            <div class="panel panel-default panel-<?php echo strtolower($network); ?>">
                <div class="panel-heading pr-0">
                    <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.social.'.$network); ?></h3>
                    <div class="panel-toolbar text-right">
                        <a href="javascript:void(0);" class="btn" data-toggle="tooltip" onclick="Mautic.refreshLeadSocialProfile('<?php echo $network; ?>', '<?php echo $lead->getId(); ?>', event);" title="<?php echo $view['translator']->trans('mautic.lead.lead.social.lastupdate', array("%datetime%" => $view['date']->toFullConcat($details['lastRefresh'], 'utc'))); ?>">
                            <i class="text-white fa fa-refresh"></i>
                        </a>
                        <!--<a href="javascript:void(0);" class="btn" data-toggle="panelcollapse"><i class="text-white fa fa-angle-up"></i></a>-->
                        <a href="javascript:void(0);" class="btn" onclick="Mautic.clearLeadSocialProfile('<?php echo $network; ?>', '<?php echo $lead->getId(); ?>', event);" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.lead.lead.social.removecache'); ?>">
                            <i class="text-white fa fa-times"></i>
                        </a>
                        <!-- trickery to allow tooltip and onclick for close button -->
                        <a class="hide <?php echo $network . '-panelremove'; ?>" data-toggle="panelremove" data-parent=".col-md-6">&amp;</a>
                    </div>
                </div>
                 <div class="panel-collapse pull out" id="<?php echo "{$network}CompleteProfile"; ?>">
                    <?php echo $view->render('MauticLeadBundle:Social/' . $network . ':view.html.php', array(
                    'lead'              => $lead,
                    'details'           => $details,
                    'network'           => $network,
                    'socialProfileUrls' => $socialProfileUrls
                )); ?>
                </div>
            </div>
        </div>
    <?php $count++; ?>
    <?php endforeach; ?>
</div>
<?php
$view['slots']->append('modal', $view->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id'      => 'socialImageModal',
    'body'    => '<img class="img-responsive img-thumbnail" />',
    'header'  => false,
    'paddingg' => 'pa-0'
)));