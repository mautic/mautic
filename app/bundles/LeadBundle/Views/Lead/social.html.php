<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//@todo generate points log view
?>

    <?php $count = 0; ?>
    <div class="row">
    <?php foreach ($socialProfiles as $network => $details): ?>
        <?php if ($count > 0 && $count%3 == 0): echo '</div><div class="row">'; endif; ?>
        <div class="col-md-4">
            <div class="panel panel-default panel-<?php echo strtolower($network); ?>">
                <div class="panel-heading pr-0">
                    <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.social.'.$network); ?></h3>
                    <div class="panel-toolbar text-right">
                            <a href="#" class="btn" data-toggle="tooltip"
                                    onclick="Mautic.refreshLeadSocialProfile('<?php echo $network; ?>', '<?php echo $lead->getId(); ?>', event);" title="<?php echo $view['translator']->trans('mautic.lead.lead.social.lastupdate', array(
                "%datetime%" => $view['date']->toFullConcat($details['lastRefresh'], 'utc')
            )); ?>
">
                                <i class="text-white fa fa-refresh"></i>
                            </a>
                            <!-- <a href="#" class="btn" data-toggle="panelcollapse"><i class="text-white fa fa-angle-up"></i></a> -->
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