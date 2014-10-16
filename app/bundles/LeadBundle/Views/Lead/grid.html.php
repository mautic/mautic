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
            <div class="shuffle shuffle-item grid col-sm-6 col-lg-4">
                <div class="panel ovf-h">
                    <div class="box-layout">
                        <div class="col-xs-4 va-m">
                            <div class="panel-body">
                        <span class="img-wrapper img-rounded" style="width:100%">
                            <img class="img img-responsive"
                                 src="<?php echo $view['gravatar']->getImage($fields['core']['email']['value'], '250'); ?>" />
                        </span>
                            </div>
                        </div>
                        <div class="col-xs-8 va-m">
                            <div class="panel-body">
                                <?php
                                $color = $item->getColor();
                                $style = !empty($color) ? ' style="background-color: ' . $color . ' !important;"' : '';
                                ?>
                                <span class="pull-right label label-default mnt-1"<?php echo $style; ?>><?php echo $item->getPoints(); ?></span>
                                <h4 class="fw-sb mb-xs">
                                    <a href="<?php echo $view['router']->generate('mautic_lead_action',
                                        array("objectAction" => "view", "objectId" => $item->getId())); ?>"
                                       data-toggle="ajax">
                                        <span><?php echo $item->getPrimaryIdentifier(); ?></span>
                                    </a>
                                </h4>
                                <div class="text-muted mb-1">
                                    <i class="fa fa-fw fa-building mr-xs"></i><?php echo $fields['core']['company']['value']; ?>
                                </div>
                                <div class="text-muted mb-1">
                                    <i class="fa fa-fw fa-map-marker mr-xs"></i><?php
                                    if (!empty($fields['core']['city']['value']) && !empty($fields['core']['state']['value']))
                                        echo $fields['core']['city']['value'] . ', ' . $fields['core']['state']['value'];
                                    elseif (!empty($fields['core']['city']['value']))
                                        echo $fields['core']['city']['value'];
                                    elseif (!empty($fields['core']['state']['value']))
                                        echo $fields['core']['state']['value'];
                                    ?>
                                </div>
                                <div class="text-muted mb-1">
                                    <i class="fa fa-fw fa-globe mr-xs"></i><?php echo $fields['core']['country']['value']; ?>
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
</div>
<div class="row">
    <div class="col-sm-12">
        <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
            "totalItems"      => $totalItems,
            "page"            => $page,
            "limit"           => $limit,
            "menuLinkId"      => 'mautic_lead_index',
            "baseUrl"         => $view['router']->generate('mautic_lead_index'),
            "tmpl"            => $indexMode,
            'sessionVar'      => 'lead'
        )); ?>
    </div>
</div>
