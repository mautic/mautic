<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!isset($class)) {
    $class = 'table';
}
if (!isset($shortenLinkText)) {
    $shortenLinkText = 30;
}
$showHeaders = [
    'mautic.dashboard.label.contact.email.address',
    'mautic.dashboard.label.contact.open',
    'mautic.dashboard.label.contact.click',
    'mautic.dashboard.label.email.name',
    'mautic.dashboard.label.segment.name',
    'mautic.dashboard.label.company.name',
    'mautic.dashboard.label.campaign.name',
];
$showValues = [
    'contact_email',
    'open',
    'click',
    'email_name',
    'segment_name',
    'company_name',
    'campaign_name',
];
?>

<table class="<?php echo $class; ?>">
    <?php if (!empty($headItems)) : ?>
        <thead>
            <tr>
            <?php foreach ($headItems as $headItem) : ?>
                <?php if (in_array($headItem, $showHeaders)) : ?>
                <th><?php echo $view['translator']->trans($headItem); ?></th>
                <?php endif; ?>
            <?php endforeach; ?>
            </tr>
        </thead>
    <?php endif; ?>
    <?php if (!empty($bodyItems)) : ?>
        <tbody>
            <?php foreach ($bodyItems as $id => $row) : ?>
                <tr>
                    <?php if (is_array($row)) : ?>
                        <?php foreach ($row as $key => $item) : ?>
                            <?php if (in_array($key, $showValues)) : ?>
                            <td>
                                <?php if ($key === 'contact_email' && !empty($row['contact_id'])) : ?>
                                    <a href="<?php echo $view['router']->path('mautic_contact_action', ['objectAction' => 'view', 'objectId' => $row['contact_id']]); ?>"
                                        title="<?php echo $item; ?>"
                                        data-toggle="ajax">
                                        <?php echo $view['assets']->shortenText($item, $shortenLinkText); ?>
                                    </a>
                                <?php elseif ($key === 'email_name' && !empty($row['email_id'])) : ?>
                                    <a href="<?php echo $view['router']->path('mautic_email_action', ['objectAction' => 'view', 'objectId' => $row['email_id']]); ?>"
                                        title="<?php echo $item; ?>"
                                        data-toggle="ajax">
                                        <?php echo $view['assets']->shortenText($item, $shortenLinkText); ?>
                                    </a>
                                <?php elseif ($key === 'segment_name' && !empty($row['segment_id'])) : ?>
                                    <a href="<?php echo $view['router']->path('mautic_segment_action', ['objectAction' => 'view', 'objectId' => $row['segment_id']]); ?>"
                                        title="<?php echo $item; ?>"
                                        data-toggle="ajax">
                                        <?php echo $view['assets']->shortenText($item, $shortenLinkText); ?>
                                    </a>
                                <?php elseif ($key === 'company_name' && !empty($row['company_id'])) : ?>
                                    <a href="<?php echo $view['router']->path('mautic_company_action', ['objectAction' => 'edit', 'objectId' => $row['company_id']]); ?>"
                                        title="<?php echo $item; ?>"
                                        data-toggle="ajax">
                                        <?php echo $view['assets']->shortenText($item, $shortenLinkText); ?>
                                    </a>
                                <?php elseif ($key === 'campaign_name' && !empty($row['campaign_id'])) : ?>
                                    <a href="<?php echo $view['router']->path('mautic_campaign_action', ['objectAction' => 'view', 'objectId' => $row['campaign_id']]); ?>"
                                        title="<?php echo $item; ?>"
                                        data-toggle="ajax">
                                        <?php echo $view['assets']->shortenText($item, $shortenLinkText); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo $item; ?>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    <?php endif; ?>
</table>
