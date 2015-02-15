<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
    $view->extend('MauticLeadBundle:Lead:index.html.php');
?>

<div class="pa-md bg-auto">
    <?php if (count($items)): ?>
    <div class="row shuffle-grid">
        <?php foreach ($items as $item): ?>
            <?php
                $fields = $item->getFields();
                $color  = $item->getColor();
                $style  = !empty($color) ? ' style="background-color: ' . $color . ' !important;"' : '';
            ?>
            <div class="shuffle shuffle-item grid col-sm-6 col-lg-4">
                <div class="panel card ovf-h" style="border-top: 3px solid <?php echo $color; ?>;">
                    <div class="box-layout">
                        <div class="col-xs-4 va-m">
                            <div class="panel-body">
                        <span class="img-wrapper img-rounded" style="width:100%">
                            <?php $preferred = $item->getPreferredProfileImage(); ?>
                            <?php if ($preferred == 'gravatar' || empty($preferred)) : ?>
                                <?php $img = $view['gravatar']->getImage($fields['core']['email']['value'], '250'); ?>
                            <?php else : ?>
                                <?php $socialData = $item->getSocialCache(); ?>
                                <?php $img = $socialData[$preferred]['profile']['profileImage']; ?>
                            <?php endif; ?>
                            <img class="img img-responsive"
                                 src="<?php echo $img; ?>" />
                        </span>
                            </div>
                        </div>
                        <div class="col-xs-8 va-t">
                            <div class="panel-body">
                                <?php if (empty($hideCheckbox)): ?>
                                <div class="pull-right">
                                    <div class="checkbox-inline custom-primary mnr-10">
                                        <label class="mb-0">
                                            <input type="checkbox" value="1">
                                            <span></span>
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (in_array($item->getId(), $noContactList)) : ?>
                                <div class="pull-right label label-danger"><i class="fa fa-ban"> </i></div>
                                <?php endif; ?>
                                <h4 class="fw-sb mb-xs">
                                    <a href="<?php echo $view['router']->generate('mautic_lead_action',
                                        array("objectAction" => "view", "objectId" => $item->getId())); ?>"
                                       data-toggle="ajax">
                                        <span><?php echo $item->getPrimaryIdentifier(); ?></span>
                                    </a>
                                </h4>
                                <div class="text-muted mb-1 ellipsis">
                                    <i class="fa fa-fw fa-building mr-xs"></i><?php echo $fields['core']['company']['value']; ?>
                                </div>
                                <div class="text-muted mb-1 ellipsis">
                                    <i class="fa fa-fw fa-map-marker mr-xs"></i><?php
                                    $location = array();
                                    if (!empty($fields['core']['city']['value'])):
                                        $location[] = $fields['core']['city']['value'];
                                    endif;
                                    if (!empty($fields['core']['state']['value'])):
                                        $location[] = $fields['core']['state']['value'];
                                    elseif (!empty($fields['core']['country']['value'])):
                                        $location[] = $fields['core']['country']['value'];
                                    endif;
                                    echo implode(', ', $location);
                                    ?>
                                </div>
                                <div class="text-muted mb-1 ellipsis">
                                    <i class="fa fa-fw fa-globe mr-xs"></i><?php echo $fields['core']['country']['value']; ?>
                                </div>
                                <?php $flag = (!empty($fields['core']['country'])) ? $view['assets']->getCountryFlag($fields['core']['country']['value']) : ''; ?>

                                <?php if (!empty($flag)): ?>
                                    <div style="position: absolute; right: 30px; bottom: 30px">
                                        <img src="<?php echo $flag; ?>" style="max-height: 24px;" class="ml-sm" />
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
    <?php endif; ?>
</div>
<?php if (count($items)): ?>
    <div class="panel-footer">
        <?php
        $link = (isset($link))? $link : 'mautic_lead_index';
        echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
            "totalItems"      => $totalItems,
            "page"            => $page,
            "limit"           => $limit,
            "menuLinkId"      => $link,
            "baseUrl"         => (isset($objectId)) ? $view['router']->generate($link, array('objectId' => $objectId)) : $view['router']->generate($link),
            "tmpl"            => (!in_array($tmpl, array('grid', 'index'))) ? $tmpl : $indexMode,
            'sessionVar'      => (isset($sessionVar)) ? $sessionVar : 'lead',
            'target'          => (isset($target)) ? $target : 'page-list'
        ));
        ?>
    </div>
<?php endif; ?>
