<?php if (isset($emailStats)): ?>
<div class="lead-stats-title"><?php echo $view['translator']->trans('mautic.lead.stats.email_title'); ?></div>
<div class="lead-stats">
    <div class="lead-stats-el">
        <span class="lead-stats-name"><?php echo $view['translator']->trans('mautic.lead.stats.sent_count'); ?></span>
        <span class="lead-stats-val"><?php echo $emailStats['sent_count']; ?></span>
    </div>
    <div>
        <span class="lead-stats-name"><?php echo $view['translator']->trans('mautic.lead.stats.open_rate'); ?></span>
        <span class="lead-stats-val"><?php echo $emailStats['open_rate'] * 100; ?>%</span>
    </div>
    <div>
        <span class="lead-stats-name"><?php echo $view['translator']->trans('mautic.lead.stats.click_through_rate'); ?></span>
        <span class="lead-stats-val"><?php echo $emailStats['click_through_rate'] * 100; ?>%</span>
    </div>
    <div>
        <span class="lead-stats-name"><?php echo $view['translator']->trans('mautic.lead.stats.click_through_open_rate'); ?></span>
        <span class="lead-stats-val"><?php echo $emailStats['click_through_open_rate'] * 100; ?>%</span>
    </div>
</div>
<?php endif; ?>
