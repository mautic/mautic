<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="pa-md bg-auto">
    <?php if (count($items)): ?>
    <div class="shuffle-grid row">
        <?php foreach ($items as $item): ?>
            <div class="shuffle shuffle-item card grid col-sm-6 col-lg-4">
                <div class="panel ovf-h" style="border-top: 3px solid <?php echo $item['color']; ?>;">
                    <div class="box-layout">
                        <div class="col-xs-4 va-m">
                            <div class="panel-body">
                                <span class="img-wrapper img-rounded" style="width:100%">
                                    <?php $preferred = $item['preferred_profile_image']; ?>
                                    <?php if ($preferred == 'gravatar' || empty($preferred)) : ?>
                                        <?php $img = $view['gravatar']->getImage($item['email'], '250'); ?>
                                    <?php else : ?>
                                        <?php $socialData = unserialize($item['social_cache']); ?>
                                        <?php $img        = (!empty($socialData[$preferred]['profile']['profileImage'])) ? $socialData[$preferred]['profile']['profileImage'] : $view['gravatar']->getImage($item['email'], '250'); ?>
                                    <?php endif; ?>
                                    <img class="img img-responsive" src="<?php echo $img; ?>" />
                                </span>
                            </div>
                        </div>
                        <div class="col-xs-8 va-t">
                            <div class="panel-body">
                                <?php if (in_array($item['id'], $noContactList)) : ?>
                                    <div class="pull-right label label-danger"><i class="fa fa-ban"> </i></div>
                                <?php endif; ?>
                                <h4 class="fw-sb mb-xs">
                                    <a href="<?php echo $view['router']->path('mautic_contact_action', ['objectAction' => 'view', 'objectId' => $item['id']]); ?>" data-toggle="ajax">
                                        <span>
                                            <?php
                                            if (!empty($item['firstname']) && !empty($item['lastname'])):
                                                echo $item['lastname'].', '.$item['firstname'];
                                            elseif (!empty($item['lastname'])):
                                                echo $item['lastname'];
                                            elseif (!empty($item['firstname'])):
                                                echo $item['firstname'];
                                            elseif (!empty($item['email'])):
                                                echo $item['email'];
                                            else:
                                                echo $view['translator']->trans('mautic.lead.lead.anonymous');
                                            endif;
                                            ?>
                                        </span>
                                    </a>
                                </h4>
                                <div class="text-muted mb-1">
                                    <i class="fa fa-fw fa-building mr-xs"></i><?php echo $item['company']; ?>
                                </div>
                                <div class="text-muted mb-1">
                                    <i class="fa fa-fw fa-map-marker mr-xs"></i><?php
                                    $available = ['city', 'state'];
                                    $location  = [];
                                    foreach ($available as $a):
                                        if (!empty($item[$a])):
                                            $location[] = $a;
                                        endif;
                                    endforeach;
                                    echo implode(', ', $location);
                                    ?>
                                </div>
                                <div class="text-muted mb-1">
                                    <i class="fa fa-fw fa-globe mr-xs"></i><?php echo $item['country']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
        <div class="clearfix"></div>
    <?php endif; ?>
</div>
<?php if (count($items)): ?>
<div class="panel-footer">
    <?php
    $link = (isset($link)) ? $link : 'mautic_contact_index';
    echo $view->render('MauticCoreBundle:Helper:pagination.html.php', [
        'totalItems' => $totalItems,
        'page'       => $page,
        'limit'      => $limit,
        'menuLinkId' => $link,
        'baseUrl'    => (isset($objectId)) ? $view['router']->path($link, ['objectId' => $objectId]) : $view['router']->path($link),
        'tmpl'       => (!in_array($tmpl, ['grid', 'index'])) ? $tmpl : $indexMode,
        'sessionVar' => (isset($sessionVar)) ? $sessionVar : 'lead',
        'target'     => (isset($target)) ? $target : '.page-list',
    ]);
    ?>
</div>
<?php endif; ?>
