<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php
if (count($items)):
foreach ($items as $key => $item):
    $activeClass = ($tmpl == 'index' && !empty($activeForm) && $item->getId() === $activeForm->getId()) ? " active" : "";
    ?>
    <div class="bundle-list-item<?php echo $activeClass; ?>" id="form-<?php echo $item->getId(); ?>">
        <div class="padding-sm">
            <span class="list-item-publish-status">
                <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                    'item'       => $item,
                    'dateFormat' => (!empty($dateFormat)) ? $dateFormat : 'F j, Y g:i a',
                    'model'      => 'form.form'
                )); ?>

            </span>
                <a href="<?php echo $view['router']->generate('mautic_form_action',
                    array(
                        'objectAction' => 'view',
                        'objectId' => $item->getId(),
                        'tmpl' => 'form',

                    )); ?>"
                   onclick="Mautic.activateListItem('form', <?php echo $item->getId(); ?>);"
                   data-toggle="ajax"
                   data-menu-link="mautic_form_index">

                <span class="list-item-primary">
                    <?php echo $item->getName(); ?>
                </span>
                <span class="list-item-secondary list-item-indent" data-toggle="tooltip" data-placement="right"
                      title="<?php echo $item->getDescription(); ?>">
                    <?php echo $item->getDescription(true); ?>
                </span>
            </a>
            <div class="badge-count padding-sm">
                <a href="<?php echo $view['router']->generate('mautic_form_action',
                    array('objectAction' => 'results', 'objectId' => $item->getId())); ?>"
                   data-toggle="ajax"
                   data-menu-link="mautic_form_index">
                    <span class="badge" data-toggle="tooltip"
                          title="<?php echo $view['translator']->trans('mautic.form.form.resultcount'); ?>">
                        <?php echo $item->getResultCount(); ?>
                    </span>
                </a>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
<?php endforeach; ?>
<?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
    "items"           => $items,
    "page"            => $page,
    "limit"           => $limit,
    "totalItems"      => $totalCount,
    "menuLinkId"      => 'mautic_form_index',
    "baseUrl"         => $view['router']->generate('mautic_form_index'),
    "queryString"     => 'tmpl=list',
    "paginationClass" => "sm",
    'sessionVar'      => 'form',
    'tmpl'            => 'list'
)); ?>
<?php else: ?>
<h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
<?php endif; ?>

<div class="footer-margin"></div>