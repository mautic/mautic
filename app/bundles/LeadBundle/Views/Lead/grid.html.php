<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
    $view->extend('MauticLeadBundle:Lead:index.html.php');
?>

<div class="shuffle grid row scrollable page-list" id="shuffle-grid">
    <?php if (count($items)): ?>
    <?php foreach ($items as $item): ?>
    <?php $fields = $item->getFields(); ?>
    <div class="shuffle shuffle-item grid margin-md-bottom col-sm-6 col-md-4">
        <div class="panel widget">
            <div class="table-layout nm">
                <div class="col-xs-4 text-center">
                    <img class="img img-responsive"
                         src="<?php echo $view['gravatar']->getImage($fields['core']['email']['value'], '250'); ?>" />
                </div>
                <div class="col-xs-8 valign-middle">
                    <div class="panel-body">
                        <h5>
                            <a href="<?php echo $view['router']->generate('mautic_lead_action',
                                array("objectAction" => "view", "objectId" => $item->getId())); ?>"
                               data-toggle="ajax">
                                <span><?php echo $item->getPrimaryIdentifier(); ?></span>
                            </a>
                            <span class="badge"><?php echo $item->getScore(); ?></span>
                        </h5>
                        <div class="text-muted">
                            <i class="fa fa-fw fa-building"></i><span class="padding-sm-left"><?php echo $fields['core']['company']['value']; ?></span>
                        </div>
                        <div class="text-muted">
                            <i class="fa fa-fw fa-envelope"></i><span class="padding-sm-left"><?php echo $fields['core']['email']['value']; ?></span>
                        </div>
                        <div class="text-muted">
                            <i class="fa fa-fw fa-map-marker"></i><span class="padding-sm-left"><?php
                            if (!empty($fields['core']['city']['value']) && !empty($fields['core']['state']['value']))
                                echo $fields['core']['city']['value'] . ', ' . $fields['core']['state']['value'];
                            elseif (!empty($fields['core']['city']['value']))
                                echo $fields['core']['city']['value'];
                            elseif (!empty($fields['core']['state']['value']))
                                echo $fields['core']['state']['value'];
                            ?></span>
                        </div>
                        <div class="text-muted">
                            <i class="fa fa-fw fa-globe"></i><span class="padding-sm-left"><?php echo $fields['core']['country']['value']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
        <div class="col-xs-12">
            <h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
        </div>
    <?php endif; ?>

    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "totalItems"      => $totalItems,
        "page"            => $page,
        "limit"           => $limit,
        "menuLinkId"      => 'mautic_lead_index',
        "baseUrl"         => $view['router']->generate('mautic_lead_index'),
        "tmpl"            => $indexMode,
        'sessionVar'      => 'lead'
    )); ?>
    <div class="footer-margin"></div>
</div>
