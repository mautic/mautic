<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'tagmanager');
$view['slots']->set('headerTitle', $tag->getTag());
$customButtons = [];

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'item'            => $tag,
            'customButtons'   => (isset($customButtons)) ? $customButtons : [],
            'nameGetter'      => 'getTag',
            'templateButtons' => [
                'edit'   => $view['security']->isGranted('tagManager:tagManager:edit'),
                'delete' => $view['security']->isGranted('tagManager:tagManager:delete'),
                'close'  => $view['security']->isGranted('tagManager:tagManager:edit'),
            ],
            'routeBase' => 'tagmanager',
        ]
    )
);

?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-12 bg-white height-auto">
        <div class="bg-auto">
            <!-- page detail header -->
            <!-- sms detail collapseable toggler -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    
                    <div class="col-xs-10">
                        <div class="text-white dark-sm mb-0"><?php echo $tag->getDescription(); ?></div>
                    </div>

                </div>
            </div>
            <div class="collapse" id="sms-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                            <tr>
                                <td width="20%"><span class="fw-b textTitle"><?php echo $view['translator']->trans('mautic.core.id'); ?></span></td>
                                <td><?php echo $tag->getId(); ?></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!--/ sms detail collapseable toggler -->
        <div class="bg-auto bg-dark-xs">
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="Detail">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse"
                       data-target="#sms-details">
                        <span class="caret"></span> <?php echo $view['translator']->trans('mautic.core.details'); ?>
                    </a>
                </span>
            </div>
        </div>
    </div>

    <!--/ right section -->
    <input name="entityId" id="entityId" type="hidden" value="<?php echo $view->escape($tag->getId()); ?>" />
</div>
<!--/ end: box layout -->
