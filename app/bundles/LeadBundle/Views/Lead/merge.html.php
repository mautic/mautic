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

<?php if ($tmpl == 'index'): ?>
<div class="lead-merge-form">
    <?php echo $view->render('MauticCoreBundle:Helper:search.html.php', [
        'searchId'    => (empty($searchId)) ? null : $searchId,
        'searchValue' => $searchValue,
        'action'      => $currentRoute,
        'searchHelp'  => false,
        'target'      => '.lead-merge-options',
        'tmpl'        => 'update',
    ]); ?>
    <div class="lead-merge-options mt-sm">
<?php endif; ?>

        <?php echo $view['form']->start($form); ?>

        <div class="hide">
            <?php echo $view['form']->row($form['buttons']); ?>
        </div>

        <?php echo $view['form']->end($form); ?>

<?php if ($tmpl == 'index'): ?>
    </div>
</div>
<?php endif; ?>