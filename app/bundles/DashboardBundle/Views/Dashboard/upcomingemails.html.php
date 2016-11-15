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

<?php if ($upcomingEmails) : ?>
    <ul class="list-group mb-0">
        <?php foreach ($upcomingEmails as $email): ?>
            <li class="list-group-item bg-auto bg-light-xs">
                <div class="box-layout">
                    <div class="col-md-1 va-m">
                        <h3><span class="fa <?php echo isset($icons['email']) ? $icons['email'] : ''; ?> fw-sb text-success"></span></h3>
                    </div>
                    <div class="col-md-4 va-m">
                        <h5 class="fw-sb text-primary">
                            <a href="<?php echo $view['router']->path('mautic_campaign_action', ['objectAction' => 'view', 'objectId' => $email['campaign_id']]); ?>" data-toggle="ajax">
                                <?php echo $email['campaign_name']; ?>
                            </a>
                        </h5>
                        <span class="text-white dark-sm"><?php echo $email['event_name']; ?></span>
                    </div>
                    <div class="col-md-4 va-m text-right">
                        <a class="btn btn-sm btn-success"  href="<?php echo $view['router']->path('mautic_contact_action', ['objectAction' => 'view', 'objectId' => $email['lead_id']]); ?>" data-toggle="ajax">
                            <span class="fa <?php echo isset($icons['lead']) ? $icons['lead'] : ''; ?>"></span>
                            <?php echo $email['lead_name']; ?>
                        </a>
                    </div>
                    <div class="col-md-3 va-m text-right">
                        <?php echo $view['date']->toFull($email['trigger_date']); ?>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
        <div class="alert alert-warning" role="alert">
            <?php echo $view['translator']->trans('mautic.note.no.upcoming.emails'); ?>
        </div>
<?php endif; ?>