<?php
/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view['assets']->addScript('app/bundles/PageBundle/Assets/js/prefcenter.js');
if (isset($form)) {
    // add form tag
    $view['assets']->addCustomDeclaration($view['form']->start($form), 'bodyOpen');
}
?>
    <a href="javascript:void(null)"
       class="button btn btn-default btn-save"
       <?php if (isset($form)) : ?>onclick="saveUnsubscribePreferences('<?php echo $form->vars['id']; ?>')"<?php endif; ?>
       style="display:inline-block;text-decoration:none;border-color:#4e5d9d;border-width: 10px 20px;border-style:solid; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; background-color: #4e5d9d; display: inline-block;font-size: 16px; color: #ffffff; ">
        <?php echo $view['translator']->trans('mautic.page.form.saveprefs'); ?>
    </a>
    <div style="clear:both"></div>
<?php
if (isset($form)) {
    unset($form['subscribed_channels'], $form['buttons']['save'], $form['buttons']['cancel']);
    if (!$showContactCategories) {
        unset($form['global_categories']);
    }
    if (!$showContactSegments) {
        unset($form['lead_lists']);
    }
    if (!$showContactPauseDates) {
        unset($form['contact_pause_start_date_email'], $form['contact_pause_end_date_email']);
    }
    if (!$showContactFrequency) {
        unset($form['frequency_number_email'], $form['frequency_time_email']);
    }
    if (!$showContactPreferredChannels) {
        unset($form['preferred_channel']);
    }
    // add close form tag before the custom tag to prevent cascading forms
    // in case there is already an unsubscribe form on the page
    // that's why we can't use the bodyclose customdeclaration
    if (isset($custom_tag)) {
        echo $custom_tag;
        $view['assets']->addCustomDeclaration($view['form']->end($form), 'customTag');
    } else {
        $view['assets']->addCustomDeclaration($view['form']->end($form), 'bodyClose');
    }
}
?>

