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
$view['slots']->set('mauticContent', 'mauticWebhook');

/* @var \Mautic\WebhookBundle\Entity\Webhook $item */
$view['slots']->set('headerTitle', $item->getName());

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', [
    'item'            => $item,
    'templateButtons' => [
        'edit'   => $view['security']->hasEntityAccess($permissions['webhook:webhooks:editown'], $permissions['webhook:webhooks:editother'], $item->getCreatedBy()),
        'clone'  => $permissions['webhook:webhooks:create'],
        'delete' => $view['security']->hasEntityAccess($permissions['webhook:webhooks:deleteown'], $permissions['webhook:webhooks:deleteown'], $item->getCreatedBy()),
    ],
    'routeBase' => 'webhook',
]));
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- page detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-10">
                        <div class="text-muted"><?php echo $item->getDescription(); ?></div>
                    </div>
                    <div class="col-xs-2 text-right">
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $item]); ?>
                    </div>
                </div>
            </div>
            <!--/ page detail header -->
        </div>
        <div class="pa-md">
            <div class="row">
                <div class="col-md-12">
                    <?php $hookLog = $item->getLogs(); ?>
                    <?php if (!count($hookLog)): ?>
                        <div class="alert alert-warning col-md-6 col-md-offset-3 mt-md" style="white-space: normal;">
                            <h4>
                                <?php echo $view['translator']->trans('mautic.webhook.no.logs'); ?>
                            </h4>
                            <p>
                                <?php echo $view['translator']->trans('mautic.webhook.no.logs_desc'); ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <table class="table table-responsive table-striped">
                            <thead>
                                <tr>
                                    <th>
                                        <?php echo $view['translator']->trans('mautic.core.id'); ?>
                                    </th>
                                    <th>
                                        <?php echo $view['translator']->trans('mautic.webhook.status'); ?>
                                    </th>
                                    <th>
                                        <?php echo $view['translator']->trans('mautic.webhook.note'); ?>
                                    </th>
                                    <th>
                                        <?php echo $view['translator']->trans('mautic.webhook.runtime'); ?>
                                    </th>
                                    <th>
                                        <?php echo $view['translator']->trans('mautic.core.date.added'); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hookLog as $log): ?>
                                    <tr>
                                        <td><?php echo $log->getId(); ?></td>
                                        <td><?php
                                            echo $view->render('MauticWebhookBundle:Helper:labelcode.html.php', [
                                                'code' => $log->getStatusCode(),
                                            ]);
                                            ?>
                                        </td>
                                        <td><?php
                                            $note = $log->getNote();
                                            if ($note) :
                                                echo $note;
                                            else :
                                                echo $view['translator']->trans('mautic.webhook.webhook.logs.empty.response');
                                            endif;
                                        ?></td>
                                        <td><?php echo $log->getRuntime(); ?> s</td>
                                        <td><?php echo $view['date']->toFull($log->getDateAdded()); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="alert alert-info col-md-6 col-md-offset-3 mt-md">
                            <h4>
                                <?php echo $view['translator']->trans('mautic.webhook.webhook.logs.title'); ?>
                            </h4>
                            <p>
                                <?php echo $view['translator']->trans('mautic.webhook.webhook.logs.desc'); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- preview URL -->
        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mt-sm mb-0">
            <div class="panel-heading">
                <div class="panel-title"><?php echo $view['translator']->trans('mautic.webhook.webhook_url'); ?></div>
            </div>
            <div class="panel-body pt-xs">
                <div class="input-group">
                    <input onclick="this.setSelectionRange(0, this.value.length);" type="text" class="form-control" readonly
                           value="<?php echo $view->escape($item->getWebhookUrl()); ?>" />
                    <span class="input-group-btn">
                        <button class="btn btn-default btn-nospin" onclick="window.open('<?php echo $item->getWebhookUrl(); ?>', '_blank');">
                            <i class="fa fa-external-link"></i>
                        </button>
                    </span>
                </div>
            </div>

            <hr class="hr-w-2" style="width:50%">

            <!-- recent activity -->
            <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]); ?>
        </div>
    </div>
    <!--/ right section -->
</div>
