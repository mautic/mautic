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
    <a href="<?php echo $view['router']->generate('mautic_form_action',
        array(
            'objectAction' => 'view',
            'objectId' => $item->getId(),
            'tmpl' => 'form',

        )); ?>"
       onclick="Mautic.activateForm(<?php echo $item->getId(); ?>);"
       data-toggle="ajax"
       data-menu-link="mautic_form_index">
        <div class="form-profile<?php echo $activeClass; ?>" id="form-<?php echo $item->getId(); ?>">
            <div class="padding-sm">
                <div class="pull-left">
                    <span class="form-name">
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                            'item'       => $item,
                            'dateFormat' => (!empty($dateFormat)) ? $dateFormat : 'F j, Y g:i a',
                            'model'      => 'form.form'
                        )); ?>
                        <?php echo $item->getName(); ?>
                    </span>
                    <span class="form-description"><?php echo $item->getDescription(true); ?></span>
                </div>
                <div class="pull-right padding-sm">
                    <span class="badge"><?php echo $item->getResultCount(); ?></span>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    </a>

    <div class="clearfix"></div>
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
    'tmpl'            => 'list',
    'target'          => '.forms'
)); ?>
<?php else: ?>
<h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
<?php endif; ?>

<div class="form-footer"></div>