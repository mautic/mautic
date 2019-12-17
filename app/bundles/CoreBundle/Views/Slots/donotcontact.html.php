<?php
/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>

<?php if (isset($form)) : ?>
    <?php if ($doNotContactText):?>
        <?php echo $doNotContactText; ?>
    <?php endif; ?>
<?php else: ?>
    <?php echo $view['translator']->trans('mautic.email.do_not_contact.text'); ?>
<?php endif; ?>
